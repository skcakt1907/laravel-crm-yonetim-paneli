<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class KampanyaController extends Controller
{
    public function index()
    {
        $kampanyalar = collect([]);
        if (Schema::hasTable('kampanyalar')) {
            $query = DB::table('kampanyalar');
            
            // Sıralama - kolonlar varsa kullan
            if (Schema::hasColumn('kampanyalar', 'sira')) {
                $query->orderBy('sira', 'asc');
            }
            if (Schema::hasColumn('kampanyalar', 'created_at')) {
                $query->orderBy('created_at', 'desc');
            } else {
                // created_at yoksa id'ye göre sırala
                $query->orderBy('id', 'desc');
            }
            
            $kampanyalar = $query->get();
        }
        
        // İndirimli paketleri de göster (fırsat olarak)
        $firsatPaketler = collect([]);
        $hasIndirim = Schema::hasColumn('yazilimlar', 'indirim');
        $hasFirsat = Schema::hasColumn('yazilimlar', 'firsat');
        
        if ($hasIndirim || $hasFirsat) {
            // Mevcut kolonları kontrol et
            $selectColumns = ['id', 'adi', 'resim', 'indirim', 'firsat', 'seo'];
            
            // fiyat ve tutar kolonlarını kontrol et
            if (Schema::hasColumn('yazilimlar', 'fiyat')) {
                $selectColumns[] = 'fiyat';
            }
            if (Schema::hasColumn('yazilimlar', 'tutar')) {
                $selectColumns[] = 'tutar';
            }
            
            $firsatPaketlerQuery = DB::table('yazilimlar')
                ->where('durum', 1)
                ->where(function($query) use ($hasIndirim, $hasFirsat) {
                    if ($hasIndirim) {
                        $query->whereNotNull('indirim')->where('indirim', '>', 0);
                    }
                    if ($hasFirsat) {
                        if ($hasIndirim) {
                            $query->orWhereNotNull('firsat')->where('firsat', 1);
                        } else {
                            $query->whereNotNull('firsat')->where('firsat', 1);
                        }
                    }
                })
                ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($query) {
                    return $query->where('dil', 1);
                })
                ->orderBy('id', 'desc')
                ->select($selectColumns)
                ->get();
        }
        
        return view('admin.kampanyalar.index', compact('kampanyalar', 'firsatPaketler'));
    }
    
    public function create()
    {
        return view('admin.kampanyalar.ekle');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'resim' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'indirim' => 'nullable|numeric|min:0|max:100',
            'link' => 'nullable|url|max:500',
            'baslangic_tarihi' => 'nullable|date',
            'bitis_tarihi' => 'nullable|date|after_or_equal:baslangic_tarihi',
            'sira' => 'nullable|integer|min:0',
            'durum' => 'nullable',
        ]);
        
        $data = [
            'baslik' => $request->baslik,
            'baslik_en' => $request->baslik_en,
            'baslik_ar' => $request->baslik_ar,
            'aciklama' => $request->aciklama,
            'aciklama_en' => $request->aciklama_en,
            'aciklama_ar' => $request->aciklama_ar,
            'indirim' => $request->indirim ?? 0,
            'link' => $request->link,
            'baslangic_tarihi' => $request->baslangic_tarihi,
            'bitis_tarihi' => $request->bitis_tarihi,
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
            'seo' => $request->seo ?? Str::slug($request->baslik),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // Resim yükleme
        if ($request->hasFile('resim')) {
            $resim = $request->file('resim');
            $baseName = time() . '_' . Str::slug($request->baslik);
            $hedefKlasor = public_path('tema/uploads/kampanyalar');
            $data['resim'] = ImageHelper::saveAsWebp($resim, $hedefKlasor, $baseName);
        }
        
        // SEO unique kontrolü
        if (Schema::hasTable('kampanyalar')) {
            $seo = $data['seo'];
            $counter = 1;
            while (DB::table('kampanyalar')->where('seo', $seo)->exists()) {
                $seo = $data['seo'] . '-' . $counter;
                $counter++;
            }
            $data['seo'] = $seo;
            
            $kid = DB::table('kampanyalar')->insertGetId($data);
            \App\Models\Ceviri::sync('kampanyalar', $request->input('ceviriler', []), $kid);

            // 📧 Kampanya bildirimi - sadece izin verenler
            if ($request->has('mail_gonder') && $request->mail_gonder == 1) {
                try {
                    $query = DB::table('uyeler')
                        ->where('durum', 1)
                        ->whereNotNull('email')
                        ->where('email', '!=', '');

                    // İzin kolonu varsa kullan (yoksa email_bildirim fallback)
                    if (Schema::hasColumn('uyeler', 'kampanya_mail_izin')) {
                        $query->where('kampanya_mail_izin', 1);
                    } elseif (Schema::hasColumn('uyeler', 'email_bildirim')) {
                        $query->where('email_bildirim', 1);
                    }

                    $uyeIds = $query->pluck('id');
                    $sayac = 0;
                    foreach ($uyeIds as $uyeId) {
                        try {
                            \App\Services\CustomerNotifier::kampanyaBildirimi($uyeId, $kid);
                            $sayac++;
                        } catch (\Throwable $e) {
                            \Log::warning('Kampanya mail hatası', ['uye_id' => $uyeId, 'err' => $e->getMessage()]);
                        }
                    }
                    \Log::info('Kampanya bildirimi gönderildi', ['kampanya_id' => $kid, 'alici' => $sayac]);
                } catch (\Throwable $e) {
                    \Log::warning('Kampanya toplu mail hatası', ['err' => $e->getMessage()]);
                }
            }
        }

        return redirect()->route('admin.kampanyalar.index')->with('success', 'Kampanya başarıyla oluşturuldu.');
    }
    
    public function edit($id)
    {
        $kampanya = null;
        if (Schema::hasTable('kampanyalar')) {
            $kampanya = DB::table('kampanyalar')->where('id', $id)->first();
        }
        
        if (!$kampanya) {
            abort(404, 'Kampanya bulunamadı');
        }
        
        return view('admin.kampanyalar.duzenle', compact('kampanya'));
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'resim' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'indirim' => 'nullable|numeric|min:0|max:100',
            'link' => 'nullable|url|max:500',
            'baslangic_tarihi' => 'nullable|date',
            'bitis_tarihi' => 'nullable|date|after_or_equal:baslangic_tarihi',
            'sira' => 'nullable|integer|min:0',
            'durum' => 'nullable',
        ]);
        
        $kampanya = DB::table('kampanyalar')->where('id', $id)->first();
        if (!$kampanya) {
            abort(404, 'Kampanya bulunamadı');
        }
        
        $data = [
            'baslik' => $request->baslik,
            'baslik_en' => $request->baslik_en,
            'baslik_ar' => $request->baslik_ar,
            'aciklama' => $request->aciklama,
            'aciklama_en' => $request->aciklama_en,
            'aciklama_ar' => $request->aciklama_ar,
            'indirim' => $request->indirim ?? 0,
            'link' => $request->link,
            'baslangic_tarihi' => $request->baslangic_tarihi,
            'bitis_tarihi' => $request->bitis_tarihi,
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
            'updated_at' => now(),
        ];
        
        // SEO güncelleme
        if ($request->seo && $request->seo != $kampanya->seo) {
            $seo = $request->seo;
            $counter = 1;
            while (DB::table('kampanyalar')->where('seo', $seo)->where('id', '!=', $id)->exists()) {
                $seo = $request->seo . '-' . $counter;
                $counter++;
            }
            $data['seo'] = $seo;
        }
        
        // Resim yükleme
        if ($request->hasFile('resim')) {
            $resim = $request->file('resim');
            $resimAdi = time() . '_' . Str::slug($request->baslik) . '.' . $resim->getClientOriginalExtension();
            
            $hedefKlasor = public_path('tema/uploads/kampanyalar');
            if (!file_exists($hedefKlasor)) {
                mkdir($hedefKlasor, 0755, true);
            }
            
            // Eski resmi sil
            if ($kampanya->resim && file_exists($hedefKlasor . '/' . $kampanya->resim)) {
                @unlink($hedefKlasor . '/' . $kampanya->resim);
            }
            
            $resim->move($hedefKlasor, $resimAdi);
            $data['resim'] = $resimAdi;
        }
        
        DB::table('kampanyalar')->where('id', $id)->update($data);
        \App\Models\Ceviri::sync('kampanyalar', $request->input('ceviriler', []), (int) $id);

        return redirect()->route('admin.kampanyalar.index')->with('success', 'Kampanya başarıyla güncellendi.');
    }
    
    public function destroy($id)
    {
        $kampanya = DB::table('kampanyalar')->where('id', $id)->first();
        if (!$kampanya) {
            abort(404, 'Kampanya bulunamadı');
        }
        
        // Resmi sil
        if ($kampanya->resim) {
            $resimYolu = public_path('tema/uploads/kampanyalar/' . $kampanya->resim);
            if (file_exists($resimYolu)) {
                @unlink($resimYolu);
            }
        }
        
        DB::table('kampanyalar')->where('id', $id)->delete();
        
        return redirect()->route('admin.kampanyalar.index')->with('success', 'Kampanya başarıyla silindi.');
    }
}