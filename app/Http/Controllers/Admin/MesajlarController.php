<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MesajlarController extends Controller
{
    public function index()
    {
        $mesajlar = DB::table('mesajlar')
            ->orderBy('id', 'desc')
            ->paginate(20);
            
        return view('admin.mesajlar.index', compact('mesajlar'));
    }
    
    public function detay($id)
    {
        $mesaj = DB::table('mesajlar')->where('id', $id)->first();
        
        if (!$mesaj) {
            return redirect()->route('admin.mesajlar.index')->with('error', 'Mesaj bulunamadı!');
        }
        
        // Mesajı okundu olarak işaretle
        DB::table('mesajlar')->where('id', $id)->update(['durum' => 1]);
        
        return view('admin.mesajlar.detay', compact('mesaj'));
    }
    
    public function sil($id)
    {
        DB::table('mesajlar')->where('id', $id)->delete();
        return redirect()->route('admin.mesajlar.index')->with('success', 'Mesaj başarıyla silindi!');
    }
    
    public function durumDegistir($id)
    {
        $mesaj = DB::table('mesajlar')->where('id', $id)->first();
        $yeniDurum = $mesaj->durum == 1 ? 0 : 1;
        
        DB::table('mesajlar')->where('id', $id)->update(['durum' => $yeniDurum]);
        
        return redirect()->back()->with('success', 'Durum güncellendi!');
    }
}






