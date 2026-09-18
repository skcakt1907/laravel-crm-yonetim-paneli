<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use App\Services\SosyalMedyaRaporu;
use App\Services\SosyalMedyaTakip;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Sosyal medya gün sonu raporu — her gün 19:00, AYRI mail.
 *
 * Finans raporu ve Gün Sonu Bildirimi'yle birleştirilmedi: alıcıları farklı
 * (burada Seda Hanım var, orada yok) ve içeriği farklı bir ekibi ilgilendiriyor.
 *
 * --kuru ile mail göndermeden çıktı alınabilir (gönderim öncesi kontrol için).
 */
class SosyalMedyaGunSonu extends Command
{
    protected $signature = 'sosyal-medya:gun-sonu
                            {--tarih= : Y-m-d (boşsa bugün)}
                            {--kuru : Mail gönderme, sadece özeti ekrana yaz}';

    protected $description = 'Sosyal medya gün sonu paylaşım raporunu e-posta ile gönderir';

    public function handle(SosyalMedyaTakip $takip): int
    {
        $tarih = $this->option('tarih')
            ? Carbon::parse($this->option('tarih'))->startOfDay()
            : Carbon::today();

        $kuru = (bool) $this->option('kuru');

        $veri = $takip->gunSonuVerisi($tarih);

        $planliSayi = count($veri['tamamlanan']) + count($veri['eksik'])
                    + count($veri['ertelenen']) + count($veri['iptal']);

        // ── GÖNDERİLECEK BİR ŞEY VAR MI ───────────────────────
        // Planı olmayan gün (pazar, resmî tatil) için mail gönderilmez.
        // Boş rapor her akşam gelirse insanlar raporu okumayı bırakır.
        //
        // Bu kontrol KİLİTTEN ÖNCE yapılır. Ters sırada olduğunda, planın
        // henüz girilmediği bir anda çalışan komut günün kilidini boşuna
        // kapatıyor ve akşamki gerçek gönderim atlanıyordu.
        if ($planliSayi === 0 && empty($veri['bugune_ertelenen'])) {
            $this->info($tarih->format('d.m.Y') . ' için planlanmış paylaşım yok, mail gönderilmedi.');
            return self::SUCCESS;
        }

        // ── GÜNDE BİR KEZ ─────────────────────────────────────
        // Ödeme hatırlatma mailinde yaşananın aynısı burada olmasın diye:
        // cPanel'de iki "schedule:run" satırı varsa komut iki kez çalışır.
        // ÖNCE yeri kapatıyoruz, SONRA gönderiyoruz — kontrol-sonra-yaz
        // sırası olsaydı iki süreç aynı anda "gönderilmemiş" görüp iki mail
        // atardı. (Yukarıdaki içerik kontrolü bu yarışı açmaz: iki süreç de
        // geçse bile kilidi yalnızca biri kapabilir.)
        if (! $kuru && Schema::hasTable('gunluk_gorev_log')) {
            $kapildi = DB::table('gunluk_gorev_log')->insertOrIgnore([
                'gorev'      => 'sosyal-medya-gun-sonu',
                'tarih'      => $tarih->toDateString(),
                'created_at' => now(),
            ]);

            if (! $kapildi) {
                $this->info('Bu rapor bugün zaten gönderilmiş, atlandı.');
                return self::SUCCESS;
            }
        }

        $konu  = SosyalMedyaRaporu::konu($veri, $tarih);
        $govde = SosyalMedyaRaporu::mailGovdesi($veri, $tarih);

        if ($kuru) {
            $this->ekranaYaz($tarih, $veri, $konu);
            $this->warn('KURU ÇALIŞMA — mail gönderilmedi.');
            return self::SUCCESS;
        }

        $alicilar = SosyalMedyaRaporu::alicilar();

        if (empty($alicilar)) {
            $this->warn('Rapor gönderilecek yönetici bulunamadı.');
            return self::SUCCESS;
        }

        $gonderilen = 0;
        foreach ($alicilar as $mail) {
            try {
                EmailNotificationService::send($mail, $konu, $govde);
                $gonderilen++;
            } catch (\Throwable $e) {
                Log::warning('Sosyal medya gün sonu raporu gönderilemedi', [
                    'mail' => $mail, 'hata' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Sosyal medya gün sonu raporu gönderildi', [
            'tarih' => $tarih->toDateString(),
            'eksik' => count($veri['eksik']),
            'alici' => $gonderilen,
        ]);

        $this->info($tarih->format('d.m.Y') . ' sosyal medya raporu ' . $gonderilen
            . ' kişiye gönderildi. Eksik: ' . count($veri['eksik']));

        return self::SUCCESS;
    }

    private function ekranaYaz(Carbon $tarih, array $veri, string $konu): void
    {
        $this->line('Konu: ' . $konu);
        $this->newLine();

        foreach ([
            'eksik'            => 'EKSİK',
            'tamamlanan'       => 'TAMAMLANAN',
            'ertelenen'        => 'ERTELENEN',
            'iptal'            => 'İPTAL',
        ] as $anahtar => $etiket) {
            if (empty($veri[$anahtar])) {
                continue;
            }
            $this->line($etiket . ' (' . count($veri[$anahtar]) . ')');
            foreach ($veri[$anahtar] as $r) {
                $this->line(sprintf('   %-28s %-12s %d/%d  %s',
                    mb_substr($r['marka'], 0, 28), $r['platform'],
                    $r['yapilan'], $r['hedef'], $r['sorumlu']));
            }
        }

        if (! empty($veri['bugune_ertelenen'])) {
            $this->line('BUGÜNE ERTELENEN (' . count($veri['bugune_ertelenen']) . ')');
            foreach ($veri['bugune_ertelenen'] as $r) {
                $this->line('   ' . $r['marka'] . ' · ' . $r['platform'] . ' — ' . $r['eski_tarih']);
            }
        }
    }
}
