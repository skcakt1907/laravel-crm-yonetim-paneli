<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SliderController extends Controller
{
    public function index()
    {
        $sliderlar = DB::table('slider')
            ->orderBy('sira', 'asc')
            ->paginate(20);
            
        return view('admin.slider.index', compact('sliderlar'));
    }
    
    public function ekle()
    {
        return view('admin.slider.ekle');
    }
    
    public function eklePost(Request $request)
    {
        $request->validate([
            'adi' => 'required|string|max:255',
            'resim' => 'nullable|image',
        ]);
        
        $resim = null;
        if ($request->hasFile('resim')) {
            $file = $request->file('resim');
            $baseName = time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $resim = ImageHelper::saveAsWebp($file, public_path('tema/uploads/slider'), $baseName);
        }
        
        $sliderId = DB::table('slider')->insertGetId([
            'adi' => $request->adi,
            'url' => $request->url,
            'sekme' => $request->sekme ?? 0,
            'resim' => $resim,
            'aciklama' => $request->aciklama,
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
            'tarih' => date('Y-m-d H:i:s'),
            'dil' => 1,
        ]);
        \App\Models\Ceviri::sync('slider', $request->input('ceviriler', []), $sliderId);

        return redirect()->route('admin.slider.index')->with('success', 'Slider başarıyla eklendi!');
    }
    
    public function duzenle($id)
    {
        $slider = DB::table('slider')->where('id', $id)->first();
        
        if (!$slider) {
            return redirect()->route('admin.slider.index')->with('error', 'Slider bulunamadı!');
        }
        
        return view('admin.slider.duzenle', compact('slider'));
    }
    
    public function duzenlePost(Request $request, $id)
    {
        $request->validate([
            'adi' => 'required|string|max:255',
        ]);
        
        $data = [
            'adi' => $request->adi,
            'url' => $request->url,
            'sekme' => $request->sekme ?? 0,
            'aciklama' => $request->aciklama,
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
        ];
        
        // Mevcut resim referansı (silme/değiştirme için)
        $existing = DB::table('slider')->where('id', $id)->value('resim');

        // Resim sil checkbox
        if ($request->boolean('delete_resim') && !empty($existing)) {
            foreach ([public_path('tema/uploads/'.$existing), public_path('tema/uploads/slider/'.$existing)] as $p) {
                if (is_file($p)) { @unlink($p); }
            }
            $data['resim'] = null;
        }

        if ($request->hasFile('resim')) {
            $file = $request->file('resim');
            $baseName = time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $data['resim'] = ImageHelper::saveAsWebp($file, public_path('tema/uploads/slider'), $baseName);
        }

        DB::table('slider')->where('id', $id)->update($data);
        \App\Models\Ceviri::sync('slider', $request->input('ceviriler', []), (int) $id);

        return redirect()->route('admin.slider.index')->with('success', 'Slider başarıyla güncellendi!');
    }
    
    public function sil($id)
    {
        DB::table('slider')->where('id', $id)->delete();
        return redirect()->route('admin.slider.index')->with('success', 'Slider başarıyla silindi!');
    }
}






