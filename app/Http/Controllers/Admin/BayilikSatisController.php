<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BayilikSatisController extends Controller
{
    public function index()
    {
        $satislar = DB::table('satilanlar')
            ->leftJoin('uyeler', 'satilanlar.uyeid', '=', 'uyeler.id')
            ->leftJoin('bayiler', 'uyeler.id', '=', 'bayiler.uye_id')
            ->select(
                'satilanlar.*',
                'uyeler.ad',
                'uyeler.soyad',
                'uyeler.email',
                DB::raw("COALESCE(bayiler.bayi_kodu, CONCAT_WS(' ', uyeler.ad, uyeler.soyad), '—') as bayi_adi")
            )
            ->orderBy('satilanlar.id', 'desc')
            ->paginate(20);
        
        return view('admin.bayilik-satislar.index', compact('satislar'));
    }
}

