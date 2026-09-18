<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Paket değerlendirme (puan + yorum) — ön yüz.
 *
 * Kurallar:
 *  - Sadece üyeler değerlendirir (admin ekranı uyeler ile join'lediği için,
 *    ayrıca gerçek müşteri yorumu güven verir).
 *  - Bir üye bir paketi bir kez değerlendirir.
 *  - Yorum durum=0 (onay bekler) olarak düşer; admin /yonetim/yorumlar'dan onaylar.
 */
class YorumController extends Controller
{
    public function ekle(Request $request)
    {
        $uye = Auth::guard('uye')->user();
        if (!$uye) {
            return back()->with('yorum_hata', __('messages.login_to_review'))->withInput();
        }

        $data = $request->validate([
            'icerik_id' => 'required|integer|exists:yazilimlar,id',
            'puan'      => 'required|integer|min:1|max:5',
            'yorum'     => 'required|string|min:10|max:1000',
        ], [
            'puan.required'  => __('messages.rating_required'),
            'yorum.required' => __('messages.review_required'),
            'yorum.min'      => __('messages.review_min_length'),
        ]);

        // Aynı üye aynı paketi bir kez değerlendirsin
        $zatenVar = DB::table('yorumlar')
            ->where('tip', 'paket')
            ->where('icerik_id', $data['icerik_id'])
            ->where('uyeid', $uye->id)
            ->exists();

        if ($zatenVar) {
            return back()->with('yorum_hata', __('messages.already_reviewed_error'))
                         ->withFragment('degerlendirmeler');
        }

        DB::table('yorumlar')->insert([
            'icerik_id' => $data['icerik_id'],
            'tip'       => 'paket',
            'ustid'     => 0,
            'uyeid'     => $uye->id,
            'adi'       => trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: 'Üye',
            'email'     => $uye->email ?? null,
            'yorum'     => $data['yorum'],
            'puan'      => $data['puan'],
            'durum'     => 0,          // admin onayı bekler
            'ip'        => $request->ip(),
            'tarih'     => now(),
        ]);

        return back()
            ->with('yorum_ok', __('messages.review_received'))
            ->withFragment('degerlendirmeler');
    }
}
