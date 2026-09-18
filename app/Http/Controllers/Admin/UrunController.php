<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UrunController extends Controller
{
    public function index()
    {
        $urunler = DB::table('urunler')->orderBy('id', 'desc')->paginate(20);
        return view('admin.urunler.index', compact('urunler'));
    }
    
    public function ekle()
    {
        return view('admin.urunler.ekle');
    }
    
    public function eklePost(Request $request)
    {
        $validated = $request->validate([
            'adi' => 'required|string|max:255',
            'fiyat' => 'required|numeric',
            'stok' => 'nullable|integer',
            'aciklama' => 'nullable|string',
        ]);
        
        DB::table('urunler')->insert([
            'adi' => $validated['adi'],
            'fiyat' => $validated['fiyat'],
            'stok' => $validated['stok'] ?? 0,
            'aciklama' => $validated['aciklama'] ?? '',
            'durum' => $request->has('durum') ? 1 : 0,
            'tarih' => date('Y-m-d H:i:s'),
        ]);
        
        return redirect()->route('admin.urunler.index')->with('success', 'Ürün başarıyla eklendi.');
    }
    
    public function duzenle($id)
    {
        $urun = DB::table('urunler')->where('id', $id)->first();
        if (!$urun) return redirect()->route('admin.urunler.index')->with('error', 'Ürün bulunamadı.');
        return view('admin.urunler.duzenle', compact('urun'));
    }
    
    public function duzenlePost(Request $request, $id)
    {
        $validated = $request->validate([
            'adi' => 'required|string|max:255',
            'fiyat' => 'required|numeric',
            'stok' => 'nullable|integer',
            'aciklama' => 'nullable|string',
        ]);
        
        DB::table('urunler')->where('id', $id)->update([
            'adi' => $validated['adi'],
            'fiyat' => $validated['fiyat'],
            'stok' => $validated['stok'] ?? 0,
            'aciklama' => $validated['aciklama'] ?? '',
            'durum' => $request->has('durum') ? 1 : 0,
        ]);
        
        return redirect()->route('admin.urunler.index')->with('success', 'Ürün başarıyla güncellendi.');
    }
    
    public function sil($id)
    {
        DB::table('urunler')->where('id', $id)->delete();
        return redirect()->route('admin.urunler.index')->with('success', 'Ürün başarıyla silindi.');
    }
}

