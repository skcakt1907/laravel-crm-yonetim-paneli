<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Services\BayiKodService;

class BayiBasvuruController extends Controller
{
    public function form()
    {
        $uye = Auth::guard('uye')->user();

        // Zaten bayi mi kontrol et
        $mevcutBayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();

        if ($mevcutBayi && $mevcutBayi->durum == 1) {
            return redirect()->route('hesabim')->with('info', 'Zaten aktif bir bayilik hesabiniz bulunmaktadir.');
        }

        $beklemede = $mevcutBayi && $mevcutBayi->durum == 0;

        return view('tema.bayi-basvuru', compact('uye', 'beklemede'));
    }

    public function basvur(Request $request)
    {
        $uye = Auth::guard('uye')->user();

        // Zaten bayi mi
        $mevcutBayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
        if ($mevcutBayi) {
            if ($mevcutBayi->durum == 1) {
                return redirect()->route('hesabim')->with('info', 'Zaten aktif bir bayilik hesabiniz var.');
            }
            return redirect()->route('bayi.basvuru')->with('info', 'Basvurunuz zaten inceleniyor.');
        }

        // 🛡️ YARIM KALMIŞ KAYIT KORUMASI
        // Eski başarısız bir başvurudan yetim kalmış yonetici kaydı olabilir
        $mevcutYonetici = DB::table('yoneticiler')->where('kullaniciadi', 'bayi_' . $uye->id)->first();
        if ($mevcutYonetici) {
            DB::table('yoneticiler')->where('id', $mevcutYonetici->id)->delete();
            \Log::warning('Yarim kalmis bayi yonetici kaydi temizlendi', [
                'uye_id' => $uye->id,
                'silinen_yonetici_id' => $mevcutYonetici->id
            ]);
        }

        $request->validate([
            'firma_adi' => 'required|string|max:255',
            'telefon' => 'required|string|max:20',
            'adres' => 'nullable|string|max:500',
            'neden' => 'nullable|string|max:1000',
        ]);

        // Bayi kodu olustur
        $bayiKodu = strtoupper(substr(md5($uye->id . time()), 0, 8));

        // 🔒 TRANSACTION - ya hepsi ya hiçbiri
        try {
            DB::transaction(function () use ($uye, $request, $bayiKodu) {
                // Yonetici hesabi olustur
                $yoneticiId = DB::table('yoneticiler')->insertGetId([
                    'kullaniciadi' => 'bayi_' . $uye->id,
                    'email' => $uye->email,
                    'eposta' => $uye->email,
                    'sifre' => $uye->sifre,
                    'adi' => trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')),
                    'rol' => 3,
                    'yetki' => 1,
                    'durum' => 0, // Admin onayina kadar pasif
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Bayiler tablosuna ekle
                DB::table('bayiler')->insert([
                    'uye_id' => $uye->id,
                    'yonetici_id' => $yoneticiId,
                    'bayi_kodu' => $bayiKodu,
                    'firma_adi' => $request->firma_adi,
                    'telefon' => $request->telefon,
                    'adres' => $request->adres,
                    'durum' => 0, // Onay bekliyor
                    'komisyon_orani' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            \Log::error('Bayi basvuru hatasi', [
                'uye_id' => $uye->id,
                'err' => $e->getMessage()
            ]);
            return back()
                ->withInput()
                ->with('error', 'Basvuru sirasinda bir hata olustu. Lutfen tekrar deneyin veya destek ekibiyle iletisime gecin.');
        }

        return redirect()->route('bayi.basvuru')->with('success', 'Bayilik basvurunuz basariyla alindi! Yonetim ekibimiz en kisa surede inceleyecektir.');
    }
}