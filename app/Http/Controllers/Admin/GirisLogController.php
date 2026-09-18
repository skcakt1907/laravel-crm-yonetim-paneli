<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GirisLogController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('giris_loglari')) {
            return view('admin.giris-loglari.index', [
                'loglar' => collect(),
                'tableMissing' => true,
                'istatistik' => ['toplam' => 0, 'basarili' => 0, 'basarisiz' => 0, 'bloke' => 0, 'tekil_ip' => 0],
                'filtre' => 'tumu',
                'tip' => 'tumu',
            ]);
        }

        $filtre = $request->get('durum', 'tumu');   // tumu | basarili | basarisiz | bloke
        $tip    = $request->get('tip', 'tumu');      // tumu | yonetici | uye

        $query = DB::table('giris_loglari');
        if (in_array($filtre, ['basarili', 'basarisiz', 'bloke'])) {
            $query->where('durum', $filtre);
        }
        if (in_array($tip, ['yonetici', 'uye'])) {
            $query->where('kullanici_tipi', $tip);
        }

        $loglar = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(50)->withQueryString();

        // İstatistikler (filtreden bağımsız genel)
        $istatistik = [
            'toplam'    => DB::table('giris_loglari')->count(),
            'basarili'  => DB::table('giris_loglari')->where('durum', 'basarili')->count(),
            'basarisiz' => DB::table('giris_loglari')->where('durum', 'basarisiz')->count(),
            'bloke'     => DB::table('giris_loglari')->where('durum', 'bloke')->count(),
            'tekil_ip'  => DB::table('giris_loglari')->distinct('ip')->count('ip'),
        ];

        return view('admin.giris-loglari.index', [
            'loglar' => $loglar,
            'tableMissing' => false,
            'istatistik' => $istatistik,
            'filtre' => $filtre,
            'tip' => $tip,
        ]);
    }
}