<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\IslemGecmisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * İşlem Geçmişi ekranı (Faz 0).
 * Panelde yapılan yazma işlemlerini listeler; her satırda "Geri Al" düğmesi.
 */
class IslemGecmisiController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('islem_gecmisi');

        // Basit filtre: kaynak (manuel/ai) + arama
        if (in_array($request->get('kaynak'), ['manuel', 'ai'], true)) {
            $q->where('kaynak', $request->get('kaynak'));
        }
        if ($ara = trim((string) $request->get('ara'))) {
            $q->where('aciklama', 'like', '%' . $ara . '%');
        }

        $kayitlar = $q->orderBy('id', 'desc')->paginate(30)->withQueryString();

        return view('admin.islem-gecmisi.index', compact('kayitlar'));
    }

    public function geriAl(Request $request, $id)
    {
        $sonuc = IslemGecmisi::geriAl((int) $id);

        return back()->with($sonuc['ok'] ? 'success' : 'error', $sonuc['mesaj']);
    }
}
