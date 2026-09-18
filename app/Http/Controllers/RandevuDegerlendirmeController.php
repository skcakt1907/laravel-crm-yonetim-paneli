<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Randevu değerlendirme (müşteriye SMS/mail ile giden tokenli link — giriş gerektirmez).
 */
class RandevuDegerlendirmeController extends Controller
{
    private function kayit(string $token)
    {
        return DB::table('randevu_degerlendirmeler as d')
            ->leftJoin('randevular as r', 'r.id', '=', 'd.randevu_id')
            ->leftJoin('randevu_calisanlar as c', 'c.id', '=', 'd.calisan_id')
            ->leftJoin('randevu_hizmetler as h', 'h.id', '=', 'r.hizmet_id')
            ->where('d.token', $token)
            ->select('d.*', 'r.baslangic', 'c.ad as calisan_ad', 'h.ad as lokasyon_ad')
            ->first();
    }

    public function goster(string $token)
    {
        $d = $this->kayit($token);
        abort_unless($d, 404);

        return view('tema.randevu-degerlendirme', [
            'd'          => $d,
            'token'      => $token,
            'dolduruldu' => !empty($d->dolduruldu_at),
        ]);
    }

    public function kaydet(Request $request, string $token)
    {
        $d = $this->kayit($token);
        abort_unless($d, 404);

        if (!empty($d->dolduruldu_at)) {
            return redirect()->route('randevu.degerlendirme', $token);
        }

        $data = $request->validate([
            'puan'  => 'required|integer|min:1|max:5',
            'yorum' => 'nullable|string|max:2000',
        ]);

        DB::table('randevu_degerlendirmeler')->where('id', $d->id)->update([
            'puan'          => $data['puan'],
            'yorum'         => $data['yorum'] ?? null,
            'dolduruldu_at' => Carbon::now(),
        ]);

        return redirect()->route('randevu.degerlendirme', $token)->with('tesekkur', 1);
    }
}