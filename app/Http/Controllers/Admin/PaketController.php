<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class PaketController extends Controller
{
    /**
     * Demo linkini normalize eder:
     *  - boşsa null
     *  - http(s):// veya site-göreli "/..." yolları olduğu gibi bırakılır
     *  - "demo.site.com/x" gibi şemasız tam adreslere https:// eklenir
     * Böylece hem göreli yollar (/temalar/...) hem de dış adresler bozulmadan kaydedilir;
     * type=url doğrulama tuzağına takılmaz.
     */
    private function normalizeDemoUrl($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        // Zaten şemalı ya da site-göreli yol ise dokunma
        if (preg_match('~^(https?://|/)~i', $value)) {
            return $value;
        }
        // Şemasız tam adres (ör. www.demo.com / demo.site.com/x)
        return 'https://' . $value;
    }

    public function index(Request $request)
    {
        $arama = trim((string) $request->get('q'));
        $kategori = $request->get('kategori');
        $durum = $request->get('durum');
        $hasBaslikColumn = Schema::hasColumn('yazilimlar', 'baslik');

        $query = DB::table('yazilimlar')
            ->leftJoin('web_kategori', 'yazilimlar.kategori', '=', 'web_kategori.id')
            ->select('yazilimlar.*', 'web_kategori.adi as kategori_adi');

        // Sadece ana dil (TR) kayıtlarını listele (dil kolonu varsa)
        if (Schema::hasColumn('yazilimlar', 'dil')) {
            $query->where('yazilimlar.dil', 1);
        }

        // Müşteriye özel teklif olmayan genel web paketleri
        if (Schema::hasColumn('yazilimlar', 'musteri')) {
            $query->where('yazilimlar.musteri', 0);
        }

        if ($arama !== '') {
            $query->where(function($q) use ($arama, $hasBaslikColumn) {
                $q->where('yazilimlar.adi', 'like', "%{$arama}%");

                if ($hasBaslikColumn) {
                    $q->orWhere('yazilimlar.baslik', 'like', "%{$arama}%");
                }

                $q->orWhere('yazilimlar.kisa', 'like', "%{$arama}%")
                  ->orWhere('yazilimlar.aciklama', 'like', "%{$arama}%")
                  ->orWhere('web_kategori.adi', 'like', "%{$arama}%");
            });
        }

        if ($kategori !== null && $kategori !== '') {
            $query->where('yazilimlar.kategori', (int) $kategori);
        }

        if ($durum !== null && $durum !== '') {
            $query->where('yazilimlar.durum', (int) $durum);
        }

        $paketler = $query
            ->orderBy('yazilimlar.sira', 'asc')
            ->orderBy('yazilimlar.id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $ozelListe = false;
        $kategoriler = DB::table('web_kategori')->where('durum', 1)->orderBy('sira')->get();

        return view('admin.paketler.index', compact('paketler', 'arama', 'kategori', 'durum', 'ozelListe', 'kategoriler'));
    }

    /**
     * Anasayfada gosterilen paketleri yonetme ekrani.
     * Tum aktif paketleri listeler, anasayfa=1 olanlari isaretli gosterir.
     */
    public function anasayfaPaketleri(Request $request)
    {
        if (!Schema::hasColumn('yazilimlar', 'anasayfa')) {
            return redirect()->route('admin.paketler.index')->with('error', '"anasayfa" kolonu yok.');
        }

        $arama = trim((string) $request->get('q'));

        $q = DB::table('yazilimlar')
            ->select('id', 'adi', 'seo', 'durum', 'anasayfa', 'sira')
            ->where('durum', 1);

        if (Schema::hasColumn('yazilimlar', 'dil')) {
            $q->where('dil', 1);
        }
        if (Schema::hasColumn('yazilimlar', 'musteri')) {
            $q->where('musteri', 0);
        }
        if ($arama !== '') {
            $q->where('adi', 'like', "%{$arama}%");
        }

        $paketler = $q->orderByDesc('anasayfa')
            ->orderBy('sira', 'asc')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $secilenSayisi = DB::table('yazilimlar')->where('anasayfa', 1)->where('durum', 1)->count();

        return view('admin.paketler.anasayfa', compact('paketler', 'arama', 'secilenSayisi'));
    }

    public function anasayfaPaketleriKaydet(Request $request)
    {
        if (!Schema::hasColumn('yazilimlar', 'anasayfa')) {
            return redirect()->route('admin.paketler.index')->with('error', '"anasayfa" kolonu yok.');
        }

        $secilenler = collect($request->input('anasayfa', []))
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        // Sayfada listelenen ID'leri al ki sadece onlarin uzerinde islem yapalim
        // (sayfalama varsa diger sayfalardaki secimler bozulmasin).
        $sayfaIdleri = collect($request->input('listelenen', []))
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        if (empty($sayfaIdleri)) {
            return back()->with('error', 'Listelenen paket bulunamadi.');
        }

        // Once bu sayfadakileri sifirla, sonra secilenleri 1 yap
        DB::table('yazilimlar')->whereIn('id', $sayfaIdleri)->update(['anasayfa' => 0]);
        if (!empty($secilenler)) {
            DB::table('yazilimlar')->whereIn('id', $secilenler)->update(['anasayfa' => 1]);
        }

        \Cache::forget('homepage_packages'); // ileride cache eklenirse
        return back()->with('success', count($secilenler) . ' paket anasayfada gosterilecek sekilde guncellendi.');
    }

    /**
     * Müşterilere özel teklif paketleri listesi
     */
    public function ozelTeklifler(Request $request)
    {
        $arama = trim((string) $request->get('q'));
        $kategori = $request->get('kategori');
        $durum = $request->get('durum');
        $hasBaslikColumn = Schema::hasColumn('yazilimlar', 'baslik');

        $query = DB::table('yazilimlar')
            ->leftJoin('web_kategori', 'yazilimlar.kategori', '=', 'web_kategori.id')
            ->select('yazilimlar.*', 'web_kategori.adi as kategori_adi');

        // Sadece ana dil (TR) kayıtlarını listele (dil kolonu varsa)
        if (Schema::hasColumn('yazilimlar', 'dil')) {
            $query->where('yazilimlar.dil', 1);
        }

        // Sadece müşteriye özel teklifler:
        // - musteri != 0 olanlar veya
        // - başlığında/seo'sunda "teklif" geçenler
        if (Schema::hasColumn('yazilimlar', 'musteri')) {
            $query->where(function ($q) {
                $q->where('yazilimlar.musteri', '<>', 0)
                  ->orWhere('yazilimlar.adi', 'like', '%teklif%')
                  ->orWhere('yazilimlar.seo', 'like', '%teklif%');
            });
        } else {
            $query->where(function ($q) {
                $q->where('yazilimlar.adi', 'like', '%teklif%')
                  ->orWhere('yazilimlar.seo', 'like', '%teklif%');
            });
        }

        if ($arama !== '') {
            $query->where(function($q) use ($arama, $hasBaslikColumn) {
                $q->where('yazilimlar.adi', 'like', "%{$arama}%");

                if ($hasBaslikColumn) {
                    $q->orWhere('yazilimlar.baslik', 'like', "%{$arama}%");
                }

                $q->orWhere('yazilimlar.kisa', 'like', "%{$arama}%")
                  ->orWhere('yazilimlar.aciklama', 'like', "%{$arama}%")
                  ->orWhere('web_kategori.adi', 'like', "%{$arama}%");
            });
        }

        if ($kategori !== null && $kategori !== '') {
            $query->where('yazilimlar.kategori', (int) $kategori);
        }

        if ($durum !== null && $durum !== '') {
            $query->where('yazilimlar.durum', (int) $durum);
        }

        $paketler = $query
            ->orderBy('yazilimlar.sira', 'asc')
            ->orderBy('yazilimlar.id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $ozelListe = true;

        // Formdan oluşturulan gerçek müşteri teklifleri (paket_teklifleri)
        $paketTeklifleri = collect();
        if (Schema::hasTable('paket_teklifleri')) {
            $ptQuery = DB::table('paket_teklifleri')
                ->leftJoin('uyeler', 'paket_teklifleri.uye_id', '=', 'uyeler.id')
                ->select('paket_teklifleri.*', 'uyeler.ad as uye_ad', 'uyeler.soyad as uye_soyad', 'uyeler.email as uye_email');

            if ($arama !== '') {
                $ptQuery->where(function ($q) use ($arama) {
                    $q->where('paket_teklifleri.baslik', 'like', "%{$arama}%")
                      ->orWhere('uyeler.ad', 'like', "%{$arama}%")
                      ->orWhere('uyeler.soyad', 'like', "%{$arama}%")
                      ->orWhere('uyeler.email', 'like', "%{$arama}%");
                });
            }

            $paketTeklifleri = $ptQuery->orderByDesc('paket_teklifleri.id')->get();
        }

        return view('admin.paketler.index', compact('paketler', 'arama', 'kategori', 'durum', 'ozelListe', 'paketTeklifleri'));
    }
    
    public function ekle()
    {
        $kategoriler = DB::table('web_kategori')->where('durum', 1)->get();
        return view('admin.paketler.ekle', compact('kategoriler'));
    }
    
    public function eklePost(Request $request)
    {
        try {
            $request->validate([
                'adi' => 'required_without:baslik|string|max:255',
                'baslik' => 'required_without:adi|string|max:255',
                'tutar' => 'required_without:fiyat|numeric|min:0',
                'fiyat' => 'required_without:tutar|numeric|min:0',
                'para_birimi' => 'nullable|string|in:TL,TRY,USD,EUR,AED',
                'kategori' => 'required_without:kid|nullable|array',
                'kategori.*' => 'integer',
                'kid' => 'required_without:kategori|nullable|integer',
                // İçerik görselleri: en fazla 5 adet, her biri max 10MB
                'icerik_resimleri' => 'nullable|array|max:5',
                'icerik_resimleri.*' => 'image|max:10240',
            ]);
        
        $title = $request->input('adi') ?? $request->input('baslik');
        $rawPrice = $request->input('tutar') ?? $request->input('fiyat');
        $currency = strtoupper($request->input('para_birimi', 'TL'));
        
        // Fiyatı her zaman TL olarak sakla; gerekirse dövizden TL'ye çevir
        if (in_array($currency, ['USD', 'EUR', 'AED'])) {
            $price = \App\Helpers\DovizKuruHelper::tlYeCevir($rawPrice, $currency);
        } else {
            $price = (float) $rawPrice;
        }
        $category = $request->input('kategori') ?? $request->input('kid');
        $shortDescription = $request->input('kisa') ?? $request->input('ozet');
        $features = $request->input('ozellik') ?? $request->input('ozellikler');
        $description = $request->input('aciklama') ?? '';
        $content = $request->input('icerik') ?? '';
        $instructions = $request->input('talimat') ?? '';
        
        $seo = Str::slug($title);
        
        // Resim yükleme (Kapak görseli) - mümkünse WEBP olarak kaydet
        $resim = null;
        if ($request->hasFile('resim')) {
            try {
                $file = $request->file('resim');
                
                // Güvenlik kontrolleri
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $extension = strtolower($file->getClientOriginalExtension());
                
                if (!in_array($extension, $allowedExtensions)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Geçersiz dosya formatı. Sadece JPG, PNG, GIF, WEBP ve SVG dosyaları yüklenebilir.');
                }
                
                // Dosya boyutu kontrolü (max 10MB)
                $maxSize = 10 * 1024 * 1024; // 10MB
                if ($file->getSize() > $maxSize) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Dosya boyutu çok büyük. Maksimum 10MB olmalıdır.');
                }
                
                // MIME type kontrolü
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Geçersiz dosya tipi.');
                }

                $uploadDir = public_path('tema/uploads/webpaketleri');
                
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }
                
                $baseName = time() . '_' . Str::slug($title) . '_' . Str::random(8);
                $resim = $this->storeImageAsWebp($file, $uploadDir, $baseName);
            } catch (\Exception $e) {
                \Log::error('Paket resim yükleme hatası', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Resim yüklenirken bir hata oluştu: ' . $e->getMessage());
            }
        }
        
        $baseData = [];
        
        // Sadece mevcut kolonları ekle
        if (Schema::hasColumn('yazilimlar', 'adi')) {
            $baseData['adi'] = $title;
        }
        if (Schema::hasColumn('yazilimlar', 'baslik')) {
            $baseData['baslik'] = $title;
        }
        if (Schema::hasColumn('yazilimlar', 'seo')) {
            $baseData['seo'] = $seo;
        }
        if (Schema::hasColumn('yazilimlar', 'kisa') && $shortDescription) {
            $baseData['kisa'] = $shortDescription;
        }
        if (Schema::hasColumn('yazilimlar', 'aciklama') && $description) {
            $baseData['aciklama'] = $description;
        }
        if (Schema::hasColumn('yazilimlar', 'icerik') && $content) {
            $baseData['icerik'] = $content;
        }
        if (Schema::hasColumn('yazilimlar', 'talimat') && $instructions) {
            $baseData['talimat'] = $instructions;
        }
        if (Schema::hasColumn('yazilimlar', 'ozellik') && $features) {
            $baseData['ozellik'] = $features;
        }
        if (Schema::hasColumn('yazilimlar', 'ozellikler') && $features) {
            $baseData['ozellikler'] = $features;
        }
        // Manuel çeviriler: EN
        if (Schema::hasColumn('yazilimlar', 'adi_en') && $request->filled('adi_en')) {
            $baseData['adi_en'] = $request->input('adi_en');
        }
        if (Schema::hasColumn('yazilimlar', 'kisa_en') && $request->filled('kisa_en')) {
            $baseData['kisa_en'] = $request->input('kisa_en');
        }
        if (Schema::hasColumn('yazilimlar', 'aciklama_en') && $request->filled('aciklama_en')) {
            $baseData['aciklama_en'] = $request->input('aciklama_en');
        }
        if (Schema::hasColumn('yazilimlar', 'icerik_en') && $request->filled('icerik_en')) {
            $baseData['icerik_en'] = $request->input('icerik_en');
        }
        if (Schema::hasColumn('yazilimlar', 'talimat_en') && $request->filled('talimat_en')) {
            $baseData['talimat_en'] = $request->input('talimat_en');
        }
        if (Schema::hasColumn('yazilimlar', 'ozellik_en') && $request->filled('ozellik_en')) {
            $baseData['ozellik_en'] = $request->input('ozellik_en');
        }
        if (Schema::hasColumn('yazilimlar', 'ozellikler_en') && $request->filled('ozellikler_en')) {
            $baseData['ozellikler_en'] = $request->input('ozellikler_en');
        }
        // Manuel çeviriler: AR
        if (Schema::hasColumn('yazilimlar', 'adi_ar') && $request->filled('adi_ar')) {
            $baseData['adi_ar'] = $request->input('adi_ar');
        }
        if (Schema::hasColumn('yazilimlar', 'kisa_ar') && $request->filled('kisa_ar')) {
            $baseData['kisa_ar'] = $request->input('kisa_ar');
        }
        if (Schema::hasColumn('yazilimlar', 'aciklama_ar') && $request->filled('aciklama_ar')) {
            $baseData['aciklama_ar'] = $request->input('aciklama_ar');
        }
        if (Schema::hasColumn('yazilimlar', 'icerik_ar') && $request->filled('icerik_ar')) {
            $baseData['icerik_ar'] = $request->input('icerik_ar');
        }
        if (Schema::hasColumn('yazilimlar', 'talimat_ar') && $request->filled('talimat_ar')) {
            $baseData['talimat_ar'] = $request->input('talimat_ar');
        }
        if (Schema::hasColumn('yazilimlar', 'ozellik_ar') && $request->filled('ozellik_ar')) {
            $baseData['ozellik_ar'] = $request->input('ozellik_ar');
        }
        if (Schema::hasColumn('yazilimlar', 'ozellikler_ar') && $request->filled('ozellikler_ar')) {
            $baseData['ozellikler_ar'] = $request->input('ozellikler_ar');
        }
        if (Schema::hasColumn('yazilimlar', 'tutar')) {
            $baseData['tutar'] = $price;
        }
        if (Schema::hasColumn('yazilimlar', 'fiyat')) {
            $baseData['fiyat'] = $price;
        }
        // Kategori (artık tek seçim - primary category)
        $primaryCategory = is_array($category) ? reset($category) : $category;

        if (Schema::hasColumn('yazilimlar', 'kategori') && $primaryCategory) {
            $baseData['kategori'] = $primaryCategory;
        }
        if (Schema::hasColumn('yazilimlar', 'kid') && $primaryCategory) {
            $baseData['kid'] = $primaryCategory;
        }
        if (Schema::hasColumn('yazilimlar', 'resim') && $resim) {
            $baseData['resim'] = $resim;
        }
        if (Schema::hasColumn('yazilimlar', 'demo_link')) {
            $baseData['demo_link'] = $this->normalizeDemoUrl($request->input('demo_link'));
        }
        if (Schema::hasColumn('yazilimlar', 'demo_admin_link')) {
            $baseData['demo_admin_link'] = $this->normalizeDemoUrl($request->input('demo_admin_link'));
        }
        if (Schema::hasColumn('yazilimlar', 'sira')) {
            $baseData['sira'] = $request->input('sira', 0);
        }
        if (Schema::hasColumn('yazilimlar', 'durum')) {
            $baseData['durum'] = $request->has('durum') ? 1 : 0;
        }
        if (Schema::hasColumn('yazilimlar', 'anasayfa')) {
            $baseData['anasayfa'] = $request->has('anasayfa') ? 1 : 0;
        }
        if (Schema::hasColumn('yazilimlar', 'dil')) {
            $baseData['dil'] = 1;
        }
        
        if (Schema::hasColumn('yazilimlar', 'created_at')) {
            $baseData['created_at'] = now();
        }
        if (Schema::hasColumn('yazilimlar', 'updated_at')) {
            $baseData['updated_at'] = now();
        }
        
        try {
            $paketId = DB::table('yazilimlar')->insertGetId($baseData);
            \App\Models\Ceviri::sync('yazilimlar', $request->input('ceviriler', []), $paketId);
        } catch (\Exception $e) {
            \Log::error('Paket ekleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $baseData
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Paket eklenirken bir hata oluştu: ' . $e->getMessage());
        }
        
        // İçerik görselleri (galeri) - webpaketresim tablosuna kaydet
        if ($request->hasFile('icerik_resimleri')) {
            $galeriDosyalari = $request->file('icerik_resimleri');
            $maxGorselAdedi = 5;

            // Güvenlik için, backend tarafında da en fazla $maxGorselAdedi dosyayı işle
            if (is_array($galeriDosyalari) && count($galeriDosyalari) > $maxGorselAdedi) {
                $galeriDosyalari = array_slice($galeriDosyalari, 0, $maxGorselAdedi);
            }
            $hedefKlasor = public_path('tema/uploads/webpaketleri');
            
            if (!is_dir($hedefKlasor)) {
                @mkdir($hedefKlasor, 0775, true);
            }
            
            foreach ($galeriDosyalari as $index => $gorsel) {
                if (!$gorsel->isValid()) {
                    continue;
                }
                
                // Güvenlik kontrolleri
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $extension = strtolower($gorsel->getClientOriginalExtension());
                
                if (!in_array($extension, $allowedExtensions)) {
                    continue; // Geçersiz dosya, atla
                }
                
                // Dosya boyutu kontrolü (max 10MB)
                $maxSize = 10 * 1024 * 1024;
                if ($gorsel->getSize() > $maxSize) {
                    continue; // Çok büyük dosya, atla
                }
                
                // MIME type kontrolü
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                if (!in_array($gorsel->getMimeType(), $allowedMimes)) {
                    continue; // Geçersiz MIME type, atla
                }

                $baseName = time() . '_' . $paketId . '_' . $index . '_' . Str::random(6);
                
                try {
                    $dosyaAdi = $this->storeImageAsWebp($gorsel, $hedefKlasor, $baseName);
                } catch (\Exception $e) {
                    \Log::warning('Galeri resmi yükleme hatası', [
                        'error' => $e->getMessage(),
                        'index' => $index
                    ]);
                    continue;
                }
                
                // webpaketresim tablosu varsa kaydet
                if (Schema::hasTable('webpaketresim')) {
                    DB::table('webpaketresim')->insert([
                        'rid' => $paketId,
                        'resim' => $dosyaAdi,
                    ]);
                }
            }
        }
        
        // Otomatik çeviri kullanılmıyor; tüm diller manuel yönetilecek
        
        return redirect()->route('admin.paketler.index')->with('success', 'Paket başarıyla eklendi.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Paket ekleme genel hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Paket eklenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function duzenle($id)
    {
        $paket = DB::table('yazilimlar')->where('id', $id)->first();
        
        if (!$paket) {
            return redirect()->route('admin.paketler.index')->with('error', 'Paket bulunamadı!');
        }
        
        // Ekle sayfası ile tutarlı olması için kategorileri web_kategori tablosundan cek
        $kategoriler = DB::table('web_kategori')->where('durum', 1)->get();

        // Mevcut içerik görsellerini al
        $icerikResimleri = [];
        if (Schema::hasTable('webpaketresim')) {
            $icerikResimleri = DB::table('webpaketresim')->where('rid', $id)->get();
        }

        // Index ile aynı sıraya göre (sira ASC, id DESC) tüm ID'leri al
        $idsQuery = DB::table('yazilimlar');
        if (Schema::hasColumn('yazilimlar', 'dil')) {
            $idsQuery->where('dil', 1);
        }
        $ids = $idsQuery
            ->orderBy('sira', 'asc')
            ->orderBy('id', 'desc')
            ->pluck('id')
            ->toArray();

        $prevPaket = null;
        $nextPaket = null;

        // ID'ler dizide integer, route parametresi string gelebileceği için
        // strict olmayan arama kullan
        $currentIndex = array_search($id, $ids);
        if ($currentIndex !== false) {
            if ($currentIndex > 0) {
                $prevId = $ids[$currentIndex - 1];
                $prevPaket = DB::table('yazilimlar')->where('id', $prevId)->first();
            }
            if ($currentIndex < count($ids) - 1) {
                $nextId = $ids[$currentIndex + 1];
                $nextPaket = DB::table('yazilimlar')->where('id', $nextId)->first();
            }
        }
        
        return view('admin.paketler.duzenle', compact('paket', 'kategoriler', 'prevPaket', 'nextPaket', 'icerikResimleri'));
    }
    
    public function duzenlePost(Request $request, $id)
    {
        try {
            // ID validation
            if (!is_numeric($id) || $id <= 0) {
                return redirect()->route('admin.paketler.index')->with('error', 'Geçersiz paket ID.');
            }
            
            $request->validate([
            'adi' => 'required_without:baslik|string|max:255',
            'baslik' => 'required_without:adi|string|max:255',
            'tutar' => 'required_without:fiyat|numeric|min:0',
            'fiyat' => 'required_without:tutar|numeric|min:0',
            'kategori' => 'nullable|array',
            'kategori.*' => 'integer',
            // İçerik görselleri: en fazla 5 adet, her biri max 10MB
            'icerik_resimleri' => 'nullable|array|max:5',
            'icerik_resimleri.*' => 'image|max:10240',
        ]);
        
        $paket = DB::table('yazilimlar')->where('id', $id)->first();
        
        if (!$paket) {
            return redirect()->route('admin.paketler.index')->with('error', 'Paket bulunamadı!');
        }
        
        // Başlık ve fiyatı belirle (eklePost ile uyumlu)
        $title = $request->input('baslik') ?? $request->input('adi');
        $rawPrice = $request->input('tutar') ?? $request->input('fiyat');
        $seo = Str::slug($title);
        
        // Kategori: Eğer array geliyorsa ilkini al, değilse direkt değeri al
        $kategoriId = null;
        if ($request->has('kategori') && is_array($request->kategori) && count($request->kategori) > 0) {
            $kategoriId = $request->kategori[0];
        } elseif ($request->has('kategori')) {
            $kategoriId = $request->kategori;
        } elseif ($request->has('kid')) {
            $kategoriId = $request->kid;
        }
        
        // Demo linkleri: kullanıcı http(s):// yazmasa da normalize et (type=url doğrulama tuzağını önler)
        $demoLink      = $this->normalizeDemoUrl($request->input('demo_link'));
        $demoAdminLink = $this->normalizeDemoUrl($request->input('demo_admin_link'));

        $updateData = [
            'adi' => $title,
            'baslik' => $title,
            'seo' => $seo,
            'kisa' => $request->input('kisa') ?? $paket->kisa,
            'aciklama' => $request->aciklama,
            'icerik' => $request->icerik,
            'talimat' => $request->input('talimat') ?? $paket->talimat,
            'ozellik' => $request->input('ozellik') ?? $request->ozellikler,
            'ozellikler' => $request->input('ozellik') ?? $request->ozellikler,
            'tutar' => $rawPrice,
            'fiyat' => $rawPrice,
            'kategori' => $kategoriId,
            'kid' => $kategoriId,
            'demo_link' => $demoLink,
            'demo_admin_link' => $demoAdminLink,
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
            'anasayfa' => $request->anasayfa ?? 0,
        ];

        // Schema'da olmayan kolonları temizle (örn. baslik / fiyat / kid / icerik / talimat / ozellikler)
        $updateData = array_filter(
            $updateData,
            fn ($k) => Schema::hasColumn('yazilimlar', $k),
            ARRAY_FILTER_USE_KEY
        );
        
        // Resim sil - "delete_resim" checkbox işaretli ise mevcut resmi kaldır
        if ($request->boolean('delete_resim') && !empty($paket->resim)) {
            $stored = (string) $paket->resim;
            foreach ([
                public_path('tema/uploads/webpaketleri/kapak/' . $stored),
                public_path('tema/uploads/webpaketleri/' . $stored),
                public_path('tema/uploads/webpaketleri/kucuk/' . $stored),
            ] as $p) {
                if (is_file($p)) { @unlink($p); }
            }
            $updateData['resim'] = null;
        }

        // Resim yükleme - mümkünse WEBP olarak kaydet
        if ($request->hasFile('resim')) {
            // Eski resimleri farklı olası lokasyonlardan temizle (geriye dönük uyum)
            if (!empty($paket->resim)) {
                $stored = (string) $paket->resim;
                $candidates = [];

                // Eğer DB'de path tutuluyorsa direkt onu dene
                if (str_contains($stored, '/') || str_contains($stored, '\\')) {
                    $clean = ltrim(str_replace('\\', '/', $stored), '/');
                    $candidates[] = public_path($clean);
                    $stored = basename($clean);
                }

                // Standart aranan klasörler (tema kökü ve public kökü)
                $candidates[] = base_path('tema/uploads/webpaketleri/kapak/' . $stored);
                $candidates[] = base_path('tema/uploads/webpaketleri/' . $stored);
                $candidates[] = base_path('tema/uploads/webpaketleri/kucuk/' . $stored);
                $candidates[] = base_path('tema/uploads/paketler/' . $stored); // eski admin upload yolu
                $candidates[] = public_path('tema/uploads/webpaketleri/kapak/' . $stored);
                $candidates[] = public_path('tema/uploads/webpaketleri/' . $stored);
                $candidates[] = public_path('tema/uploads/webpaketleri/kucuk/' . $stored);
                $candidates[] = public_path('tema/uploads/paketler/' . $stored); // eski admin upload yolu

                foreach (array_unique($candidates) as $p) {
                    if ($p && file_exists($p)) {
                        @unlink($p);
                    }
                }
            }
            
            try {
                $file = $request->file('resim');
                
                // Güvenlik kontrolleri
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $extension = strtolower($file->getClientOriginalExtension());
                
                if (!in_array($extension, $allowedExtensions)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Geçersiz dosya formatı. Sadece JPG, PNG, GIF, WEBP ve SVG dosyaları yüklenebilir.');
                }
                
                // Dosya boyutu kontrolü (max 10MB)
                $maxSize = 10 * 1024 * 1024;
                if ($file->getSize() > $maxSize) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Dosya boyutu çok büyük. Maksimum 10MB olmalıdır.');
                }
                
                // MIME type kontrolü
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Geçersiz dosya tipi.');
                }
                
                $baseName = time() . '_' . Str::slug($request->baslik ?? $paket->adi ?? 'paket') . '_' . Str::random(8);
                $uploadDir = public_path('tema/uploads/webpaketleri');
                
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }
                
                $updateData['resim'] = $this->storeImageAsWebp($file, $uploadDir, $baseName);
            } catch (\Exception $e) {
                \Log::error('Paket resim güncelleme hatası', [
                    'id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Resim yüklenirken bir hata oluştu: ' . $e->getMessage());
            }
        }
        
        // İşlem geçmişi için: güncelleme ÖNCESİ satırın hâlini al (geri-al altyapısı)
        $eskiSatir = (array) (DB::table('yazilimlar')->where('id', $id)->first() ?? []);

        try {
            DB::table('yazilimlar')->where('id', $id)->update($updateData);
            \App\Models\Ceviri::sync('yazilimlar', $request->input('ceviriler', []), (int) $id);

            // Değişen alanları işlem geçmişine yaz (geri alınabilir)
            $degEski = []; $degYeni = [];
            foreach ($updateData as $kol => $yeniDeg) {
                $eskiDeg = $eskiSatir[$kol] ?? null;
                if ((string) $eskiDeg !== (string) $yeniDeg) {
                    $degEski[$kol] = $eskiDeg;
                    $degYeni[$kol] = $yeniDeg;
                }
            }
            if ($degEski) {
                \App\Services\IslemGecmisi::kaydet(
                    'guncelle',
                    "'".($eskiSatir['adi'] ?? ('#'.$id))."' paketi düzenlendi (".count($degEski)." alan)",
                    [['tablo' => 'yazilimlar', 'id' => (int) $id, 'eski' => $degEski, 'yeni' => $degYeni]],
                    'manuel'
                );
            }
        } catch (\Exception $e) {
            \Log::error('Paket veritabanı güncelleme hatası', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Paket güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
        
        // İçerik görselleri (galeri) - webpaketresim tablosuna kaydet
        if ($request->hasFile('icerik_resimleri')) {
            $galeriDosyalari = $request->file('icerik_resimleri');
            $maxGorselAdedi = 5;

            // Güvenlik için, backend tarafında da en fazla $maxGorselAdedi dosyayı işle
            if (is_array($galeriDosyalari) && count($galeriDosyalari) > $maxGorselAdedi) {
                $galeriDosyalari = array_slice($galeriDosyalari, 0, $maxGorselAdedi);
            }
            $hedefKlasor = public_path('tema/uploads/webpaketleri');
            
            if (!is_dir($hedefKlasor)) {
                @mkdir($hedefKlasor, 0775, true);
            }
            
            foreach ($galeriDosyalari as $index => $gorsel) {
                if (!$gorsel->isValid()) {
                    continue;
                }
                
                // Güvenlik kontrolleri
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $extension = strtolower($gorsel->getClientOriginalExtension());
                
                if (!in_array($extension, $allowedExtensions)) {
                    continue; // Geçersiz dosya, atla
                }
                
                // Dosya boyutu kontrolü (max 10MB)
                $maxSize = 10 * 1024 * 1024;
                if ($gorsel->getSize() > $maxSize) {
                    continue; // Çok büyük dosya, atla
                }
                
                // MIME type kontrolü
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                if (!in_array($gorsel->getMimeType(), $allowedMimes)) {
                    continue; // Geçersiz MIME type, atla
                }
                
                $baseName = time() . '_' . $id . '_' . $index . '_' . Str::random(6);
                
                try {
                    $dosyaAdi = $this->storeImageAsWebp($gorsel, $hedefKlasor, $baseName);
                } catch (\Exception $e) {
                    \Log::warning('Galeri resmi güncelleme hatası', [
                        'error' => $e->getMessage(),
                        'id' => $id,
                        'index' => $index
                    ]);
                    continue;
                }
                
                // webpaketresim tablosu varsa kaydet
                if (Schema::hasTable('webpaketresim')) {
                    DB::table('webpaketresim')->insert([
                        'rid' => $id,
                        'resim' => $dosyaAdi,
                    ]);
                }
            }
        }
        
        // Listeye dönmek yerine aynı düzenleme ekranında kal
        return redirect()
            ->route('admin.paketler.duzenle', $id)
            ->with('success', 'Paket başarıyla güncellendi.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Paket güncelleme genel hatası', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Paket güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function sil($id)
    {
        $paket = DB::table('yazilimlar')->where('id', $id)->first();
        
        if (!$paket) {
            return redirect()->route('admin.paketler.index')->with('error', 'Paket bulunamadı!');
        }
        
        // Resmi sil
        if (!empty($paket->resim)) {
            $stored = (string) $paket->resim;
            $candidates = [];

            if (str_contains($stored, '/') || str_contains($stored, '\\')) {
                $clean = ltrim(str_replace('\\', '/', $stored), '/');
                $candidates[] = public_path($clean);
                $stored = basename($clean);
            }

            $candidates[] = public_path('tema/uploads/webpaketleri/kapak/' . $stored);
            $candidates[] = public_path('tema/uploads/webpaketleri/' . $stored);
            $candidates[] = public_path('tema/uploads/webpaketleri/kucuk/' . $stored);
            $candidates[] = public_path('tema/uploads/paketler/' . $stored);

            foreach (array_unique($candidates) as $p) {
                if ($p && file_exists($p)) {
                    @unlink($p);
                }
            }
        }
        
        // Paket resimlerini sil
        $paketResimleri = DB::table('webpaketresim')->where('rid', $id)->get();
        foreach ($paketResimleri as $resim) {
            if ($resim->resim && file_exists(public_path($resim->resim))) {
                unlink(public_path($resim->resim));
            }
        }
        DB::table('webpaketresim')->where('rid', $id)->delete();
        
        DB::table('yazilimlar')->where('id', $id)->delete();
        
        return redirect()->route('admin.paketler.index')->with('success', 'Paket silindi.');
    }

    /**
     * Sepette "Önerilen Paket" durumunu aç/kapat (sepet_oner 1<->0).
     * Sepet öneri sisteminde öne çıkacak paketler bununla işaretlenir.
     */
    public function sepetOnerToggle($id)
    {
        if (!\Schema::hasColumn('yazilimlar', 'sepet_oner')) {
            return redirect()->back()->with('error', '"sepet_oner" kolonu yok. Önce SQL kurulumunu çalıştırın.');
        }

        $paket = DB::table('yazilimlar')->where('id', $id)->first();
        if (!$paket) {
            return redirect()->route('admin.paketler.index')->with('error', 'Paket bulunamadı!');
        }

        $yeni = ((int) ($paket->sepet_oner ?? 0) === 1) ? 0 : 1;
        DB::table('yazilimlar')->where('id', $id)->update(['sepet_oner' => $yeni]);

        $mesaj = $yeni === 1
            ? '"' . ($paket->adi ?? 'Paket') . '" sepette önerilenlere eklendi.'
            : '"' . ($paket->adi ?? 'Paket') . '" sepet önerilerinden çıkarıldı.';

        return redirect()->back()->with('success', $mesaj);
    }
    
    public function icerikResimSil($id)
    {
        try {
            $resim = DB::table('webpaketresim')->where('id', $id)->first();
            
            if (!$resim) {
                return response()->json(['success' => false, 'message' => 'Görsel bulunamadı'], 404);
            }
            
            // Fiziksel dosyayı sil
            $dosyaYolu = public_path('tema/uploads/webpaketleri/' . $resim->resim);
            if (file_exists($dosyaYolu)) {
                @unlink($dosyaYolu);
            }
            
            // Veritabanından sil
            DB::table('webpaketresim')->where('id', $id)->delete();
            
            return response()->json(['success' => true, 'message' => 'Görsel başarıyla silindi']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * TinyMCE için resim yükleme
     */
    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            ]);
            
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $baseName = time() . '_' . Str::random(10);
                $uploadDir = public_path('tema/uploads/editor');
                $filename = ImageHelper::saveAsWebp($file, $uploadDir, $baseName);
                $url = asset('tema/uploads/editor/' . $filename);

                return response()->json([
                    'location' => $url
                ]);
            }
            
            return response()->json(['error' => 'Dosya yüklenemedi'], 400);
        } catch (\Exception $e) {
            \Log::error('Editor resim yükleme hatası', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Resim yüklenirken bir hata oluştu'], 500);
        }
    }

    /**
     * Yüklenen görselleri mümkünse WEBP olarak diske kaydeder.
     * SVG veya başarısız dönüşüm durumunda orijinal uzantı ile kayda devam eder.
     */
    protected function storeImageAsWebp(UploadedFile $file, string $uploadDir, string $baseName): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        // SVG'ler dönüştürülmez, direkt taşınır
        if ($extension === 'svg') {
            $filename = $baseName . '.svg';
            $file->move($uploadDir, $filename);
            @chmod($uploadDir . DIRECTORY_SEPARATOR . $filename, 0644);
            return $filename;
        }

        // WEBP veya dönüştürülebilir tipler için WEBP'e çevir
        $canConvert = function_exists('imagewebp') && in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
        $targetFilename = $baseName . '.webp';
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetFilename;

        if ($canConvert) {
            $sourcePath = $file->getRealPath();
            $image = null;

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = @imagecreatefromjpeg($sourcePath);
                    break;
                case 'png':
                    $image = @imagecreatefrompng($sourcePath);
                    break;
                case 'gif':
                    $image = @imagecreatefromgif($sourcePath);
                    break;
                case 'webp':
                    $image = @imagecreatefromwebp($sourcePath);
                    break;
            }

            if ($image) {
                if (function_exists('imagepalettetotruecolor')) {
                    @imagepalettetotruecolor($image);
                }
                @imagealphablending($image, true);
                @imagesavealpha($image, true);

                if (@imagewebp($image, $targetPath, 85)) {
                    @imagedestroy($image);
                    @chmod($targetPath, 0644);
                    return $targetFilename;
                }

                @imagedestroy($image);
            }
        }

        // Dönüşüm başarısızsa orijinal uzantı ile kaydet
        $fallbackFilename = $baseName . '.' . $extension;
        $file->move($uploadDir, $fallbackFilename);
        @chmod($uploadDir . DIRECTORY_SEPARATOR . $fallbackFilename, 0644);
        return $fallbackFilename;
    }
}