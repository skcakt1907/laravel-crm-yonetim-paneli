<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotDefteriController extends Controller
{
    public function index()
    {
        $notlar = DB::table('notlar')
            ->where('uyeid', session('admin_id'))
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.not-defteri.index', compact('notlar'));
    }
    
    public function ekle(Request $request)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'icerik' => 'required|string',
        ]);
        
        DB::table('notlar')->insert([
            'baslik' => $validated['baslik'],
            'desc' => $validated['icerik'], // icerik -> desc
            'uyeid' => session('admin_id'), // kullanici_id -> uyeid
        ]);
        
        return redirect()->route('admin.not-defteri.index')->with('success', 'Not başarıyla eklendi.');
    }
    
    public function guncelle(Request $request, $id)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'icerik' => 'required|string',
        ]);
        
        DB::table('notlar')->where('id', $id)->where('uyeid', session('admin_id'))->update([
            'baslik' => $validated['baslik'],
            'desc' => $validated['icerik'], // icerik -> desc
        ]);
        
        return redirect()->route('admin.not-defteri.index')->with('success', 'Not başarıyla güncellendi.');
    }
    
    public function sil($id)
    {
        DB::table('notlar')->where('id', $id)->where('uyeid', session('admin_id'))->delete();
        return redirect()->route('admin.not-defteri.index')->with('success', 'Not başarıyla silindi.');
    }
}

