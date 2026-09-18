<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KuponlarController extends Controller
{
    public function index()
    {
        $kuponlar = DB::table('kuponlar')
            ->orderBy('id', 'desc')
            ->paginate(20);
            
        return view('admin.kuponlar.index', compact('kuponlar'));
    }
    
    public function ekle()
    {
        return view('admin.kuponlar.ekle');
    }
    
    public function eklePost(Request $request)
    {
        $request->validate([
            'kod' => 'required|string|max:50|unique:kuponlar,kod',
            'indirim' => 'required|numeric|min:0',
        ]);

        // tip: yuzde=1, tutar=2 (DB'de tur kolonu int)
        $tur = ($request->tip ?? 'yuzde') === 'tutar' ? 2 : 1;

        DB::table('kuponlar')->insert([
            'kod' => strtoupper($request->kod),
            'miktar' => $request->indirim,
            'tur' => $tur,
            'bas_tarih' => $request->baslangic_tarih,
            'bit_tarih' => $request->bitis_tarih,
        ]);

        return redirect()->route('admin.kuponlar.index')->with('success', 'Kupon başarıyla eklendi!');
    }

    public function duzenle($id)
    {
        $kuponDb = DB::table('kuponlar')->where('id', $id)->first();

        if (!$kuponDb) {
            return redirect()->route('admin.kuponlar.index')->with('error', 'Kupon bulunamadı!');
        }

        // View 'tip' ve 'indirim' bekliyor; DB'deki 'tur' ve 'miktar' kolonlarını eşle
        $kupon = (object) [
            'id' => $kuponDb->id,
            'kod' => $kuponDb->kod,
            'indirim' => $kuponDb->miktar,
            'tip' => ((int) $kuponDb->tur) === 2 ? 'tutar' : 'yuzde',
            'baslangic_tarih' => $kuponDb->bas_tarih,
            'bitis_tarih' => $kuponDb->bit_tarih,
            'kullanim_limiti' => null,
            'durum' => 1,
        ];

        return view('admin.kuponlar.duzenle', compact('kupon'));
    }

    public function duzenlePost(Request $request, $id)
    {
        $request->validate([
            'kod' => 'required|string|max:50|unique:kuponlar,kod,' . $id,
            'indirim' => 'required|numeric|min:0',
        ]);

        $tur = ($request->tip ?? 'yuzde') === 'tutar' ? 2 : 1;

        DB::table('kuponlar')->where('id', $id)->update([
            'kod' => strtoupper($request->kod),
            'miktar' => $request->indirim,
            'tur' => $tur,
            'bas_tarih' => $request->baslangic_tarih,
            'bit_tarih' => $request->bitis_tarih,
        ]);

        return redirect()->route('admin.kuponlar.index')->with('success', 'Kupon başarıyla güncellendi!');
    }
    
    public function sil($id)
    {
        DB::table('kuponlar')->where('id', $id)->delete();
        return redirect()->route('admin.kuponlar.index')->with('success', 'Kupon başarıyla silindi!');
    }
}






