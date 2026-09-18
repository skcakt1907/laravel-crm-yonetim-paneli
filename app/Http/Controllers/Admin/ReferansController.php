<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReferansController extends Controller
{
    public function index()
    {
        $referanslar = DB::table('referanslar')
            ->orderBy('durum', 'desc')   // aktifler önce
            ->orderBy('sira', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(20);
        
        return view('admin.referanslar.index', compact('referanslar'));
    }
    
    public function ekle()
    {
        return view('admin.referanslar.ekle');
    }
    
    public function eklePost(Request $request)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
        ]);
        
        $seo = Str::slug($request->baslik);
        
        // Logo yükleme
        $logo = null;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $baseName = time() . '_' . Str::slug($request->baslik);
            $filename = ImageHelper::saveAsWebp($file, public_path('tema/uploads/referanslar'), $baseName);
            $logo = 'tema/uploads/referanslar/' . $filename;
        }
        
        // Resim yükleme
        $resim = null;
        if ($request->hasFile('resim')) {
            $file = $request->file('resim');
            $baseName = time() . '_resim_' . Str::slug($request->baslik);
            $filename = ImageHelper::saveAsWebp($file, public_path('tema/uploads/referanslar'), $baseName);
            $resim = 'tema/uploads/referanslar/' . $filename;
        }
        
        $insertData = [
            'adi' => $request->baslik, // baslik -> adi
            'seo' => $seo,
            'aciklama' => $request->aciklama,
            'resim' => $resim,
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
        ];
        
        // Eğer kolonlar varsa ekle
        if (Schema::hasColumn('referanslar', 'baslik')) {
            $insertData['baslik'] = $request->baslik;
        }
        if (Schema::hasColumn('referanslar', 'logo')) {
            $insertData['logo'] = $logo;
        }
        if (Schema::hasColumn('referanslar', 'link')) {
            $insertData['link'] = $request->link;
        }
        
        $refId = DB::table('referanslar')->insertGetId($insertData);
        \App\Models\Ceviri::sync('referanslar', $request->input('ceviriler', []), $refId);

        return redirect()->route('admin.referanslar.index')->with('success', 'Referans başarıyla eklendi.');
    }
    
    public function duzenle($id)
    {
        $referans = DB::table('referanslar')->where('id', $id)->first();
        
        if (!$referans) {
            return redirect()->route('admin.referanslar.index')->with('error', 'Referans bulunamadı!');
        }
        
        return view('admin.referanslar.duzenle', compact('referans'));
    }
    
    public function duzenlePost(Request $request, $id)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
        ]);
        
        $referans = DB::table('referanslar')->where('id', $id)->first();
        
        if (!$referans) {
            return redirect()->route('admin.referanslar.index')->with('error', 'Referans bulunamadı!');
        }
        
        $seo = Str::slug($request->baslik);
        
        $updateData = [
            'adi' => $request->baslik, // baslik -> adi
            'seo' => $seo,
            'aciklama' => $request->aciklama,
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
        ];
        
        // Eğer kolonlar varsa ekle
        if (Schema::hasColumn('referanslar', 'baslik')) {
            $updateData['baslik'] = $request->baslik;
        }
        if (Schema::hasColumn('referanslar', 'link')) {
            $updateData['link'] = $request->link;
        }
        
        // Logo yükleme (DB'de logo kolonu yok, resim kolonuna yaz)
        if ($request->hasFile('logo')) {
            if (!empty($referans->resim) && file_exists(public_path($referans->resim))) {
                @unlink(public_path($referans->resim));
            }

            $file = $request->file('logo');
            $filename = time() . '_' . Str::slug($request->baslik) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('tema/uploads/referanslar'), $filename);
            $updateData['resim'] = 'tema/uploads/referanslar/' . $filename;
        }
        
        // Resim yükleme
        if ($request->hasFile('resim')) {
            if ($referans->resim && file_exists(public_path($referans->resim))) {
                unlink(public_path($referans->resim));
            }
            
            $file = $request->file('resim');
            $filename = time() . '_resim_' . Str::slug($request->baslik) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('tema/uploads/referanslar'), $filename);
            $updateData['resim'] = 'tema/uploads/referanslar/' . $filename;
        }
        
        DB::table('referanslar')->where('id', $id)->update($updateData);
        \App\Models\Ceviri::sync('referanslar', $request->input('ceviriler', []), (int) $id);

        return redirect()->route('admin.referanslar.index')->with('success', 'Referans başarıyla güncellendi.');
    }
    
    public function sil($id)
    {
        $referans = DB::table('referanslar')->where('id', $id)->first();

        if (!$referans) {
            return redirect()->route('admin.referanslar.index')->with('error', 'Referans bulunamadı!');
        }

        // Resmi sil (logo kolonu DB'de yok, sadece resim)
        if (!empty($referans->resim) && file_exists(public_path($referans->resim))) {
            @unlink(public_path($referans->resim));
        }

        DB::table('referanslar')->where('id', $id)->delete();

        return redirect()->route('admin.referanslar.index')->with('success', 'Referans silindi.');
    }
}


