<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Randevu değerlendirmeleri — admin listesi.
 * Müşterilere giden tokenli değerlendirme davetlerinin sonuçlarını gösterir.
 */
class RandevuDegerlendirmeController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('randevu_degerlendirmeler')) {
            return redirect()->route('admin.randevu.randevular')
                ->with('error', 'Değerlendirme tablosu bulunamadı.');
        }

        $sorgu = DB::table('randevu_degerlendirmeler as d')
            ->leftJoin('randevular as r', 'r.id', '=', 'd.randevu_id')
            ->leftJoin('randevu_calisanlar as c', 'c.id', '=', 'd.calisan_id')
            ->leftJoin('randevu_hizmetler as h', 'h.id', '=', 'r.hizmet_id')
            ->select('d.*', 'r.baslangic', 'c.ad as calisan_ad', 'h.ad as lokasyon_ad');

        // Filtreler
        $puan = $request->input('puan');
        if ($puan !== null && $puan !== '' && in_array((int) $puan, [1, 2, 3, 4, 5], true)) {
            $sorgu->where('d.puan', (int) $puan);
        }

        $durum = $request->input('durum');
        if ($durum === 'dolduruldu') {
            $sorgu->whereNotNull('d.dolduruldu_at');
        } elseif ($durum === 'bekliyor') {
            $sorgu->whereNull('d.dolduruldu_at');
        }

        $liste = $sorgu->orderByDesc('d.created_at')->paginate(30)->withQueryString();

        // Özet istatistikler (filtreden bağımsız, genel resim)
        $ozet = [
            'toplam'    => 0,
            'doldurulan' => 0,
            'ortalama'  => null,
            'bes'       => 0,
        ];
        try {
            $ozet['toplam']     = (int) DB::table('randevu_degerlendirmeler')->count();
            $ozet['doldurulan'] = (int) DB::table('randevu_degerlendirmeler')->whereNotNull('dolduruldu_at')->count();
            $ort = DB::table('randevu_degerlendirmeler')->whereNotNull('puan')->avg('puan');
            $ozet['ortalama']   = $ort !== null ? round((float) $ort, 1) : null;
            $ozet['bes']        = (int) DB::table('randevu_degerlendirmeler')->where('puan', 5)->count();
        } catch (\Throwable $e) {
            // özet hesaplanamazsa sayfa yine açılsın
        }

        return view('admin.randevu.degerlendirmeler', [
            'liste'      => $liste,
            'ozet'       => $ozet,
            'secimPuan'  => $puan,
            'secimDurum' => $durum,
        ]);
    }
}