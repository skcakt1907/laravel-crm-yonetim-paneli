<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IletisimController extends Controller
{
    public function index()
    {
        // İletişim tablosu varsa mesajları getir
        if (!Schema::hasTable('iletisim')) {
            return view('admin.iletisim.index', ['mesajlar' => collect([]), 'okunmamisSayisi' => 0]);
        }
        
        $mesajlar = DB::table('iletisim')
            ->orderBy('tarih', 'desc')
            ->orderBy('durum', 'asc') // Okunmamışlar önce
            ->paginate(20);
            
        // Okunmamış mesaj sayısı
        $okunmamisSayisi = DB::table('iletisim')->where('durum', 0)->count();
            
        return view('admin.iletisim.index', compact('mesajlar', 'okunmamisSayisi'));
    }
    
    public function detay($id)
    {
        if (!Schema::hasTable('iletisim')) {
            return redirect()->route('admin.iletisim.index')->with('error', 'İletişim tablosu bulunamadı!');
        }
        
        $mesaj = DB::table('iletisim')->where('id', $id)->first();
        
        if (!$mesaj) {
            return redirect()->route('admin.iletisim.index')->with('error', 'Mesaj bulunamadı!');
        }
        
        // Mesajı okundu olarak işaretle
        DB::table('iletisim')->where('id', $id)->update(['durum' => 1]);
        
        return view('admin.iletisim.detay', compact('mesaj'));
    }
    
    public function sil($id)
    {
        if (!Schema::hasTable('iletisim')) {
            return redirect()->route('admin.iletisim.index')->with('error', 'İletişim tablosu bulunamadı!');
        }
        
        DB::table('iletisim')->where('id', $id)->delete();
        return redirect()->route('admin.iletisim.index')->with('success', 'Mesaj başarıyla silindi!');
    }
    
    public function durumDegistir($id)
    {
        if (!Schema::hasTable('iletisim')) {
            return redirect()->route('admin.iletisim.index')->with('error', 'İletişim tablosu bulunamadı!');
        }
        
        $mesaj = DB::table('iletisim')->where('id', $id)->first();
        
        if (!$mesaj) {
            return redirect()->route('admin.iletisim.index')->with('error', 'Mesaj bulunamadı!');
        }
        
        $yeniDurum = $mesaj->durum == 1 ? 0 : 1;
        
        DB::table('iletisim')->where('id', $id)->update(['durum' => $yeniDurum]);
        
        return redirect()->back()->with('success', 'Durum güncellendi!');
    }
    
    public function topluSil(Request $request)
    {
        if (!Schema::hasTable('iletisim')) {
            return redirect()->route('admin.iletisim.index')->with('error', 'İletişim tablosu bulunamadı!');
        }

        // Hem JSON string hem PHP array kabul et
        $raw = $request->input('ids');
        if (is_array($raw)) {
            $ids = array_values(array_filter(array_map('intval', $raw)));
        } elseif (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $ids = is_array($decoded) ? array_values(array_filter(array_map('intval', $decoded)))
                                       : array_values(array_filter(array_map('intval', explode(',', $raw))));
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Silinecek mesaj seçilmedi!');
        }

        $deleted = DB::table('iletisim')->whereIn('id', $ids)->delete();

        return redirect()->route('admin.iletisim.index')->with('success', $deleted . ' mesaj başarıyla silindi!');
    }
}

