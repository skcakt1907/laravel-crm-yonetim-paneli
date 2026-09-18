<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class StaticFileController extends Controller
{
    /**
     * Statik dosyaları serve et (CSS, JS, images, fonts)
     * .htaccess çalışmadığında geçici çözüm
     */
    public function serve(Request $request, $path)
    {
        $filePath = public_path($path);
        
        // Güvenlik: Sadece public klasörü içindeki dosyalara izin ver
        $realPath = realpath($filePath);
        $publicPath = realpath(public_path());
        
        if (!$realPath || strpos($realPath, $publicPath) !== 0) {
            abort(404);
        }
        
        // Dosya yoksa 404
        if (!File::exists($filePath)) {
            abort(404);
        }
        
        // Dosya uzantısına göre MIME type belirle
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'json' => 'application/json',
            'map' => 'application/json',
        ];
        
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        // Dosya içeriğini oku
        $content = File::get($filePath);
        
        // Response oluştur
        $response = response($content, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000, immutable')
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        
        return $response;
    }
}
