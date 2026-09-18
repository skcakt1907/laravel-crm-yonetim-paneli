<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\AylikOdemeController;
use Illuminate\Console\Command;

/**
 * Aylık bildirimli ödemeler için vade hatırlatması.
 * 1 hafta / 3 gün / 1 gün kala + gecikmiş için patron + muhasebe rollerine
 * MAİL + uygulama içi bildirim gönderir; vadesi geçen 'ödendi'leri yeniler.
 * routes/console.php'de günlük çalışacak şekilde zamanlanır.
 */
class AylikOdemeHatirlatCommand extends Command
{
    protected $signature = 'mail:aylik-odeme-hatirlat';
    protected $description = 'Aylık bildirimli ödeme vade hatırlatması (mail + panel bildirimi)';

    public function handle(): int
    {
        $sonuc = app(AylikOdemeController::class)->hatirlatmaCekirdek();
        $this->info('Aylık ödeme hatırlatma: ' . json_encode($sonuc, JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }
}
