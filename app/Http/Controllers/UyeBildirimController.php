<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UyeBildirim;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UyeBildirimController extends Controller
{
    public function index()
    {
        $uyeId = Auth::guard('uye')->id();

        $bildirimler = UyeBildirim::forUye($uyeId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        UyeBildirim::where('uye_id', $uyeId)
            ->where('okundu', false)
            ->update(['okundu' => true, 'okundu_tarih' => now()]);

        return view('tema.bildirimlerim', compact('bildirimler'));
    }

    public function okundu($id)
    {
        $uyeId = Auth::guard('uye')->id();

        UyeBildirim::forUye($uyeId)
            ->where('id', $id)
            ->update(['okundu' => true, 'okundu_tarih' => now()]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Bildirim okundu olarak işaretlendi.');
    }

    public function hepsiniOku()
    {
        $uyeId = Auth::guard('uye')->id();

        UyeBildirim::where('uye_id', $uyeId)
            ->where('okundu', false)
            ->update(['okundu' => true, 'okundu_tarih' => now()]);

        return back()->with('success', 'Tüm bildirimler okundu olarak işaretlendi.');
    }

    public function sil($id)
    {
        $uyeId = Auth::guard('uye')->id();

        UyeBildirim::where('uye_id', $uyeId)->where('id', $id)->delete();

        return back()->with('success', 'Bildirim silindi.');
    }

    public function hepsiniSil()
    {
        $uyeId = Auth::guard('uye')->id();

        UyeBildirim::where('uye_id', $uyeId)->delete();

        return back()->with('success', 'Kişisel bildirimleriniz silindi.');
    }

    /**
     * POLLING ENDPOINT (JSON) — müşteri zil ikonu için.
     * Okunmamış sayısı + son 8 bildirimi döner (link dahil).
     * GET /bildirimlerim/sayim
     */
    public function sayim()
    {
        $uyeId = Auth::guard('uye')->id();
        if (!$uyeId) {
            return response()->json(['okunmamis' => 0, 'bildirimler' => []]);
        }

        $okunmamis = DB::table('uye_bildirimler')
            ->where('uye_id', $uyeId)
            ->where('okundu', 0)
            ->count();

        $sonlar = DB::table('uye_bildirimler')
            ->where('uye_id', $uyeId)
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'tip', 'baslik', 'mesaj', 'link', 'okundu', 'created_at']);

        $bildirimler = $sonlar->map(function ($b) {
            $zaman = null;
            if (!empty($b->created_at)) {
                try { $zaman = \Carbon\Carbon::parse($b->created_at)->diffForHumans(); } catch (\Throwable $e) {}
            }
            return [
                'id'     => $b->id,
                'tip'    => $b->tip,
                'baslik' => $b->baslik,
                'mesaj'  => $b->mesaj,
                'okundu' => (int) $b->okundu,
                'zaman'  => $zaman,
                'link'   => $b->link ?: null,
            ];
        });

        return response()->json([
            'okunmamis'   => $okunmamis,
            'bildirimler' => $bildirimler,
        ]);
    }
}