<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BildirimMerkezi;
use Illuminate\Http\Request;

/**
 * BİLDİRİM MERKEZİ.
 *
 * Domain, sözleşme, fatura, alacak ve ödeme hatırlatmalarını tek ekranda toplar.
 * Hatırlatmaları GÖNDEREN komutlara dokunmaz — onlar kendi saatlerinde çalışmaya
 * devam eder; burası "ne zaman ne gidecek / ne gitti" sorusunun tek cevabı.
 */
class BildirimMerkeziController extends Controller
{
    public function index(Request $request)
    {
        $gun = (int) $request->query('gun', 30);
        if ($gun < 1 || $gun > 365) $gun = 30;

        $tur = $request->query('tur');
        if (!array_key_exists((string) $tur, BildirimMerkezi::TURLER)) $tur = null;

        $yaklasanlar = BildirimMerkezi::yaklasanlar($gun, $tur);

        // Sadece gecikmişleri göster filtresi
        if ($request->query('sadece') === 'gecikmis') {
            $yaklasanlar = $yaklasanlar->filter(fn ($x) => $x['gun'] !== null && $x['gun'] < 0)->values();
        }

        return view('admin.bildirim-merkezi.index', [
            'yaklasanlar'  => $yaklasanlar,
            'ozet'         => BildirimMerkezi::ozet($yaklasanlar),
            'gonderilenler'=> BildirimMerkezi::gonderilenler(60, $tur),
            'zamanlama'    => BildirimMerkezi::zamanlama(),
            'turler'       => BildirimMerkezi::TURLER,
            'gun'          => $gun,
            'tur'          => $tur,
            'sadece'       => $request->query('sadece'),
        ]);
    }
}
