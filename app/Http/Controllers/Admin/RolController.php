<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminSayfaKatalogu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolController extends Controller
{
    /**
     * Rol yonetimine kim girebilir?
     *
     * Eskiden yalnizca rol id = 1 (Patron) idi. Artik roller.korumali = 1 olan
     * HER rol yetkilidir — RolKontrol middleware'i de ayni mantigi kullanir
     * (korumali rol her seye erisir). Boylece "Patron gibi tam yetkili ama
     * Patron olarak gorunmeyen" roller (orn. Yazilimci) rol/yonetici
     * yonetebilir. Yetki vermek/almak icin tek yer: roller.korumali kolonu.
     */
    private function patronMu()
    {
        $rolId = (int) session('admin_rol');

        if ($rolId === 1) {
            return true;
        }
        if ($rolId <= 0) {
            return false;
        }

        $rol = DB::table('roller')->where('id', $rolId)->first();

        return $rol && !empty($rol->korumali);
    }

     public function index()
    {
        if (!$this->patronMu()) {
            return redirect()->route('admin.dashboard')->with('error', 'Sadece patron rol yonetebilir!');
        }
 
        // Subquery ile yetki sayısı - GROUP BY kullanmadan, daha temiz
        $yetkiSayisi = DB::table('rol_yetkileri')
            ->select('rol_id', DB::raw('SUM(CASE WHEN gorebilir = 1 THEN 1 ELSE 0 END) as toplam'))
            ->groupBy('rol_id');
 
        $roller = DB::table('roller')
            ->leftJoinSub($yetkiSayisi, 'yetki_say', function ($join) {
                $join->on('roller.id', '=', 'yetki_say.rol_id');
            })
            ->select('roller.*', DB::raw('COALESCE(yetki_say.toplam, 0) as yetki_sayisi'))
            ->orderBy('roller.id')
            ->get();
 
        return view('admin.roller.index', compact('roller'));
    }

    public function ekle()
    {
        if (!$this->patronMu()) {
            return redirect()->route('admin.dashboard')->with('error', 'Sadece patron rol yonetebilir!');
        }

        $kategoriler = AdminSayfaKatalogu::kategorilereGore();
        $mevcutYetkiler = [];

        return view('admin.roller.ekle', compact('kategoriler', 'mevcutYetkiler'));
    }

    public function eklePost(Request $request)
    {
        if (!$this->patronMu()) {
            return redirect()->route('admin.dashboard')->with('error', 'Sadece patron rol yonetebilir!');
        }

        $request->validate([
            'ad' => 'required|string|max:100',
            'aciklama' => 'nullable|string|max:255',
            'ikon' => 'nullable|string|max:10',
            'renk' => 'nullable|string|max:20',
        ]);

        $slug = Str::slug($request->ad);
        if (empty($slug)) {
            $slug = 'rol-' . Str::random(6);
        }
        $taban = $slug;
        $i = 1;
        while (DB::table('roller')->where('slug', $slug)->exists()) {
            $slug = $taban . '-' . (++$i);
        }

        $rolId = DB::table('roller')->insertGetId([
            'ad' => $request->ad,
            'slug' => $slug,
            'aciklama' => $request->aciklama,
            'ikon' => $request->ikon ?: 'ЁЯЫбя╕П',
            'renk' => $request->renk ?: 'primary',
            'korumali' => 0,
            'tam_yetki' => $request->filled('tam_yetki') ? 1 : 0,
            'durum' => $request->filled('durum') ? 1 : 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->yetkileriKaydet($rolId, $request);

        return redirect()->route('admin.roller.index')->with('success', 'Rol olusturuldu ve yetkiler kaydedildi.');
    }

    public function duzenle($id)
    {
        if (!$this->patronMu()) {
            return redirect()->route('admin.dashboard')->with('error', 'Sadece patron rol yonetebilir!');
        }

        $rol = DB::table('roller')->where('id', $id)->first();
        if (!$rol) {
            return redirect()->route('admin.roller.index')->with('error', 'Rol bulunamadi!');
        }

        $kategoriler = AdminSayfaKatalogu::kategorilereGore();
        $mevcutYetkiler = DB::table('rol_yetkileri')
            ->where('rol_id', $id)
            ->pluck('gorebilir', 'sayfa_route')
            ->toArray();

        return view('admin.roller.duzenle', compact('rol', 'kategoriler', 'mevcutYetkiler'));
    }

    public function duzenlePost(Request $request, $id)
    {
        if (!$this->patronMu()) {
            return redirect()->route('admin.dashboard')->with('error', 'Sadece patron rol yonetebilir!');
        }

        $rol = DB::table('roller')->where('id', $id)->first();
        if (!$rol) {
            return redirect()->route('admin.roller.index')->with('error', 'Rol bulunamadi!');
        }

        $request->validate([
            'ad' => 'required|string|max:100',
            'aciklama' => 'nullable|string|max:255',
            'ikon' => 'nullable|string|max:10',
            'renk' => 'nullable|string|max:20',
        ]);

        $guncel = [
            'aciklama' => $request->aciklama,
            'ikon' => $request->ikon ?: 'ЁЯЫбя╕П',
            'renk' => $request->renk ?: 'primary',
            'tam_yetki' => $request->filled('tam_yetki') ? 1 : 0,
            'durum' => $request->filled('durum') ? 1 : 0,
            'updated_at' => now(),
        ];

        // Korumali rolun adi degistirilemez
        if (!$rol->korumali) {
            $guncel['ad'] = $request->ad;
        }

        DB::table('roller')->where('id', $id)->update($guncel);

        // Patron (korumali) icin yetki kaydi tutmuyoruz - her seye erisim.
        if (!$rol->korumali) {
            $this->yetkileriKaydet($id, $request);
        }

        return redirect()->route('admin.roller.index')->with('success', 'Rol guncellendi.');
    }

    public function sil($id)
    {
        if (!$this->patronMu()) {
            return redirect()->route('admin.dashboard')->with('error', 'Sadece patron rol yonetebilir!');
        }

        $rol = DB::table('roller')->where('id', $id)->first();
        if (!$rol) {
            return redirect()->route('admin.roller.index')->with('error', 'Rol bulunamadi!');
        }
        if ($rol->korumali) {
            return redirect()->route('admin.roller.index')->with('error', 'Korumali rol silinemez!');
        }

        // Bu role atanmis yonetici var mi?
        $kullanimSayisi = DB::table('yoneticiler')->where('rol', $id)->count();
        if ($kullanimSayisi > 0) {
            return redirect()->route('admin.roller.index')->with('error', 'Bu rol ' . $kullanimSayisi . ' yoneticiye atanmis, once yoneticilerden kaldirin.');
        }

        DB::table('rol_yetkileri')->where('rol_id', $id)->delete();
        DB::table('roller')->where('id', $id)->delete();

        return redirect()->route('admin.roller.index')->with('success', 'Rol silindi.');
    }

    private function yetkileriKaydet($rolId, Request $request)
    {
        $sayfalar = \App\Services\AdminSayfaKatalogu::liste();

        DB::table('rol_yetkileri')->where('rol_id', $rolId)->delete();

        $now = now();
        $rows = [];
        foreach ($sayfalar as $sayfa) {
            $checkboxName = 'yetki_' . str_replace('.', '__', $sayfa['route']);
            $gorebilir = $request->has($checkboxName) && $request->input($checkboxName) == '1' ? 1 : 0;

            $rows[] = [
                'rol_id' => $rolId,
                'sayfa_route' => $sayfa['route'],
                'sayfa_adi' => $sayfa['adi'],
                'gorebilir' => $gorebilir,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('rol_yetkileri')->insert($chunk);
            }
        }

        // Middleware cache'ini temizle
        Cache::forget("rol_{$rolId}");
        Cache::forget("rol_yetkileri_{$rolId}");
    }
}