<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankaController extends Controller
{
    public function index()
    {
        $bankalar = DB::table('banka_hesaplari')
            ->orderBy('id', 'desc')
            ->paginate(20);
            
        return view('admin.banka.index', compact('bankalar'));
    }
    
    public function ekle()
    {
        return view('admin.banka.ekle');
    }
    
    public function eklePost(Request $request)
    {
        $request->validate([
            'banka' => 'required_without:banka_adi|string|max:255',
            'banka_adi' => 'required_without:banka|string|max:255',
            'hesap' => 'required_without:hesap_sahibi|string|max:255',
            'hesap_sahibi' => 'required_without:hesap|string|max:255',
        ]);
        
        DB::table('banka_hesaplari')->insert([
            'banka' => $request->banka ?? $request->banka_adi,
            'hesap' => $request->hesap ?? $request->hesap_sahibi,
            'iban' => $request->iban ?? '',
            'hnumara' => $request->hnumara ?? $request->hesap_no ?? '',
            'sube' => $request->sube ?? $request->sube_kodu ?? $request->sube_adi ?? '',
            'durum' => $request->durum ?? 1,
            'tarih' => date('Y-m-d H:i:s'),
        ]);
        
        return redirect()->route('admin.banka.index')->with('success', 'Banka hesabı başarıyla eklendi!');
    }
    
    public function duzenle($id)
    {
        $banka = DB::table('banka_hesaplari')->where('id', $id)->first();
        
        if (!$banka) {
            return redirect()->route('admin.banka.index')->with('error', 'Banka hesabı bulunamadı!');
        }
        
        return view('admin.banka.duzenle', compact('banka'));
    }
    
    public function duzenlePost(Request $request, $id)
    {
        $request->validate([
            'banka' => 'required_without:banka_adi|string|max:255',
            'banka_adi' => 'required_without:banka|string|max:255',
            'hesap' => 'required_without:hesap_sahibi|string|max:255',
            'hesap_sahibi' => 'required_without:hesap|string|max:255',
        ]);
        
        DB::table('banka_hesaplari')->where('id', $id)->update([
            'banka' => $request->banka ?? $request->banka_adi,
            'hesap' => $request->hesap ?? $request->hesap_sahibi,
            'iban' => $request->iban ?? '',
            'hnumara' => $request->hnumara ?? $request->hesap_no ?? '',
            'sube' => $request->sube ?? $request->sube_kodu ?? $request->sube_adi ?? '',
            'durum' => $request->durum ?? 1,
        ]);
        
        return redirect()->route('admin.banka.index')->with('success', 'Banka hesabı başarıyla güncellendi!');
    }
    
    public function sil($id)
    {
        DB::table('banka_hesaplari')->where('id', $id)->delete();
        return redirect()->route('admin.banka.index')->with('success', 'Banka hesabı başarıyla silindi!');
    }
}






