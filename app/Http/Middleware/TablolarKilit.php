<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tablolar Modülü Kilit Middleware
 * 
 * 1. Admin yetkili mi? (tablolar_kilit_ayar.yetkili_ids içinde mi?)
 * 2. PIN girilmiş mi? (session içinde tablolar_pin_ok = true mu?)
 * 
 * Yetkisiz → 403
 * Yetkili ama PIN yok → PIN ekranına yönlendir
 * Yetkili ve PIN tamam → izin ver
 */
class TablolarKilit
{
    public function handle(Request $request, Closure $next)
    {
        $adminId = session('admin_id');
        $adminRol = session('admin_rol');

        // Giriş yapmamış
        if (!$adminId) {
            return redirect()->route('admin.giris')
                ->with('error', 'Giriş yapmalısın.');
        }
        
        // Patron (rol=1) her zaman erişebilir, ama PIN'i yine de soracak
        $isPatron = ($adminRol == 1);
        
        // Kilit ayarını al
        $ayar = DB::table('tablolar_kilit_ayar')->orderByDesc('id')->first();
        
        // Ayar yoksa (kurulum yapılmamış) — sadece Patron erişebilir, kurulsun
        if (!$ayar) {
            if (!$isPatron) {
                abort(403, 'Tablolar modülü henüz aktif değil. Patron ile iletişime geç.');
            }
            return redirect()->route('admin.tablolar.kilit.kurulum')
                ->with('info', 'Tablolar modülü için PIN ve yetkili kullanıcılar belirle.');
        }
        
        // Yetkili mi kontrol et
        $yetkililer = json_decode($ayar->yetkili_ids, true) ?: [];
        $yetkiliMi = in_array($adminId, $yetkililer) || $isPatron;

        // ── ROL YETKISI DE GECERLI ──
        // Izinli listede olmasa bile, rolunde Tablolar yetkisi olan VEYA tam_yetki=1
        // olan kullanici da girebilir (PIN yine de sorulur). Boylece panelden
        // "Tablolar" yetkisi verilen herkes erisebilir.
        if (!$yetkiliMi && $adminRol) {
            $rol = DB::table('roller')->where('id', (int) $adminRol)->first();
            if ($rol) {
                // tam_yetki (neredeyse-patron) ise dogrudan gecer
                if (!empty($rol->tam_yetki)) {
                    $yetkiliMi = true;
                } else {
                    // rol_yetkileri'nde herhangi bir admin.tablolar.* sayfasi acik mi?
                    $tablolarYetkisi = DB::table('rol_yetkileri')
                        ->where('rol_id', $rol->id)
                        ->where('gorebilir', 1)
                        ->where('sayfa_route', 'like', 'admin.tablolar.%')
                        ->exists();
                    if ($tablolarYetkisi) {
                        $yetkiliMi = true;
                    }
                }
            }
        }

        if (!$yetkiliMi) {
            abort(403, '🔒 Tablolar modülüne erişim yetkin yok. Patron ile iletişime geç.');
        }
        
        // PIN doğrulanmış mı? (Session içinde, 4 saat geçerli)
        $pinTime = session('tablolar_pin_time');
        $pinValid = $pinTime && (time() - $pinTime) < (4 * 3600); // 4 saat
        
        if (!session('tablolar_pin_ok') || !$pinValid) {
            // PIN ekranına yönlendir
            session(['tablolar_redirect_after_pin' => $request->fullUrl()]);
            return redirect()->route('admin.tablolar.kilit.pin');
        }
        
        return $next($request);
    }
}