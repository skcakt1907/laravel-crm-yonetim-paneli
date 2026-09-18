<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Satilan;
use App\Models\Ayar;
use Illuminate\Support\Facades\Auth;

class HostingController extends Controller
{
    public function index()
    {
        $ayarlar = Ayar::first();
        $hostingler = Satilan::where('uyeid', Auth::guard('uye')->id())
            ->where('tipi', 1) // 1: Hosting
            ->orderBy('tarih', 'desc')
            ->paginate(20);
        
        return view('tema.hostinglerim', compact('ayarlar', 'hostingler'));
    }
    
    public function detay($id)
    {
        $ayarlar = Ayar::first();
        $hosting = Satilan::where('uyeid', Auth::guard('uye')->id())
            ->where('id', $id)
            ->where('tipi', 1)
            ->firstOrFail();
        
        return view('tema.hosting-yonetim', compact('ayarlar', 'hosting'));
    }
    
    public function yenile($id)
    {
        $ayarlar = Ayar::first();
        $hosting = Satilan::where('uyeid', Auth::guard('uye')->id())
            ->where('id', $id)
            ->where('tipi', 1)
            ->firstOrFail();
        
        // Yenileme işlemi
        return view('tema.hosting-yenileme', compact('ayarlar', 'hosting'));
    }
}