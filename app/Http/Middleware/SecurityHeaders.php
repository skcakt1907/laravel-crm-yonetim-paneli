<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Content Type Options - MIME sniffing koruması
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Frame Options - Clickjacking koruması
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions Policy (eski adıyla Feature-Policy)
        // microphone=(self) → DM sesli mesaj için kendi sitemizde mikrofon izni.
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(self), camera=()');
        
        // Sadece production'da HSTS ekle
        if (config('app.env') === 'production' && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
