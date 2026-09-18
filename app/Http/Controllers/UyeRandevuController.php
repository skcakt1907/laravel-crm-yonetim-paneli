<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Üye paneli — Randevularım.
 * Üye (uyeler.id) ile CRM müşterisi (crm_customers.id) aynı ID'yi paylaşır;
 * randevular.crm_musteri_id bu ID ile eşleşir.
 */
class UyeRandevuController extends Controller
{
    public function index()
    {
        $uyeId = Auth::guard('uye')->id();
        $uye   = Auth::guard('uye')->user();

        $gelecek = collect();
        $gecmis  = collect();

        // Üyeye karşılık gelen CRM müşteri ID'leri: aynı ID + uye_id kolonu + e-posta eşleşmesi
        $crmIds = [$uyeId];
        if ($uyeId && Schema::hasTable('crm_customers')) {
            try {
                if (Schema::hasColumn('crm_customers', 'uye_id')) {
                    $crmIds = array_merge($crmIds, DB::table('crm_customers')->where('uye_id', $uyeId)->pluck('id')->all());
                }
                if (!empty($uye->email)) {
                    $crmIds = array_merge($crmIds, DB::table('crm_customers')->where('email', $uye->email)->pluck('id')->all());
                }
            } catch (\Throwable $e) {}
        }
        $crmIds = array_values(array_unique(array_filter($crmIds)));

        if ($uyeId && Schema::hasTable('randevular')) {
            try {
                $hepsi = DB::table('randevular as r')
                    ->leftJoin('randevu_calisanlar as c', 'c.id', '=', 'r.calisan_id')
                    ->leftJoin('randevu_hizmetler as h', 'h.id', '=', 'r.hizmet_id')
                    ->whereIn('r.crm_musteri_id', $crmIds)
                    ->where('r.durum', '!=', 'iptal')
                    ->select('r.*', 'c.ad as calisan_ad', 'h.ad as lokasyon_ad')
                    ->orderBy('r.baslangic')
                    ->limit(200)
                    ->get();

                $simdi = Carbon::now();
                [$gelecek, $gecmis] = $hepsi->partition(function ($r) use ($simdi) {
                    return Carbon::parse($r->baslangic)->gte($simdi);
                });
                $gecmis = $gecmis->sortByDesc('baslangic')->values();
            } catch (\Throwable $e) {
                // tablo/sütun eksikse sayfa yine de açılsın
            }
        }

        return view('tema.randevularim', [
            'gelecek' => $gelecek,
            'gecmis'  => $gecmis,
        ]);
    }
}