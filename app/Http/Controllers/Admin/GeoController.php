<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Türkiye il / ilçe / mahalle referans verisi — kademeli dropdown'lar için JSON uçları.
 */
class GeoController extends Controller
{
    public function iller()
    {
        $iller = DB::table('tr_iller')->orderBy('ad')->get(['id', 'ad']);
        return response()->json($iller);
    }

    public function ilceler(Request $request)
    {
        $ilId = (int) $request->query('il_id');
        if (!$ilId) return response()->json([]);

        $ilceler = DB::table('tr_ilceler')->where('il_id', $ilId)
            ->orderBy('ad')->get(['id', 'ad']);
        return response()->json($ilceler);
    }

    public function mahalleler(Request $request)
    {
        $ilceId = (int) $request->query('ilce_id');
        if (!$ilceId) return response()->json([]);

        $mahalleler = DB::table('tr_mahalleler')->where('ilce_id', $ilceId)
            ->orderBy('ad')->get(['id', 'ad']);
        return response()->json($mahalleler);
    }
}
