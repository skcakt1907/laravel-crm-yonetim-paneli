<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use App\Services\FinansRaporu;
use App\Services\GunSonuRaporu;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Gün sonu bildirimi — CRM aktivitesi + personel işlem özetini, finans
 * raporuyla aynı saatte, aynı alıcılara mail olarak gönderir.
 */
class GunSonuBildirimi extends Command
{
    protected $signature   = 'gun:sonu-bildirimi {--tarih= : Y-m-d (boşsa bugün)}';
    protected $description = 'Gün sonu CRM + personel aktivite özetini yöneticilere e-posta olarak gönderir';

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
                'gorev'      => 'gun-sonu-bildirimi',
                'tarih'      => $tarih->toDateString(),
                'created_at' => now(),
            ]);

            if (! $kapildi) {
                $this->info('Bu rapor bugun zaten gonderilmis, atlandi.');
                return self::SUCCESS;
            }
        }

        $veri     = GunSonuRaporu::veri($tarih);
        $alicilar = FinansRaporu::alicilar();

        if (empty($alicilar)) {
            $this->warn('Rapor gönderilecek yönetici bulunamadı.');
            return self::SUCCESS;
        }

        $toplamAktivite = $veri['yeni_musteriler']->count() + $veri['yeni_notlar']->count()
            + $veri['yeni_gorevler']->count() + $veri['tamamlanan_gorevler']->count()
            + $veri['personel_islemleri']->count();

        $konu  = '📋 Gün Sonu Bildirimi — ' . $veri['tarih']->format('d.m.Y')
               . ' (' . $toplamAktivite . ' aktivite)';
        $govde = GunSonuRaporu::mailGovdesi($veri);

        $gonderilen = 0;
        foreach ($alicilar as $mail) {
            try {
                EmailNotificationService::send($mail, $konu, $govde);
                $gonderilen++;
            } catch (\Throwable $e) {
                Log::warning('Gün sonu bildirimi gönderilemedi', ['mail' => $mail, 'hata' => $e->getMessage()]);
            }
        }

        Log::info('Gün sonu bildirimi gönderildi', [
            'tarih' => $veri['tarih']->toDateString(),
            'aktivite' => $toplamAktivite, 'alici' => $gonderilen,
        ]);

        $this->info($veri['tarih']->format('d.m.Y') . ' gün sonu bildirimi ' . $gonderilen . ' kişiye gönderildi. '
            . 'Toplam aktivite: ' . $toplamAktivite);

        return self::SUCCESS;
    }
}
