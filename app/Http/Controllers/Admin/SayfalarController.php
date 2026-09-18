<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SayfalarController extends Controller
{
    public function index()
    {
        $sayfalar = DB::table('sayfalar')
            ->orderBy('id', 'desc')
            ->paginate(20);
            
        return view('admin.sayfalar.index', compact('sayfalar'));
    }
    
    public function ekle()
    {
        return view('admin.sayfalar.ekle');
    }
    
    public function eklePost(Request $request)
    {
        $request->validate([
            'adi' => 'required|string|max:255',
            'aciklama' => 'required',
        ]);
        
        $seo = $request->seo ?: Str::slug($request->adi);
        
        $resim = null;
        if ($request->hasFile('resim')) {
            $file = $request->file('resim');
            $baseName = time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $resim = ImageHelper::saveAsWebp($file, public_path('tema/uploads/sayfalar'), $baseName);
        }
        
        DB::table('sayfalar')->insert([
            'adi' => $request->adi,
            'adi_en' => $request->adi_en,
            'adi_ar' => $request->adi_ar,
            'seo' => $seo,
            'resim' => $resim,
            'aciklama' => $request->aciklama,
            'aciklama_en' => $request->aciklama_en,
            'aciklama_ar' => $request->aciklama_ar,
            'keywords' => $request->keywords,
            'keywords_en' => $request->keywords_en,
            'keywords_ar' => $request->keywords_ar,
            'description' => $request->description,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'kisa' => $request->kisa,
            'kisa_en' => $request->kisa_en,
            'kisa_ar' => $request->kisa_ar,
            'durum' => $request->durum ?? 1,
            'anasayfa' => $request->anasayfa ?? 0,
            'tarih' => date('Y-m-d H:i:s'),
            'dil' => 1,
        ]);
        
        return redirect()->route('admin.sayfalar.index')->with('success', 'Sayfa başarıyla eklendi!');
    }
    
    public function duzenle($id)
    {
        $sayfa = DB::table('sayfalar')->where('id', $id)->first();
        
        if (!$sayfa) {
            return redirect()->route('admin.sayfalar.index')->with('error', 'Sayfa bulunamadı!');
        }
        
        return view('admin.sayfalar.duzenle', compact('sayfa'));
    }
    
    public function duzenlePost(Request $request, $id)
    {
        $request->validate([
            'adi' => 'required|string|max:255',
            'aciklama' => 'required',
        ]);
        
        $seo = $request->seo ?: Str::slug($request->adi);
        
        $data = [
            'adi' => $request->adi,
            'adi_en' => $request->adi_en,
            'adi_ar' => $request->adi_ar,
            'seo' => $seo,
            'aciklama' => $request->aciklama,
            'aciklama_en' => $request->aciklama_en,
            'aciklama_ar' => $request->aciklama_ar,
            'keywords' => $request->keywords,
            'keywords_en' => $request->keywords_en,
            'keywords_ar' => $request->keywords_ar,
            'description' => $request->description,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'kisa' => $request->kisa,
            'kisa_en' => $request->kisa_en,
            'kisa_ar' => $request->kisa_ar,
            'durum' => $request->durum ?? 1,
            'anasayfa' => $request->anasayfa ?? 0,
        ];
        
        // Builder içeriğini kaydet
        if ($request->has('builder_content')) {
            $data['builder_content'] = $request->builder_content;
        }
        
        if ($request->hasFile('resim')) {
            $file = $request->file('resim');
            $baseName = time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $data['resim'] = ImageHelper::saveAsWebp($file, public_path('tema/uploads/sayfalar'), $baseName);
        }
        
        DB::table('sayfalar')->where('id', $id)->update($data);
        
        return redirect()->route('admin.sayfalar.index')->with('success', 'Sayfa başarıyla güncellendi!');
    }
    
    /**
     * Builder içeriğini kaydet (AJAX)
     */
    public function saveBuilder(Request $request, $id)
    {
        $request->validate([
            'builder_content' => 'required|string',
        ]);
        
        DB::table('sayfalar')->where('id', $id)->update([
            'builder_content' => $request->builder_content,
        ]);
        
        return response()->json(['success' => true, 'message' => 'İçerik başarıyla kaydedildi!']);
    }
    
    public function sil($id)
    {
        DB::table('sayfalar')->where('id', $id)->delete();
        return redirect()->route('admin.sayfalar.index')->with('success', 'Sayfa başarıyla silindi!');
    }
}






