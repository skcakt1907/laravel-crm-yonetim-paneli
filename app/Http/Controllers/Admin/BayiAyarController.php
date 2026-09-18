<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BayiAyarController extends Controller
{
    /**
     * Güvenlik Ayarları
     */
    public function guvenlik()
    {
        $bayiId = session('admin_id');
        
        $yonetici = DB::table('yoneticiler')->where('id', $bayiId)->first();
        
        // 2FA durumu
        $ikiFactorAktif = $yonetici->iki_factor_aktif ?? 0;
        
        // Oturum geçmişi
        $oturumlar = DB::table('bayi_oturum_gecmisi')
            ->where('bayi_id', $bayiId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('admin.bayi.ayarlar.guvenlik', compact('yonetici', 'ikiFactorAktif', 'oturumlar'));
    }
    
    /**
     * Şifre Değiştir
     */
    public function sifreDegistir(Request $request)
    {
        $request->validate([
            'mevcut_sifre' => 'required',
            'yeni_sifre' => 'required|min:6|confirmed',
        ]);
        
        $bayiId = session('admin_id');
        $yonetici = DB::table('yoneticiler')->where('id', $bayiId)->first();
        
        // Mevcut şifre kontrolü
        if (!Hash::check($request->mevcut_sifre, $yonetici->sifre)) {
            return back()->with('error', 'Mevcut şifreniz yanlış!');
        }
        
        // Yeni şifreyi güncelle
        DB::table('yoneticiler')
            ->where('id', $bayiId)
            ->update([
                'sifre' => Hash::make($request->yeni_sifre),
                'updated_at' => now(),
            ]);
        
        return back()->with('success', 'Şifreniz başarıyla değiştirildi!');
    }
    
    /**
     * 2FA Aktif/Pasif
     */
    public function ikiFactorAktif(Request $request)
    {
        $bayiId = session('admin_id');
        
        $durum = $request->durum ? 1 : 0;
        
        DB::table('yoneticiler')
            ->where('id', $bayiId)
            ->update([
                'iki_factor_aktif' => $durum,
                'updated_at' => now(),
            ]);
        
        $mesaj = $durum ? 'İki faktörlü doğrulama aktif edildi!' : 'İki faktörlü doğrulama kapatıldı!';
        
        return back()->with('success', $mesaj);
    }
    
    /**
     * Bildirim Ayarları
     */
    public function bildirimAyarlari()
    {
        $bayiId = session('admin_id');
        
        // Bildirim ayarları
        $ayarlar = DB::table('bayi_bildirim_ayarlari')
            ->where('bayi_id', $bayiId)
            ->first();
        
        if (!$ayarlar) {
            // Varsayılan ayarlar oluştur
            DB::table('bayi_bildirim_ayarlari')->insert([
                'bayi_id' => $bayiId,
                'yeni_satis_email' => 1,
                'odeme_onay_email' => 1,
                'sistem_duyuru_email' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $ayarlar = DB::table('bayi_bildirim_ayarlari')
                ->where('bayi_id', $bayiId)
                ->first();
        }
        
        return view('admin.bayi.ayarlar.bildirim-ayarlari', compact('ayarlar'));
    }
    
    /**
     * Bildirim Ayarlarını Güncelle
     */
    public function bildirimAyarlariGuncelle(Request $request)
    {
        $bayiId = session('admin_id');
        
        DB::table('bayi_bildirim_ayarlari')
            ->where('bayi_id', $bayiId)
            ->update([
                'yeni_satis_email' => $request->has('yeni_satis_email') ? 1 : 0,
                'odeme_onay_email' => $request->has('odeme_onay_email') ? 1 : 0,
                'sistem_duyuru_email' => $request->has('sistem_duyuru_email') ? 1 : 0,
                'updated_at' => now(),
            ]);
        
        return back()->with('success', 'Bildirim ayarları güncellendi!');
    }
}

