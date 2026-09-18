<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TablolarKilitController extends Controller
{
    /**
     * PIN giriş ekranı (yetkili ama PIN girmemiş kullanıcılara)
     */
    public function pinEkrani()
    {
        // Eğer zaten PIN tamamsa direkt yönlendir
        if (session('tablolar_pin_ok')) {
            return redirect()->route('admin.tablolar.index');
        }
        
        $adminId = session('admin_id');
        $adminRol = session('admin_rol');
        
        // Yetki kontrolü
        $ayar = DB::table('tablolar_kilit_ayar')->orderByDesc('id')->first();
        if (!$ayar) {
            return redirect()->route('admin.tablolar.kilit.kurulum');
        }
        
        $yetkililer = json_decode($ayar->yetkili_ids, true) ?: [];
        if (!$this->tablolaraErisebilirMi($adminId, $adminRol, $ayar)) {
            abort(403, '🔒 Yetkin yok.');
        }
        
        return view('admin.tablolar.kilit.pin');
    }
    
    /**
     * PIN'i doğrula
     */
    public function pinDogrula(Request $request)
    {
        $request->validate(['pin' => 'required|string|min:4|max:20']);
        
        $adminId = session('admin_id');
        $adminRol = session('admin_rol');
        
        $ayar = DB::table('tablolar_kilit_ayar')->orderByDesc('id')->first();
        if (!$ayar) {
            return redirect()->back()->with('error', 'Sistem hatası, ayar yok.');
        }
        
        // Yetki tekrar kontrol et
        $yetkililer = json_decode($ayar->yetkili_ids, true) ?: [];
        if (!$this->tablolaraErisebilirMi($adminId, $adminRol, $ayar)) {
            abort(403);
        }
        
        // PIN doğrula
        if (!Hash::check($request->pin, $ayar->pin_hash)) {
            return redirect()->back()->with('error', '❌ Yanlış PIN. Tekrar dene.');
        }
        
        // Session'a kaydet (4 saat geçerli)
        session([
            'tablolar_pin_ok' => true,
            'tablolar_pin_time' => time(),
        ]);
        
        // Hangi sayfaya gitmek istiyorduysa oraya
        $redirectUrl = session('tablolar_redirect_after_pin', route('admin.tablolar.index'));
        session()->forget('tablolar_redirect_after_pin');
        
        return redirect($redirectUrl)->with('success', '✅ PIN doğrulandı, hoş geldin!');
    }
    
    /**
     * Çıkış (PIN'i unut)
     */
    public function cikis()
    {
        session()->forget(['tablolar_pin_ok', 'tablolar_pin_time']);
        return redirect()->route('admin.dashboard')
            ->with('success', '🔒 Tablolar modülünden çıkıldı.');
    }
    
    /**
     * İlk kurulum ekranı (ayar tablosu boşsa)
     */
    public function kurulum()
    {
        if (session('admin_rol') != 1) {
            abort(403, 'Sadece Patron kurulum yapabilir.');
        }
        
        $ayar = DB::table('tablolar_kilit_ayar')->orderByDesc('id')->first();
        if ($ayar) {
            return redirect()->route('admin.tablolar.kilit.ayarlar');
        }
        
        $adminler = DB::table('yoneticiler')
            ->select('id', 'adi', 'kullaniciadi', 'email')
            ->where('durum', 1)
            ->orderBy('adi')
            ->get();
        
        return view('admin.tablolar.kilit.kurulum', compact('adminler'));
    }
    
    /**
     * İlk kurulumu kaydet
     */
    public function kurulumKaydet(Request $request)
    {
        if (session('admin_rol') != 1) {
            abort(403);
        }
        
        $request->validate([
            'pin' => 'required|string|min:4|max:20',
            'pin_tekrar' => 'required|same:pin',
            'yetkili_ids' => 'required|array|min:1',
            'yetkili_ids.*' => 'integer|exists:yoneticiler,id',
        ]);
        
        // Patron kendini her zaman yetkili olarak ekler
        $yetkililer = array_unique(array_merge(
            $request->yetkili_ids,
            [session('admin_id')]
        ));
        
        DB::table('tablolar_kilit_ayar')->insert([
            'pin_hash' => Hash::make($request->pin),
            'yetkili_ids' => json_encode(array_values($yetkililer)),
            'son_degisiklik_admin_id' => session('admin_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('admin.tablolar.kilit.pin')
            ->with('success', '✅ Kurulum tamamlandı. PIN ile giriş yap.');
    }
    
    /**
     * Ayarlar ekranı (PIN değiştir, yetkili adminleri güncelle)
     */
    public function ayarlar()
    {
        if (session('admin_rol') != 1) {
            abort(403, 'Sadece Patron ayarları değiştirebilir.');
        }
        
        $ayar = DB::table('tablolar_kilit_ayar')->orderByDesc('id')->first();
        if (!$ayar) {
            return redirect()->route('admin.tablolar.kilit.kurulum');
        }
        
        $adminler = DB::table('yoneticiler')
            ->select('id', 'adi', 'kullaniciadi', 'email')
            ->where('durum', 1)
            ->orderBy('adi')
            ->get();
        
        $yetkililer = json_decode($ayar->yetkili_ids, true) ?: [];
        
        return view('admin.tablolar.kilit.ayarlar', compact('adminler', 'yetkililer'));
    }
    
    /**
     * Ayarları kaydet
     */
    public function ayarlarKaydet(Request $request)
    {
        if (session('admin_rol') != 1) {
            abort(403);
        }
        
        $request->validate([
            'yetkili_ids' => 'required|array|min:1',
            'yetkili_ids.*' => 'integer|exists:yoneticiler,id',
            'pin' => 'nullable|string|min:4|max:20',
            'pin_tekrar' => 'nullable|same:pin',
        ]);
        
        $ayar = DB::table('tablolar_kilit_ayar')->orderByDesc('id')->first();
        
        // Patron kendini her zaman yetkili olarak ekler
        $yetkililer = array_unique(array_merge(
            $request->yetkili_ids,
            [session('admin_id')]
        ));
        
        $update = [
            'yetkili_ids' => json_encode(array_values($yetkililer)),
            'son_degisiklik_admin_id' => session('admin_id'),
            'updated_at' => now(),
        ];
        
        // PIN değiştirilmek istendi mi?
        if (!empty($request->pin)) {
            $update['pin_hash'] = Hash::make($request->pin);
        }
        
        DB::table('tablolar_kilit_ayar')
            ->where('id', $ayar->id)
            ->update($update);
        
        $msg = 'Ayarlar güncellendi.';
        if (!empty($request->pin)) {
            $msg .= ' PIN değiştirildi, herkes yeniden giriş yapmalı.';
            // PIN değiştiyse herkesin session'ından sil (sadece kendi session'ı için)
            session()->forget(['tablolar_pin_ok', 'tablolar_pin_time']);
        }
        
        return redirect()->back()->with('success', '✅ ' . $msg);
    }

    /**
     * Kullanici Tablolar'a erisebilir mi? (PIN ekrani/dogrulama icin)
     * Gecerli yollar: izinli listede VEYA patron VEYA rolunde Tablolar yetkisi
     * (panelden isaretlenmis) VEYA tam_yetki=1. Middleware ile ayni mantik.
     */
    private function tablolaraErisebilirMi($adminId, $adminRol, $ayar): bool
    {
        $yetkililer = json_decode($ayar->yetkili_ids, true) ?: [];
        if (in_array($adminId, $yetkililer) || $adminRol == 1) {
            return true;
        }
        if (!$adminRol) {
            return false;
        }
        $rol = DB::table('roller')->where('id', (int) $adminRol)->first();
        if (!$rol) {
            return false;
        }
        if (!empty($rol->tam_yetki)) {
            return true;
        }
        return DB::table('rol_yetkileri')
            ->where('rol_id', $rol->id)
            ->where('gorebilir', 1)
            ->where('sayfa_route', 'like', 'admin.tablolar.%')
            ->exists();
    }
}