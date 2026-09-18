<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Para birimi query parametresinden geliyorsa session'a kaydet
        if ($request->has('currency')) {
            $currency = strtoupper($request->get('currency'));
            
            // Sadece izin verilen para birimlerini kabul et
            if (in_array($currency, ['TRY', 'USD', 'EUR', 'AED'])) {
                session(['currency' => $currency]);
            }
        }
        
        // Dil değiştiğinde para birimini otomatik ayarla
        $currentLocale = app()->getLocale();
        $sessionLocale = session('locale', $currentLocale);
        $currencyMap = [
            'tr' => 'TRY',
            'en' => 'USD',
            'ar' => 'AED',
        ];
        
        // Eğer dil değiştiyse (lang parametresi varsa veya session'daki dil farklıysa) para birimini güncelle
        $shouldUpdateCurrency = false;
        if ($request->has('lang')) {
            // Lang parametresi varsa dil değişiyor demektir
            $shouldUpdateCurrency = true;
        } elseif (session()->has('locale')) {
            // Session'daki dil ile mevcut para birimi uyumlu mu kontrol et
            $sessionCurrency = session('currency', 'TRY');
            $expectedCurrency = $currencyMap[$sessionLocale] ?? 'TRY';
            if ($sessionCurrency !== $expectedCurrency) {
                $shouldUpdateCurrency = true;
            }
        }
        
        if ($shouldUpdateCurrency && isset($currencyMap[$sessionLocale]) && !$request->has('currency')) {
            // Currency parametresi yoksa dil'e göre ayarla
            session(['currency' => $currencyMap[$sessionLocale]]);
        }
        
        // Eğer session'da para birimi yoksa dil'e göre varsayılan para birimini kullan
        if (!session()->has('currency')) {
            $defaultCurrency = $currencyMap[$sessionLocale] ?? 'TRY';
            session(['currency' => $defaultCurrency]);
        }
        
        return $next($request);
    }
}
