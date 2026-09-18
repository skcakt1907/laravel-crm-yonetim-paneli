<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use App\Services\FinansRaporu;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Borç takip raporu — her ayın 28'inde yönetime gönderilir.
 * Müşteri Borç Takip ekranı menüden gizlendi; rapor bu verileri okumaya devam eder.
 */
class FinansBorcRaporu extends Command
{
    protected $signature   = 'finans:borc-raporu {--zorla : Ayın 28i olmasa da gönder}';
    protected $description = 'Borç takip raporunu (şirket + müşteri) yönetime e-posta olarak gönderir';

    public function handle(): int
    {
        if (!$this->option('zorla') && Carbon::now()->day !== 28) {
            $this->info('Bugün ayın 28i değil, atlandı. (Zorlamak için --zorla)');
            return self::SUCCESS;
        }

        $veri = FinansRaporu::borcVerisi();
        $alicilar = FinansRaporu::alicilar();

        if (empty($alicilar)) {
            $this->warn('Rapor gönderilecek yönetici bulunamadı.');
            return self::SUCCESS;
        }

        $konu = '💼 Borç Takip Raporu — ' . Carbon::now()->format('m.Y');
        $govde = FinansRaporu::borcMailGovdesi($veri);

        $gonderilen = 0;
        foreach ($alicilar as $mail) {
            try {
                EmailNotificationService::send($mail, $konu, $govde);
                $gonderilen++;
            } catch (\Throwable $e) {
                Log::warning('Borç raporu gönderilemedi', ['mail' => $mail, 'hata' => $e->getMessage()]);
            }
        }

        Log::info('Borç takip raporu gönderildi', [
            'sirket' => $veri['sirket_toplam'], 'musteri' => $veri['musteri_toplam'], 'alici' => $gonderilen,
        ]);

        $this->info('Borç raporu ' . $gonderilen . ' kişiye gönderildi.');

        return self::SUCCESS;
    }
}
