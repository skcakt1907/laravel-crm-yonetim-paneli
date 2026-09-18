<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BayiBildirimController extends Controller
{
    /**
     * Bildirimler
     */
    public function index()
    {
        $bayiId = session('admin_id');
        
        // Bildirimler
        $bildirimler = DB::table('bayi_bildirimler')
            ->where('bayi_id', $bayiId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Okunmamış sayısı
        $okunmamisSayisi = DB::table('bayi_bildirimler')
            ->where('bayi_id', $bayiId)
            ->where('okundu', 0)
            ->count();
        
        return view('admin.bayi.bildirimler.index', compact('bildirimler', 'okunmamisSayisi'));
    }
    
    /**
     * Bildirimi Okundu İşaretle
     */
    public function okundu($id)
    {
        DB::table('bayi_bildirimler')
            ->where('id', $id)
            ->where('bayi_id', session('admin_id'))
            ->update(['okundu' => 1, 'okunma_tarihi' => now()]);
        
        return back()->with('success', 'Bildirim okundu olarak işaretlendi!');
    }
    
    /**
     * Tüm Bildirimleri Okundu İşaretle
     */
    public function hepsiniOku()
    {
        DB::table('bayi_bildirimler')
            ->where('bayi_id', session('admin_id'))
            ->where('okundu', 0)
            ->update(['okundu' => 1, 'okunma_tarihi' => now()]);
        
        return back()->with('success', 'Tüm bildirimler okundu olarak işaretlendi!');
    }
}

