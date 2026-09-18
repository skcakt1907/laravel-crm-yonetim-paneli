<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use App\Services\FinansRaporu;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Günlük gelir-gider raporu — her gün 19:00'da muhasebeye mail atar.
 * Varsayılan olarak BUGÜNÜN hareketlerini raporlar. Panelde "Günlük Raporlar"
 * sayfası da bugünü açıyor; ikisi aynı günü göstersin diye böyle.
 * NOT: 19:00'dan sonra girilecek hareketler o günün raporuna yansımaz
 * (rapor gün bitmeden gönderiliyor).
 */
class FinansGunlukRapor extends Command
{
    protected $signature   = 'finans:gunluk-rapor {--tarih= : Y-m-d (boşsa bugün)}';
    protected $description = 'Günlük gelir-gider raporunu muhasebeye e-posta olarak gönderir';

    public function handle(): int
    {
        $tarih = $this->option('tarih')
            ? Carbon::parse($this->option('tarih'))
            : Carbon::today();

        // ── GUNDE BIR KEZ ─────────────────────────────────────
        // Rapor iki kez gonderiliyordu. Zamanlayici arka arkaya
        // tetiklenirse (cPanel'de iki "schedule:run" cron girdisi gibi)
        // ikinci calisma buradan doner. Once yeri kapariz, sonra
        // gondeririz; boylece yaris durumunda da tek mail gider.
        if (\Illuminate\Support\Facades\Schema::hasTable('gunluk_gorev_log')) {
            $kapildi = \Illuminate\Support\Facades\DB::table('gunluk_gorev_log')->insertOrIgnore([
                'gorev'      => 'finans-gunluk-rapor',
                'tarih'      => $tarih->toDateString(),
                'created_at' => now(),
            ]);

            if (! $kapildi) {
                $this->info('Bu rapor bugun zaten gonderilmis, atlandi.');
                return self::SUCCESS;
            }
        }

        $veri = FinansRaporu::gunlukVeri($tarih);
        $alicilar = FinansRaporu::alicilar();

        if (empty($alicilar)) {
            $this->warn('Rapor gönderilecek yönetici bulunamadı.');
            return self::SUCCESS;
        }

        $konu = '📊 Günlük Finans Raporu — ' . $veri['tarih']->format('d.m.Y')
            . ' (net ' . number_format($veri['net'], 2, ',', '.') . ' TL)';
        $govde = FinansRaporu::gunlukMailGovdesi($veri);

        $gonderilen = 0;
        foreach ($alicilar as $mail) {
            try {
                EmailNotificationService::send($mail, $konu, $govde);
                $gonderilen++;
            } catch (\Throwable $e) {
                Log::warning('Günlük finans raporu gönderilemedi', ['mail' => $mail, 'hata' => $e->getMessage()]);
            }
        }

        Log::info('Günlük finans raporu gönderildi', [
            'tarih' => $veri['tarih']->toDateString(),
            'gelir' => $veri['gelir_toplam'], 'gider' => $veri['gider_toplam'],
            'net' => $veri['net'], 'alici' => $gonderilen,
        ]);

        $this->info($veri['tarih']->format('d.m.Y') . ' raporu ' . $gonderilen . ' kişiye gönderildi. '
            . 'Gelir: ' . number_format($veri['gelir_toplam'], 2) . ' · Gider: ' . number_format($veri['gider_toplam'], 2));

        return self::SUCCESS;
    }
}
