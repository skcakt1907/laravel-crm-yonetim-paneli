<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SatisController extends Controller
{
    private function baseQuery()
    {
        return DB::table('satilanlar as s')
            ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
            ->select(
                's.*',
                DB::raw("TRIM(CONCAT_WS(' ', u.ad, u.soyad)) as ad"),
                'u.email as musteri_email',
                DB::raw("COALESCE(NULLIF(s.paket_baslik,''), NULLIF(s.hosting_baslik,''), s.domain, '—') as urun")
            );
    }

    public function hostingSatislar()
    {
        $satislar = $this->baseQuery()
            ->where('s.hosting', '>', 0)
            ->orderByDesc('s.id')
            ->paginate(20);

        return view('admin.satislar.hosting', compact('satislar'));
    }

    public function webPaketSatislar()
    {
        $satislar = $this->baseQuery()
            ->where('s.paket', '>', 0)
            ->orderByDesc('s.id')
            ->paginate(20);

        return view('admin.satislar.web-paket', compact('satislar'));
    }

    public function domainSatislar()
    {
        $satislar = $this->baseQuery()
            ->where(function ($query) {
                $query->whereNull('s.hosting')
                    ->orWhere('s.hosting', 0);
            })
            ->where(function ($query) {
                $query->whereNull('s.paket')
                    ->orWhere('s.paket', 0);
            })
            ->orderByDesc('s.id')
            ->paginate(20);

        return view('admin.satislar.domain', compact('satislar'));
    }
    
    /**
     * Satış detay sayfası - generic
     * Hosting / Web Paket / Domain için ortak kullanılır
     */
    public function detay($id)
    {
        $satis = DB::table('satilanlar as s')
            ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
            ->select(
                's.*',
                DB::raw("TRIM(CONCAT_WS(' ', u.ad, u.soyad)) as musteri_ad"),
                'u.email as musteri_email',
                'u.telefon as musteri_telefon',
                DB::raw("COALESCE(NULLIF(s.paket_baslik,''), NULLIF(s.hosting_baslik,''), s.domain, '—') as urun")
            )
            ->where('s.id', $id)
            ->first();

        if (!$satis) {
            return redirect()->back()->with('error', 'Satış kaydı bulunamadı.');
        }

        // Satış tipini belirle (hosting / web-paket / domain)
        if ((int)($satis->hosting ?? 0) > 0) {
            $tip = 'hosting';
        } elseif ((int)($satis->paket ?? 0) > 0) {
            $tip = 'web-paket';
        } else {
            $tip = 'domain';
        }

        return view('admin.satislar.detay', compact('satis', 'tip'));
    }
}


