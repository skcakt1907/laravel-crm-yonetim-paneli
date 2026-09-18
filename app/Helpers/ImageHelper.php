<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Yüklenen resmi WebP'e çevirir, başarısız olursa orijinal uzantıyla kaydeder.
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $uploadDir  Hedef klasör (public_path ile tam yol)
     * @param string $baseName   Uzantısız dosya adı
     * @return string            Kaydedilen dosya adı (webp veya orijinal uzantı)
     */
    public static function saveAsWebp($file, string $uploadDir, string $baseName): string
    {
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());

        // SVG ve dönüştürülemeyen tipler direkt kaydedilir
        if ($extension === 'svg') {
            $filename = $baseName . '.svg';
            $file->move($uploadDir, $filename);
            return $filename;
        }

        $convertable = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

        if ($convertable && function_exists('imagewebp')) {
            $sourcePath = $file->getRealPath();
            $image = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
                'png'         => @imagecreatefrompng($sourcePath),
                'gif'         => @imagecreatefromgif($sourcePath),
                'webp'        => @imagecreatefromwebp($sourcePath),
                default       => null,
            };

            if ($image) {
                @imagepalettetotruecolor($image);
                @imagealphablending($image, true);
                @imagesavealpha($image, true);

                $targetFilename = $baseName . '.webp';
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetFilename;

                if (@imagewebp($image, $targetPath, 85)) {
                    @imagedestroy($image);
                    @chmod($targetPath, 0644);
                    return $targetFilename;
                }

                @imagedestroy($image);
            }
        }

        // Fallback: orijinal uzantıyla kaydet
        $filename = $baseName . '.' . $extension;
        $file->move($uploadDir, $filename);
        return $filename;
    }
}
