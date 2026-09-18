<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MusteriRaporController extends Controller
{
    /**
     * Tek bir raporun detay sayfası.
     * Güvenlik: müşteri sadece kendi raporunu görür.
     */
    public function detay($id)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) {
            return redirect()->route('uye.giris')->with('error', 'Giriş yapmalısınız.');
        }

        $rapor = DB::table('musteri_raporlar')->where('id', $id)->first();
        if (!$rapor) {
            return redirect()->route('raporlarim')->with('error', 'Rapor bulunamadı.');
        }

        // Güvenlik: sadece kendi raporu
        if ((int) $rapor->uyeid !== (int) $uye->id) {
            return redirect()->route('raporlarim')->with('error', 'Bu rapora erişim yetkiniz yok.');
        }

        $ayarlar = DB::table('ayarlar')->first();

        return view('tema.rapor-detay', compact('rapor', 'ayarlar'));
    }

    /**
     * Güvenli dosya indirme — yetkilendirilmiş kullanıcıya direkt dosya gönder.
     * (Müşteri rapor dosyasını "indir" derken bu metoda gelir; sadece sahibine indirir.)
     */
    public function indir($id)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) {
            return redirect()->route('uye.giris')->with('error', 'Giriş yapmalısınız.');
        }

        $rapor = DB::table('musteri_raporlar')->where('id', $id)->first();
        if (!$rapor) {
            abort(404, 'Rapor bulunamadı.');
        }

        if ((int) $rapor->uyeid !== (int) $uye->id) {
            abort(403, 'Bu rapora erişim yetkiniz yok.');
        }

        if (empty($rapor->dosya)) {
            return redirect()->route('rapor.detay', $id)
                ->with('error', 'Bu rapora ekli bir dosya yok.');
        }

        $path = public_path($rapor->dosya);
        if (!file_exists($path)) {
            // Dosya silinmiş veya hatalı yol — Log'a yaz
            \Log::warning('Rapor dosyası bulunamadı', [
                'rapor_id' => $id,
                'kaydedilmis_yol' => $rapor->dosya,
                'aranan_path' => $path,
            ]);
            return redirect()->route('rapor.detay', $id)
                ->with('error', 'Dosya sunucuda bulunamadı. Lütfen yönetici ile iletişime geçin.');
        }

        $fileName = basename($rapor->dosya);
        return response()->download($path, $fileName);
    }
}