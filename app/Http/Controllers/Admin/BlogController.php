<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use App\Services\AutoTranslateContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $bloglar = DB::table('blog')
            ->orderBy('id', 'desc')
            ->paginate(20);

        $statCards = \App\Support\AdminStats::standart('blog', [
            'labels' => ['toplam' => 'TOPLAM YAZI', 'aktif' => 'YAYINDA', 'pasif' => 'TASLAK', 'buay' => 'BU AY'],
            'icons'  => ['toplam' => '📝', 'aktif' => '✅', 'pasif' => '📄', 'buay' => '📅'],
        ]);

        return view('admin.blog.index', compact('bloglar', 'statCards'));
    }
    
    public function ekle()
    {
        $diller = DB::table('diller')->where('durum', 1)->get();
        return view('admin.blog.ekle', compact('diller'));
    }
    
    public function eklePost(Request $request)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'icerik' => 'required|string',
            'dil' => 'nullable|integer',
        ]);
        
        $seo = Str::slug($request->baslik);
        
        // Resim yükleme
        $resim = null;
        if ($request->hasFile('resim')) {
            $file = $request->file('resim');
            $baseName = time() . '_' . Str::slug($request->baslik);
            $uploadPath = public_path('tema/uploads/bloglar');
            $filename = ImageHelper::saveAsWebp($file, $uploadPath, $baseName);
            $resim = $filename;
        }
        
        $baseData = [
            'seo' => $seo,
            'resim' => $resim,
            'dil' => (int) ($request->dil ?? 1),
            'durum' => $request->durum ?? 1,
            'tarih' => now(),
        ];

        // Başlık / içerik / özet alanları (eski ve yeni şema uyumu)
        if (Schema::hasColumn('blog', 'baslik')) {
            $baseData['baslik'] = $request->baslik;
        }
        if (Schema::hasColumn('blog', 'baslik_en')) {
            $baseData['baslik_en'] = $request->baslik_en;
        }
        if (Schema::hasColumn('blog', 'baslik_ar')) {
            $baseData['baslik_ar'] = $request->baslik_ar;
        }
        if (Schema::hasColumn('blog', 'adi')) {
            $baseData['adi'] = $request->baslik;
        }
        if (Schema::hasColumn('blog', 'adi_en')) {
            $baseData['adi_en'] = $request->baslik_en;
        }
        if (Schema::hasColumn('blog', 'adi_ar')) {
            $baseData['adi_ar'] = $request->baslik_ar;
        }

        if (Schema::hasColumn('blog', 'icerik')) {
            $baseData['icerik'] = $request->icerik;
        }
        if (Schema::hasColumn('blog', 'icerik_en')) {
            $baseData['icerik_en'] = $request->icerik_en;
        }
        if (Schema::hasColumn('blog', 'icerik_ar')) {
            $baseData['icerik_ar'] = $request->icerik_ar;
        }
        if (Schema::hasColumn('blog', 'aciklama')) {
            $baseData['aciklama'] = $request->icerik;
        }
        if (Schema::hasColumn('blog', 'aciklama_en')) {
            $baseData['aciklama_en'] = $request->icerik_en;
        }
        if (Schema::hasColumn('blog', 'aciklama_ar')) {
            $baseData['aciklama_ar'] = $request->icerik_ar;
        }

        if (Schema::hasColumn('blog', 'ozet')) {
            $baseData['ozet'] = $request->ozet;
        }
        if (Schema::hasColumn('blog', 'ozet_en')) {
            $baseData['ozet_en'] = $request->ozet_en;
        }
        if (Schema::hasColumn('blog', 'ozet_ar')) {
            $baseData['ozet_ar'] = $request->ozet_ar;
        }
        if (Schema::hasColumn('blog', 'kisa')) {
            $baseData['kisa'] = $request->ozet;
        }
        if (Schema::hasColumn('blog', 'kisa_en')) {
            $baseData['kisa_en'] = $request->ozet_en;
        }
        if (Schema::hasColumn('blog', 'kisa_ar')) {
            $baseData['kisa_ar'] = $request->ozet_ar;
        }

        // SEO alanları (mevcutsa)
        if (Schema::hasColumn('blog', 'seo_baslik')) {
            $baseData['seo_baslik'] = $request->seo_baslik ?? $request->baslik;
        }
        if (Schema::hasColumn('blog', 'seo_baslik_en')) {
            $baseData['seo_baslik_en'] = $request->seo_baslik_en;
        }
        if (Schema::hasColumn('blog', 'seo_baslik_ar')) {
            $baseData['seo_baslik_ar'] = $request->seo_baslik_ar;
        }
        if (Schema::hasColumn('blog', 'seo_aciklama')) {
            $baseData['seo_aciklama'] = $request->seo_aciklama;
        }
        if (Schema::hasColumn('blog', 'seo_aciklama_en')) {
            $baseData['seo_aciklama_en'] = $request->seo_aciklama_en;
        }
        if (Schema::hasColumn('blog', 'seo_aciklama_ar')) {
            $baseData['seo_aciklama_ar'] = $request->seo_aciklama_ar;
        }
        if (Schema::hasColumn('blog', 'seo_anahtar')) {
            $baseData['seo_anahtar'] = $request->seo_anahtar;
        }
        if (Schema::hasColumn('blog', 'seo_anahtar_en')) {
            $baseData['seo_anahtar_en'] = $request->seo_anahtar_en;
        }
        if (Schema::hasColumn('blog', 'seo_anahtar_ar')) {
            $baseData['seo_anahtar_ar'] = $request->seo_anahtar_ar;
        }

        if (Schema::hasColumn('blog', 'created_at')) {
            $baseData['created_at'] = now();
        }
        if (Schema::hasColumn('blog', 'updated_at')) {
            $baseData['updated_at'] = now();
        }

        $blogId = DB::table('blog')->insertGetId($baseData);
        \App\Models\Ceviri::sync('blog', $request->input('ceviriler', []), $blogId);

        return redirect()->route('admin.blog.index')->with('success', 'Blog yazısı başarıyla eklendi.');
    }
    
    public function duzenle($id)
    {
        $blog = DB::table('blog')->where('id', $id)->first();
        
        if (!$blog) {
            return redirect()->route('admin.blog.index')->with('error', 'Blog bulunamadı!');
        }
        
        $diller = DB::table('diller')->where('durum', 1)->get();
        
        return view('admin.blog.duzenle', compact('blog', 'diller'));
    }
    
    public function duzenlePost(Request $request, $id)
    {
        $request->validate([
            'adi' => 'required_without:baslik|string|max:255',
            'baslik' => 'required_without:adi|string|max:255',
            'aciklama' => 'nullable|string',
            'icerik' => 'nullable|string',
            'dil' => 'nullable|integer',
        ]);
        // Form alan adı uyumluluğu (adi <-> baslik, aciklama <-> icerik)
        if (!$request->filled('baslik') && $request->filled('adi')) {
            $request->merge(['baslik' => $request->input('adi')]);
        }
        if (!$request->filled('icerik') && $request->filled('aciklama')) {
            $request->merge(['icerik' => $request->input('aciklama')]);
        }

        $blog = DB::table('blog')->where('id', $id)->first();
        
        if (!$blog) {
            return redirect()->route('admin.blog.index')->with('error', 'Blog bulunamadı!');
        }
        
        $seo = Str::slug($request->baslik);
        
        $updateData = [
            'seo' => $seo,
            'dil' => $request->dil ?? 1,
            'durum' => $request->durum ?? 1,
        ];

        if (Schema::hasColumn('blog', 'baslik')) {
            $updateData['baslik'] = $request->baslik;
        }
        if (Schema::hasColumn('blog', 'baslik_en')) {
            $updateData['baslik_en'] = $request->baslik_en;
        }
        if (Schema::hasColumn('blog', 'baslik_ar')) {
            $updateData['baslik_ar'] = $request->baslik_ar;
        }
        if (Schema::hasColumn('blog', 'adi')) {
            $updateData['adi'] = $request->baslik;
        }
        if (Schema::hasColumn('blog', 'adi_en')) {
            $updateData['adi_en'] = $request->baslik_en;
        }
        if (Schema::hasColumn('blog', 'adi_ar')) {
            $updateData['adi_ar'] = $request->baslik_ar;
        }

        if (Schema::hasColumn('blog', 'icerik')) {
            $updateData['icerik'] = $request->icerik;
        }
        if (Schema::hasColumn('blog', 'icerik_en')) {
            $updateData['icerik_en'] = $request->icerik_en;
        }
        if (Schema::hasColumn('blog', 'icerik_ar')) {
            $updateData['icerik_ar'] = $request->icerik_ar;
        }
        if (Schema::hasColumn('blog', 'aciklama')) {
            $updateData['aciklama'] = $request->icerik;
        }
        if (Schema::hasColumn('blog', 'aciklama_en')) {
            $updateData['aciklama_en'] = $request->icerik_en;
        }
        if (Schema::hasColumn('blog', 'aciklama_ar')) {
            $updateData['aciklama_ar'] = $request->icerik_ar;
        }

        if (Schema::hasColumn('blog', 'ozet')) {
            $updateData['ozet'] = $request->ozet;
        }
        if (Schema::hasColumn('blog', 'ozet_en')) {
            $updateData['ozet_en'] = $request->ozet_en;
        }
        if (Schema::hasColumn('blog', 'ozet_ar')) {
            $updateData['ozet_ar'] = $request->ozet_ar;
        }
        if (Schema::hasColumn('blog', 'kisa')) {
            $updateData['kisa'] = $request->ozet;
        }
        if (Schema::hasColumn('blog', 'kisa_en')) {
            $updateData['kisa_en'] = $request->ozet_en;
        }
        if (Schema::hasColumn('blog', 'kisa_ar')) {
            $updateData['kisa_ar'] = $request->ozet_ar;
        }

        if (Schema::hasColumn('blog', 'seo_baslik')) {
            $updateData['seo_baslik'] = $request->seo_baslik ?? $request->baslik;
        }
        if (Schema::hasColumn('blog', 'seo_baslik_en')) {
            $updateData['seo_baslik_en'] = $request->seo_baslik_en;
        }
        if (Schema::hasColumn('blog', 'seo_baslik_ar')) {
            $updateData['seo_baslik_ar'] = $request->seo_baslik_ar;
        }
        if (Schema::hasColumn('blog', 'seo_aciklama')) {
            $updateData['seo_aciklama'] = $request->seo_aciklama;
        }
        if (Schema::hasColumn('blog', 'seo_aciklama_en')) {
            $updateData['seo_aciklama_en'] = $request->seo_aciklama_en;
        }
        if (Schema::hasColumn('blog', 'seo_aciklama_ar')) {
            $updateData['seo_aciklama_ar'] = $request->seo_aciklama_ar;
        }
        if (Schema::hasColumn('blog', 'seo_anahtar')) {
            $updateData['seo_anahtar'] = $request->seo_anahtar;
        }
        if (Schema::hasColumn('blog', 'seo_anahtar_en')) {
            $updateData['seo_anahtar_en'] = $request->seo_anahtar_en;
        }
        if (Schema::hasColumn('blog', 'seo_anahtar_ar')) {
            $updateData['seo_anahtar_ar'] = $request->seo_anahtar_ar;
        }
        
        // Resim sil checkbox
        if ($request->boolean('delete_resim') && !empty($blog->resim)) {
            foreach ([public_path($blog->resim), public_path('tema/uploads/bloglar/' . $blog->resim)] as $p) {
                if (is_file($p)) { @unlink($p); }
            }
            DB::table('blog')->where('id', $blog->id)->update(['resim' => null]);
            $blog->resim = null;
        }

        // Resim yükleme
        if ($request->hasFile('resim')) {
            // Eski resmi sil (hem tam path hem dosya adı senaryosu)
            if ($blog->resim) {
                $existingPath = public_path($blog->resim);
                $existingAltPath = public_path('tema/uploads/bloglar/' . $blog->resim);
                if (file_exists($existingPath)) {
                    @unlink($existingPath);
                } elseif (file_exists($existingAltPath)) {
                    @unlink($existingAltPath);
                }
            }

            $file = $request->file('resim');
            $baseName = time() . '_' . Str::slug($request->baslik);
            $uploadPath = public_path('tema/uploads/bloglar');
            $updateData['resim'] = ImageHelper::saveAsWebp($file, $uploadPath, $baseName);
        }
        
        DB::table('blog')->where('id', $id)->update($updateData);
        \App\Models\Ceviri::sync('blog', $request->input('ceviriler', []), (int) $id);

        return redirect()->route('admin.blog.index')->with('success', 'Blog yazısı başarıyla güncellendi.');
    }
    
    public function sil($id)
    {
        $blog = DB::table('blog')->where('id', $id)->first();
        
        if (!$blog) {
            return redirect()->route('admin.blog.index')->with('error', 'Blog bulunamadı!');
        }
        
        // Resmi sil
        if ($blog->resim && file_exists(public_path($blog->resim))) {
            unlink(public_path($blog->resim));
        }
        
        DB::table('blog')->where('id', $id)->delete();
        
        return redirect()->route('admin.blog.index')->with('success', 'Blog yazısı başarıyla silindi.');
    }
}


