<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RolKontrol
{
    /**
     * Handle an incoming request.
     *
     * Kullanim:
     *   ->middleware('rolkontrol')                 // sadece giris kontrolu + sayfa yetkisi
     *   ->middleware('rolkontrol:patron')          // sadece patron rolu
     *   ->middleware('rolkontrol:patron,calisan')  // birden cok slug
     *
     * NOT: Roller dinamik oldugu icin slug bazli kontrol yapiyoruz.
     *      Sayfa bazli yetki ise rol_yetkileri tablosundan gelir.
     *      Patron (korumali rol) her seye erisir.
     */
    public function handle(Request $request, Closure $next, ...$izinliSluglar): Response
    {
        if (!session()->has('admin_logged_in') || !session('admin_logged_in')) {
            return redirect()->route('admin.giris')->with('error', 'Lutfen giris yapin!');
        }

        $adminRolId = (int) session('admin_rol', 0);
        $rol = $this->rolGetir($adminRolId);

        if (!$rol) {
            return redirect()->route('admin.giris')->with('error', 'Rol tanimi bulunamadi!');
        }

        // 1) Slug bazli rol kisitlamasi (middleware parametresi)
        if (!empty($izinliSluglar) && !in_array($rol->slug, $izinliSluglar, true)) {
            return $this->reddet($request, $rol);
        }

        // 2) Patron (korumali) her seye erisir
        if ($rol->korumali) {
            return $next($request);
        }

        // 3) Sayfa bazli yetki kontrolu (rol_yetkileri)
        $routeName = optional($request->route())->getName();
        if (!$routeName) {
            return $next($request);
        }

        // Her rol icin ana dashboard'a erisim her zaman acik
        if ($routeName === 'admin.dashboard') {
            return $next($request);
        }
        // Bayi slug'i varsa bayi dashboard her zaman acik
        if ($rol->slug === 'bayi' && $routeName === 'admin.bayi.dashboard') {
            return $next($request);
        }

        // Takvim herkese acik: her rolun kendi takvimi olur (gorunurluk
        // kontrolu TakvimController icinde rol bazli yapilir).
        if (\Illuminate\Support\Str::startsWith($routeName, 'admin.takvim.')) {
            return $next($request);
        }

        // Geo referans uclari (il/ilce/mahalle dropdown beslemesi) herkese acik.
        if (\Illuminate\Support\Str::startsWith($routeName, 'admin.geo.')) {
            return $next($request);
        }

        // Tablolar PIN giris akisi her zaman acik — bunlar TablolarKilit
        // middleware'inin kendi guvenlik akisi (PIN ekrani / dogrula / cikis).
        // RolKontrol bunlari engellerse, yetkili kullanici PIN ekranina ulasamaz.
        // NOT: kurulum/ayarlar HARIC tutulur (PIN'i yoneten hassas sayfalar);
        // onlar normal yetki kontrolunden gecer.
        $kilitAcik = ['admin.tablolar.kilit.pin', 'admin.tablolar.kilit.pin.dogrula', 'admin.tablolar.kilit.cikis'];
        if (in_array($routeName, $kilitAcik, true)) {
            return $next($request);
        }

        // Bildirim sayacı (üst bar zil rozeti) herkese açık: salt okunur bir
        // sayı döndürür, sayfa içeriği içermez. Yetkiye tabi tutulursa her
        // sayfa yüklemesinde RED + log kaydı üretir (disk şişmesinin sebebiydi).
        if ($routeName === 'admin.bildirimler.sayim') {
            return $next($request);
        }

        // ── TAM YETKI (NEREDEYSE-PATRON) ──
        // Rolun tam_yetki=1 ise: Roller ve Yoneticiler haric HER admin sayfasina
        // erisebilir. Panel yetki kutularina bakilmaz. (Panelden acilip kapatilabilir.)
        if (!empty($rol->tam_yetki)) {
            $yasakli = ['admin.roller.', 'admin.yoneticiler.'];
            foreach ($yasakli as $onek) {
                if (\Illuminate\Support\Str::startsWith($routeName, $onek)) {
                    return $this->reddet($request, $rol);
                }
            }
            return $next($request);
        }

        // ── SADECE PATRON + TAM_YETKI sayfalari ──
        // Bu sayfalar hassastir (orn. Giris Loglari). Yetki katalogundan bagimsiz
        // olarak normal rollere KAPALIDIR; yalnizca Patron (yukarida gecer) ve
        // tam_yetki=1 (yukarida gecer) erisebilir. Buraya gelen = normal rol → reddet.
        $sadeceTamYetki = ['admin.giris-loglari.'];
        foreach ($sadeceTamYetki as $onek) {
            if (\Illuminate\Support\Str::startsWith($routeName, $onek)) {
                return $this->reddet($request, $rol);
            }
        }

        // ── SADECE SAYFA GORUNTULEME (GET/HEAD) yetki kontrolune tabidir ──
        // Yetki katalogu (AdminSayfaKatalogu) yalnizca GET route'larini listeler,
        // dolayisiyla rol_yetkileri tablosunda sadece GET sayfalari bulunur.
        // Bir kullanici bir bolumu GOREBILIYORSA, o bolumdeki islemleri
        // (kaydet/onayla/sil = POST/PUT/PATCH/DELETE) de yapabilmelidir.
        // Bu yuzden GET disi (islem) isteklerini yetki kontrolune sokmadan geciriyoruz.
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return $next($request);
        }

        $izinVarMi = $this->rolYetkisiVarMi($rol->id, $routeName);

        if (!$izinVarMi) {
            \Log::warning('Rol yetkisi olmayan sayfaya erisim denemesi', [
                'yonetici_id' => session('admin_id'),
                'rol_id' => $rol->id,
                'rol_slug' => $rol->slug,
                'route' => $routeName,
            ]);
            return $this->reddet($request, $rol);
        }

        return $next($request);
    }

    private function rolGetir(int $rolId)
    {
        if ($rolId <= 0) {
            return null;
        }
        // CACHE YOK: yetki/rol degisikligi aninda etkili olsun diye her istekte DB'den okunur.
        return DB::table('roller')->where('id', $rolId)->first();
    }

    private function rolYetkisiVarMi(int $rolId, string $routeName): bool
    {
        // CACHE YOK: panelden verilen yetki aninda gecerli olsun.
        return DB::table('rol_yetkileri')
            ->where('rol_id', $rolId)
            ->where('sayfa_route', $routeName)
            ->where('gorebilir', 1)
            ->exists();
    }

    private function reddet(Request $request, $rol): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Bu sayfaya erisim yetkiniz yok!'], 403);
        }

        // Musteri rolu panelde degil, hesabim sayfasinda
        if ($rol && $rol->slug === 'musteri') {
            return redirect()->route('hesabim')->with('error', 'Bu sayfaya erisim yetkiniz yok!');
        }

        $redirectRoute = ($rol && $rol->slug === 'bayi') ? 'admin.bayi.dashboard' : 'admin.dashboard';
        return redirect()->route($redirectRoute)->with('error', 'Bu sayfaya erisim yetkiniz yok!');
    }
}