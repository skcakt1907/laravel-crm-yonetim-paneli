<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UyeAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->guard('uye')->check()) {
            return redirect()->route('giris')->with('error', 'Lütfen giriş yapınız.');
        }
        
        // NOT: session()->save() BURADA ÇAĞRILMAMALI. Erken kayıt, flash verisini
        // (başarı/hata toast mesajları) view render olmadan yaşlandırıp siler.
        // Laravel'in StartSession middleware'i zaten istek sonunda kaydeder.
        return $next($request);
    }
}
