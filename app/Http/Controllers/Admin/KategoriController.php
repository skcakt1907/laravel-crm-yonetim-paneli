<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AutoTranslateContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoriler = DB::table('web_kategori')
            ->orderBy('sira', 'asc')
            ->paginate(20);
        
        return view('admin.kategoriler.index', compact('kategoriler'));
    }
    
    public function ekle()
    {
        return view('admin.kategoriler.ekle');
    }
    
    public function eklePost(Request $request)
    {
        $validated = $request->validate([
            'adi' => 'required|string|max:255',
            'sira' => 'nullable|integer',
            'durum' => 'nullable',
        ]);
        
        // SEO URL oluştur
        $seo = Str::slug($validated['adi']);
        
        // Aynı SEO varsa benzersiz yap
        $sayac = 1;
        $orijinal_seo = $seo;
        while (DB::table('web_kategori')->where('seo', $seo)->exists()) {
            $seo = $orijinal_seo . '-' . $sayac;
            $sayac++;
        }
        
        $data = [
            'adi' => $validated['adi'],
            'seo' => $seo,
            'sira' => $validated['sira'] ?? 0,
            'durum' => $request->has('durum') ? 1 : 0,
            'dil' => 1,
        ];

        if (Schema::hasColumn('web_kategori', 'created_at')) {
            $data['created_at'] = now();
        }
        if (Schema::hasColumn('web_kategori', 'updated_at')) {
            $data['updated_at'] = now();
        }

        $kategoriId = DB::table('web_kategori')->insertGetId($data);
        \App\Models\Ceviri::sync('web_kategori', $request->input('ceviriler', []), $kategoriId);

        return redirect()->route('admin.kategoriler.index')->with('success', 'Kategori başarıyla eklendi.');
    }
    
    public function duzenle($id)
    {
        $kategori = DB::table('web_kategori')->where('id', $id)->first();
        
        if (!$kategori) {
            return redirect()->route('admin.kategoriler.index')->with('error', 'Kategori bulunamadı.');
        }
        
        return view('admin.kategoriler.duzenle', compact('kategori'));
    }
    
    public function duzenlePost(Request $request, $id)
    {
        $validated = $request->validate([
            'adi' => 'required|string|max:255',
            'sira' => 'nullable|integer',
        ]);
        
        // SEO URL oluştur
        $seo = Str::slug($validated['adi']);
        
        // Aynı SEO varsa (kendisi hariç) benzersiz yap
        $sayac = 1;
        $orijinal_seo = $seo;
        while (DB::table('web_kategori')->where('seo', $seo)->where('id', '!=', $id)->exists()) {
            $seo = $orijinal_seo . '-' . $sayac;
            $sayac++;
        }
        
        DB::table('web_kategori')->where('id', $id)->update([
            'adi' => $validated['adi'],
            'seo' => $seo,
            'sira' => $validated['sira'] ?? 0,
            'durum' => $request->has('durum') ? 1 : 0,
        ]);
        \App\Models\Ceviri::sync('web_kategori', $request->input('ceviriler', []), (int) $id);

        return redirect()->route('admin.kategoriler.index')->with('success', 'Kategori başarıyla güncellendi.');
    }
    
    public function sil($id)
    {
        try {
            // ID validation
            if (!is_numeric($id) || $id <= 0) {
                return redirect()->route('admin.kategoriler.index')->with('error', 'Geçersiz kategori ID.');
            }
            
            $kategori = DB::table('web_kategori')->where('id', $id)->first();
            
            if (!$kategori) {
                return redirect()->route('admin.kategoriler.index')->with('error', 'Kategori bulunamadı.');
            }
            
            // Kategoriye bağlı paketler var mı kontrol et
            $paketSayisi = DB::table('yazilimlar')
                ->whereRaw("FIND_IN_SET(?, kategori)", [$id])
                ->count();
            
            if ($paketSayisi > 0) {
                return redirect()->route('admin.kategoriler.index')
                    ->with('error', "Bu kategoriye bağlı {$paketSayisi} adet paket bulunmaktadır. Önce paketleri başka kategoriye taşıyın veya silin.");
            }
            
            DB::table('web_kategori')->where('id', $id)->delete();
            
            return redirect()->route('admin.kategoriler.index')->with('success', 'Kategori başarıyla silindi.');
        } catch (\Exception $e) {
            \Log::error('Kategori silme hatası', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.kategoriler.index')
                ->with('error', 'Kategori silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }
}

