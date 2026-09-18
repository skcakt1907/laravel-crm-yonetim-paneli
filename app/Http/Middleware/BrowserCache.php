<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BrowserCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        $path = $request->path();
        
        // Statik dosyalar için uzun süreli cache
        if (preg_match('/\.(jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf|eot|css|js)$/i', $path)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }
        // HTML sayfalar için kısa süreli cache
        elseif ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            // Kullanıcıya özel / dinamik sayfalar için cache yok
            $noCachePages = [
                'admin/', 'hesabim', 'bilgilerim', 'sepet', 'faturalarim', 'fatura',
                'web-paketlerim', 'alan-adlarim', 'hostinglerim', 'hosting',
                'tekliflerim', 'favorilerim', 'dosyalarim', 'bildirimlerim', 'bakiyem',
                'destek', 'bayi-basvuru', 'odeme', 'siparis', 'cikis',
            ];

            $isNoCache = false;
            foreach ($noCachePages as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $isNoCache = true;
                    break;
                }
            }

            // Auth oturumu varsa hiç cache verme (kullanıcı verisi içerebilir)
            if (auth()->guard('uye')->check() || session()->has('admin_id')) {
                $isNoCache = true;
            }

            if ($isNoCache) {
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
            } else {
                // Anonim sayfalar: SADECE tarayıcı (private) tutabilir, her seferinde
                // revalidate etsin. 'public' İDİ → LiteSpeed/Cloudflare gibi PAYLAŞIMLI
                // cache misafir sürümünü (Giriş Yap + bayat CSRF) girişli kullanıcılara
                // da servis ediyor, kullanıcı işlem yapınca login'e atılıyordu.
                $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
            }
        }

        // ETag header ekle — sadece cacheable response'lar için (no-cache sayfalarda ETag → 304 boş döner, sayfa kaybolur)
        $cacheControl = $response->headers->get('Cache-Control', '');
        $skipEtag = str_contains($cacheControl, 'no-store') || str_contains($cacheControl, 'no-cache');
        if (!$skipEtag && !$response->headers->has('ETag')) {
            $etag = md5($response->getContent());
            $response->headers->set('ETag', $etag);

            if ($request->headers->get('If-None-Match') === $etag) {
                return response('', 304)->withHeaders($response->headers->all());
            }
        }
        
        return $response;
    }
}
