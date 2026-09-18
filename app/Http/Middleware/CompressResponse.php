<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompressResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Dosya ve stream yanıtlarının içeriği değiştirilemez:
        // response()->file() bir BinaryFileResponse döner ve setContent() çağrılınca
        // LogicException fırlatır ("The content cannot be set on a BinaryFileResponse instance").
        // Tarayıcılar Accept-Encoding: gzip gönderdiği için bu, /temalar/ altındaki statik
        // tema demolarını 500'e düşürüyordu. Bu yanıtları sıkıştırmadan geçiyoruz.
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        // Sadece HTML, CSS, JS, JSON, XML için compression yap
        $contentType = $response->headers->get('Content-Type', '');
        $shouldCompress = false;
        
        if (str_contains($contentType, 'text/html') || 
            str_contains($contentType, 'text/css') || 
            str_contains($contentType, 'application/javascript') ||
            str_contains($contentType, 'text/javascript') ||
            str_contains($contentType, 'application/json') ||
            str_contains($contentType, 'text/xml') ||
            str_contains($contentType, 'application/xml')) {
            $shouldCompress = true;
        }
        
        if ($shouldCompress && !$response->headers->has('Content-Encoding')) {
            $content = $response->getContent();
            
            // Brotli compression (daha iyi sıkıştırma)
            if (function_exists('brotli_compress') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'br')) {
                $compressed = brotli_compress($content, 6);
                if ($compressed !== false) {
                    $response->setContent($compressed);
                    $response->headers->set('Content-Encoding', 'br');
                    $response->headers->set('Vary', 'Accept-Encoding');
                }
            }
            // Gzip compression (fallback)
            elseif (function_exists('gzencode') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
                $compressed = gzencode($content, 6);
                if ($compressed !== false) {
                    $response->setContent($compressed);
                    $response->headers->set('Content-Encoding', 'gzip');
                    $response->headers->set('Vary', 'Accept-Encoding');
                }
            }
        }
        
        return $response;
    }
}
