<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PageMaintenanceMode
{
    /**
     * Sayfa bazlı bakım modu kontrolü
     */
    public function handle(Request $request, Closure $next)
    {
        // Admin kullanıcıları bypass
        if (session()->has('admin_id') || session()->has('admin_logged_in')) {
            return $next($request);
        }

        // Admin paneli URL'lerini bypass (giriş yapabilmek için)
        if ($request->is('admin*')) {
            return $next($request);
        }

        // === SİTE GENELİ BAKIM MODU KONTROLÜ ===
        if (Schema::hasTable('ayarlar')) {
            $ayarlar = DB::table('ayarlar')->first();
            if ($ayarlar && !empty($ayarlar->bakim_modu) && (int) $ayarlar->bakim_modu === 1) {
                return response()->view('errors.bakim', [
                    'baslik' => $ayarlar->bakim_baslik ?? 'Site Bakımda',
                    'mesaj' => $ayarlar->bakim_mesaj ?? 'Sitemiz şu anda bakım çalışması nedeniyle geçici olarak hizmet dışıdır.',
                ], 503);
            }
        }

        // === SAYFA BAZLI BAKIM MODU ===
        // Tablo var mı kontrol et
        if (!Schema::hasTable('sayfa_bakim')) {
            return $next($request);
        }
        
        // Aktif bakım modundaki sayfaları al
        $bakimSayfalari = DB::table('sayfa_bakim')
            ->where('aktif', true)
            ->get();
        
        if ($bakimSayfalari->isEmpty()) {
            return $next($request);
        }
        
        $currentPath = $request->path();
        $currentRouteName = $request->route() ? $request->route()->getName() : null;
        
        foreach ($bakimSayfalari as $sayfa) {
            $isMatch = false;
            
            // Route adı kontrolü
            if ($sayfa->route_name && $currentRouteName) {
                if (str_starts_with($currentRouteName, $sayfa->route_name)) {
                    $isMatch = true;
                }
            }
            
            // URL pattern kontrolü
            if (!$isMatch && $sayfa->url_pattern) {
                $pattern = str_replace(['*', '/'], ['.*', '\/'], $sayfa->url_pattern);
                $pattern = ltrim($pattern, '\/');
                if (preg_match('/^' . $pattern . '$/i', $currentPath)) {
                    $isMatch = true;
                }
            }
            
            // Tarih kontrolü
            if ($isMatch) {
                $now = now();
                
                // Başlangıç tarihi kontrolü
                if ($sayfa->baslangic_tarihi && $now < $sayfa->baslangic_tarihi) {
                    $isMatch = false;
                }
                
                // Bitiş tarihi kontrolü
                if ($sayfa->bitis_tarihi && $now > $sayfa->bitis_tarihi) {
                    $isMatch = false;
                }
            }
            
            if ($isMatch) {
                return response()->view('errors.sayfa-bakim', [
                    'baslik' => $sayfa->baslik ?? 'Sayfa Bakımda',
                    'mesaj' => $sayfa->mesaj ?? 'Bu sayfa şu anda bakımdadır.',
                    'sayfa_adi' => $sayfa->sayfa_adi,
                    'bitis_tarihi' => $sayfa->bitis_tarihi,
                ], 503);
            }
        }
        
        return $next($request);
    }
}
