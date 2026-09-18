<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class YorumController extends Controller
{
    public function index()
    {
        // NOT: eskiden uyeler'e INNER JOIN vardi -> uyesi silinmis veya misafir
        // yorumlar admin'de HIC gorunmuyordu (dolayisiyla onaylanamiyordu). leftJoin.
        $q = DB::table('yorumlar')
            ->leftJoin('uyeler', 'yorumlar.uyeid', '=', 'uyeler.id')
            ->select('yorumlar.*', 'uyeler.ad', 'uyeler.soyad');

        // Yorumun hangi pakete ait oldugunu goster ("Hedef" sutunu bostu)
        if (Schema::hasTable('yazilimlar')) {
            $q->leftJoin('yazilimlar', function ($j) {
                $j->on('yazilimlar.id', '=', 'yorumlar.icerik_id');
                if (Schema::hasColumn('yorumlar', 'tip')) {
                    $j->where('yorumlar.tip', '=', 'paket');
                }
            })->addSelect('yazilimlar.adi as hedef');
        }

        $yorumlar = $q->orderBy('yorumlar.id', 'desc')->paginate(20);

        return view('admin.yorumlar.index', compact('yorumlar'));
    }

    /**
     * Yorum onayla / reddet / durum değiştir
     */
    public function durumDegistir($id, $durum)
    {
        // String → integer dönüştürme
        $durumMap = ['onayli' => 1, 'aktif' => 1, 'beklemede' => 0, 'reddedildi' => 2, 'iptal' => 2];
        $durumInt = $durumMap[$durum] ?? (is_numeric($durum) ? (int) $durum : 0);

        DB::table('yorumlar')->where('id', $id)->update([
            'durum' => $durumInt,
        ]);

        return redirect()->back()->with('success', 'Yorum durumu güncellendi.');
    }

    /**
     * Yorum sil
     */
    public function sil($id)
    {
        DB::table('yorumlar')->where('id', $id)->delete();
        return redirect()->route('admin.yorumlar.index')->with('success', 'Yorum silindi.');
    }
}
