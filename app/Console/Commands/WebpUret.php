<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Yükleme klasörlerini tarar; webp'i olmayan (veya eskimiş) jpg/jpeg/png için
 * yanına .webp üretir. Orijinal SİLİNMEZ (fallback). Idempotent — varsa atlar.
 * Cron ile çalışır → yeni yüklenen resimler birkaç dk içinde otomatik webp olur.
 */
class WebpUret extends Command
{
    protected $signature   = 'webp:uret {--q=82 : JPG kalitesi}';
    protected $description = 'Yükleme klasörlerindeki jpg/png için eksik .webp dosyalarını üretir';

    public function handle(): int
    {
        if (!function_exists('imagewebp')) {
            $this->error('GD/imagewebp yok, çıkılıyor.');
            return self::FAILURE;
        }

        // Sadece içerik/upload klasörleri (statik tema asset'leri zaten çevrildi)
        $hedefler = [
            base_path('tema/uploads'),
            public_path('uploads'),
            public_path('storage'),
            public_path('tema/uploads'),
        ];

        $jpgQ = (int) $this->option('q') ?: 82;
        $ok = 0; $skip = 0; $err = 0;

        foreach ($hedefler as $dir) {
            if (!is_dir($dir)) continue;
            $rii = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($rii as $f) {
                if (!$f->isFile()) continue;
                $ext = strtolower($f->getExtension());
                if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) continue;

                $src  = $f->getPathname();
                $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);
                if (is_file($webp) && filemtime($webp) >= filemtime($src)) { $skip++; continue; }

                $data = @file_get_contents($src);
                if ($data === false) { $err++; continue; }
                $im = @imagecreatefromstring($data); // içerikten oku (yanlış uzantı toleransı)
                if (!$im) { $err++; continue; }
                imagepalettetotruecolor($im);
                imagealphablending($im, false);
                imagesavealpha($im, true);
                $q = ($ext === 'png') ? 100 : $jpgQ; // png lossless
                $r = @imagewebp($im, $webp, $q);
                imagedestroy($im);
                $r ? $ok++ : $err++;
            }
        }

        $this->info("WebP üretildi: {$ok} | Atlandı: {$skip} | Hata: {$err}");
        return self::SUCCESS;
    }
}
