<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 45 günden eski DM sesli mesajlarını temizler (dosya + kayıt).
 * Sadece ses dosyaları (audio/*) silinir; metin/resim/diğer dosyalar kalır.
 * Cron: günde bir (routes/console.php'de zamanlanır).
 */
class DmSesTemizle extends Command
{
    protected $signature   = 'dm:ses-temizle {--gun=45 : Kaç günden eski sesler silinsin}';
    protected $description = '45 günden eski DM sesli mesajlarını (ses dosyası + kayıt) siler';

    public function handle(): int
    {
        if (!Schema::hasTable('admin_dm_mesajlar')) {
            $this->warn('admin_dm_mesajlar tablosu yok, çıkılıyor.');
            return self::SUCCESS;
        }

        $gun   = (int) $this->option('gun') ?: 45;
        $sinir = now()->subDays($gun);

        $eskiler = DB::table('admin_dm_mesajlar')
            ->where(function ($q) {
                $q->where('dosya_tip', 'like', 'audio/%');
                foreach (['webm', 'weba', 'ogg', 'oga', 'm4a', 'mp3', 'wav', 'aac'] as $ext) {
                    $q->orWhere('dosya', 'like', '%.' . $ext);
                }
            })
            ->whereNotNull('created_at')
            ->where('created_at', '<', $sinir)
            ->get(['id', 'dosya']);

        $silinen = 0;
        foreach ($eskiler as $m) {
            if (!empty($m->dosya)) {
                $yol = public_path($m->dosya);
                if (is_file($yol)) {
                    @unlink($yol);
                }
            }
            DB::table('admin_dm_mesajlar')->where('id', $m->id)->delete();
            $silinen++;
        }

        $this->info("Temizlenen sesli mesaj: {$silinen} ({$gun} günden eski)");
        return self::SUCCESS;
    }
}
