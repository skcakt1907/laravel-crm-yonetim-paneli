<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\AylikAlacakController;
use Illuminate\Console\Command;

/**
 * Aylık alacak hatırlatması (görev #217/6).
 * Tahsil tarihi yaklaşan/gecikmiş alacaklar için yönetime mail atar ve
 * tahsil edilmiş & tarihi geçmiş kayıtları sonraki döneme taşır.
 */
class AylikAlacakHatirlat extends Command
{
    protected $signature   = 'alacak:hatirlat {--kuru : Sadece göster, mail atma}';
    protected $description = 'Yaklaşan aylık alacaklar için hatırlatma gönderir';

    public function handle(): int
    {
        $sonuc = (new AylikAlacakController())->hatirlatmaCekirdek((bool) $this->option('kuru'));

        if (($sonuc['durum'] ?? '') === 'tablo_yok') {
            $this->warn('aylik_alacaklar tablosu yok, çıkılıyor.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%sSonraki döneme taşınan: %d · yaklaşan kayıt: %d · bildirim gönderilen: %d',
            $this->option('kuru') ? '[KURU] ' : '',
            $sonuc['tasinan'] ?? 0,
            $sonuc['kayit'] ?? 0,
            $sonuc['bildirilen'] ?? 0
        ));

        return self::SUCCESS;
    }
}
