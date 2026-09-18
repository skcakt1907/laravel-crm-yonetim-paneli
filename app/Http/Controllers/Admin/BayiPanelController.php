<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\Helpers\BildirimHelper;

class BayiPanelController extends Controller
{
    /**
     * Güvenli bayi bilgisi alma helper metodu
     */
    private function getBayi()
    {
        try {
            return getAuthenticatedBayi(true);
        } catch (\Exception $e) {
            \Log::error('Bayi bilgisi alınamadı', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Bayi Dashboard
     */
    public function dashboard()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı! Lütfen yöneticinizle iletişime geçin.');
            }
        
        // İstatistikler
        try {
            $stats = [
                // Satış istatistikleri
                'toplam_satis' => DB::table('bayi_satislar')->where('bayi_id', $bayi->id)->count(),
                'bu_ay_satis' => DB::table('bayi_satislar')
                    ->where('bayi_id', $bayi->id)
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->count(),
                'bugun_satis' => DB::table('bayi_satislar')
                    ->where('bayi_id', $bayi->id)
                    ->whereDate('created_at', date('Y-m-d'))
                    ->count(),
                
                // Kazanç istatistikleri
                'toplam_kazanc' => DB::table('bayi_satislar')
                    ->where('bayi_id', $bayi->id)
                    ->sum('komisyon_tutari') ?? 0,
                'bu_ay_kazanc' => DB::table('bayi_satislar')
                    ->where('bayi_id', $bayi->id)
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->sum('komisyon_tutari') ?? 0,
                'bekleyen_kazanc' => DB::table('bayi_satislar')
                    ->where('bayi_id', $bayi->id)
                    ->where('odendi', 0)
                    ->sum('komisyon_tutari') ?? 0,
                'odenen_kazanc' => DB::table('bayi_satislar')
                    ->where('bayi_id', $bayi->id)
                    ->where('odendi', 1)
                    ->sum('komisyon_tutari') ?? 0,
                
                // Ödeme talepleri (tablo varsa)
                'bekleyen_odeme' => Schema::hasTable('bayi_odeme_talepleri') 
                    ? DB::table('bayi_odeme_talepleri')
                        ->where('bayi_id', $bayi->id)
                        ->where('durum', 0)
                        ->count()
                    : 0,
                'onaylanan_odeme' => Schema::hasTable('bayi_odeme_talepleri')
                    ? DB::table('bayi_odeme_talepleri')
                        ->where('bayi_id', $bayi->id)
                        ->where('durum', 1)
                        ->count()
                    : 0,
                
                // Müşteri sayısı (fatura_id üzerinden)
                'toplam_musteri' => DB::table('bayi_satislar')
                    ->where('bayi_id', $bayi->id)
                    ->distinct('fatura_id')
                    ->count('fatura_id'),
            ];
        } catch (\Exception $e) {
            // Hata durumunda boş istatistikler döndür
            $stats = [
                'toplam_satis' => 0,
                'bu_ay_satis' => 0,
                'bugun_satis' => 0,
                'toplam_kazanc' => 0,
                'bu_ay_kazanc' => 0,
                'bekleyen_kazanc' => 0,
                'odenen_kazanc' => 0,
                'bekleyen_odeme' => 0,
                'onaylanan_odeme' => 0,
                'toplam_musteri' => 0,
            ];
        }
        
        // Son satışlar
        $son_satislar = DB::table('bayi_satislar')
            ->where('bayi_id', $bayi->id)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        
        // Aylık grafik verisi
        $aylik_satis = DB::table('bayi_satislar')
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as ay'), DB::raw('COUNT(*) as adet'), DB::raw('SUM(komisyon_tutari) as kazanc'))
            ->where('bayi_id', $bayi->id)
            ->whereYear('created_at', date('Y'))
            ->groupBy('ay')
            ->orderBy('ay', 'asc')
            ->get();
        
            // Yönetici bilgisini al
            $yonetici = DB::table('yoneticiler')->where('id', session('admin_id'))->first();
            
            return view('admin.bayi.dashboard', compact('bayi', 'stats', 'son_satislar', 'aylik_satis', 'yonetici'));
        } catch (\Exception $e) {
            \Log::error('Bayi dashboard hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.giris')
                ->with('error', 'Bir hata oluştu. Lütfen tekrar giriş yapın.');
        }
    }
    
    /**
     * Satışlar
     */
    public function satislar()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            $satislar = DB::table('bayi_satislar')
                ->where('bayi_id', $bayi->id)
                ->orderBy('id', 'desc')
                ->paginate(20);
            
            return view('admin.bayi.satislar', compact('satislar', 'bayi'));
        } catch (\Exception $e) {
            \Log::error('Bayi satışlar hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Satışlar yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Kazançlar
     */
    public function kazanclar()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            // Kazanç özeti
            $kazanc_ozeti = [
                'toplam' => DB::table('bayi_satislar')->where('bayi_id', $bayi->id)->sum('komisyon_tutari') ?? 0,
                'odenen' => DB::table('bayi_satislar')->where('bayi_id', $bayi->id)->where('odendi', 1)->sum('komisyon_tutari') ?? 0,
                'bekleyen' => DB::table('bayi_satislar')->where('bayi_id', $bayi->id)->where('odendi', 0)->sum('komisyon_tutari') ?? 0,
            ];
            
            // Aylık kazançlar
            $aylik_kazanclar = DB::table('bayi_satislar')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as ay'),
                    DB::raw('COUNT(*) as satis_adedi'),
                    DB::raw('SUM(komisyon_tutari) as toplam_kazanc')
                )
                ->where('bayi_id', $bayi->id)
                ->groupBy('ay')
                ->orderBy('ay', 'desc')
                ->limit(12)
                ->get();
            
            return view('admin.bayi.kazanclar', compact('bayi', 'kazanc_ozeti', 'aylik_kazanclar'));
        } catch (\Exception $e) {
            \Log::error('Bayi kazançlar hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Kazançlar yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Ödeme Talepleri
     */
    public function odeme()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            // Bekleyen kazanç
            $bekleyen_kazanc = DB::table('bayi_satislar')
                ->where('bayi_id', $bayi->id)
                ->where('odendi', 0)
                ->sum('komisyon_tutari') ?? 0;
            
            // Ödeme talepleri
            $odeme_talepleri = DB::table('bayi_odeme_talepleri')
                ->where('bayi_id', $bayi->id)
                ->orderBy('id', 'desc')
                ->get();
            
            return view('admin.bayi.odeme', compact('bayi', 'bekleyen_kazanc', 'odeme_talepleri'));
        } catch (\Exception $e) {
            \Log::error('Bayi ödeme hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Ödeme bilgileri yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Ödeme Talebi Oluştur Formu (GET)
     */
    public function odemeTalepOlusturForm()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            // Çekilebilir bakiye
            $cekilebilirBakiye = DB::table('bayi_satislar')
                ->where('bayi_id', $bayi->id)
                ->where('odendi', 0)
                ->sum('komisyon_tutari') ?? 0;
            
            return view('admin.bayi.odeme-talep-olustur', compact('bayi', 'cekilebilirBakiye'));
        } catch (\Exception $e) {
            \Log::error('Bayi ödeme talep formu hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Form yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Ödeme Talebi Oluştur (POST)
     */
    public function odemeTalebiOlustur(Request $request)
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            $request->validate([
                'tutar' => 'required|numeric|min:100|max:999999999',
                'aciklama' => 'nullable|string|max:500',
            ]);
            
            $tutar = (float) $request->tutar;
            
            // Negatif tutar kontrolü
            if ($tutar <= 0) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Tutar pozitif bir değer olmalıdır!');
            }
            
            // Bekleyen kazancı kontrol et
            $bekleyen_kazanc = DB::table('bayi_satislar')
                ->where('bayi_id', $bayi->id)
                ->where('odendi', 0)
                ->sum('komisyon_tutari') ?? 0;
            
            if ($tutar > $bekleyen_kazanc) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Talep edilen tutar bekleyen kazançtan fazla olamaz!');
            }
            
            // Form'dan gelen banka bilgilerini de kaydet (kolon varsa)
            $talepData = [
                'bayi_id' => $bayi->id,
                'talep_tutari' => $tutar,
                'tutar' => $tutar,
                'aciklama' => $request->aciklama ?? null,
                'banka_adi' => $request->banka_adi ?? null,
                'iban' => $request->iban ?? null,
                'hesap_sahibi' => $request->hesap_sahibi ?? null,
                'durum' => 'beklemede',
                'talep_tarihi' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $mevcutKolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('bayi_odeme_talepleri');
            $talepData = array_intersect_key($talepData, array_flip($mevcutKolonlar));
            DB::table('bayi_odeme_talepleri')->insert($talepData);

            return redirect()->back()->with('success', 'Ödeme talebiniz başarıyla oluşturuldu!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Bayi ödeme talebi oluşturma hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ödeme talebi oluşturulurken bir hata oluştu.');
        }
    }
    
    /**
     * Müşteriler
     */
    public function musteriler()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            // Bayi'nin müşterileri - önce bayi_musteriler tablosundan, sonra satışlardan
            $musteriIds = [];
            
            // 1. bayi_musteriler tablosundan müşteri ID'lerini al
            if (Schema::hasTable('bayi_musteriler')) {
                $musteriIds = DB::table('bayi_musteriler')
                    ->where('bayi_id', $bayi->id)
                    ->pluck('uye_id')
                    ->toArray();
            }
            
            // 2. Satışlardan müşteri ID'lerini al (fatura üzerinden)
            $satisMusteriIds = [];
            $satislar = DB::table('bayi_satislar')
                ->where('bayi_id', $bayi->id)
                ->whereNotNull('fatura_id')
                ->where('fatura_id', '!=', 0)
                ->pluck('fatura_id')
                ->unique()
                ->toArray();
            
            if (!empty($satislar)) {
                $faturalar = DB::table('faturalar')
                    ->whereIn('id', $satislar)
                    ->pluck('uyeid')
                    ->filter()
                    ->unique()
                    ->toArray();
                
                $satisMusteriIds = array_values($faturalar);
            }
            
            // Tüm müşteri ID'lerini birleştir
            $tumMusteriIds = array_unique(array_merge($musteriIds, $satisMusteriIds));
            
            // Müşterileri çek
            $musteriler = [];
            if (!empty($tumMusteriIds)) {
                $uyeler = DB::table('uyeler')
                    ->whereIn('id', $tumMusteriIds)
                    ->orderBy('id', 'desc')
                    ->get();
                
                // Her müşteri için satış istatistiklerini ekle
                foreach ($uyeler as $uye) {
                    // Bu müşterinin satışlarını bul
                    $faturaIds = DB::table('faturalar')
                        ->where('uyeid', $uye->id)
                        ->pluck('id')
                        ->toArray();
                    
                    $satislar = DB::table('bayi_satislar')
                        ->where('bayi_id', $bayi->id)
                        ->whereIn('fatura_id', $faturaIds)
                        ->get();
                    
                    $musteri = (object) [
                        'uye' => $uye,
                        'satis_adedi' => $satislar->count(),
                        'toplam_kazanc' => $satislar->sum('komisyon_tutari'),
                        'son_satis' => $satislar->max('created_at'),
                    ];
                    
                    $musteriler[] = $musteri;
                }
            }
            
            // Son satışa göre sırala (satışı olmayanlar en sonda)
            usort($musteriler, function($a, $b) {
                if (!$a->son_satis && !$b->son_satis) return 0;
                if (!$a->son_satis) return 1;
                if (!$b->son_satis) return -1;
                return strtotime($b->son_satis) - strtotime($a->son_satis);
            });
            
            return view('admin.bayi.musteriler', compact('bayi', 'musteriler'));
        } catch (\Exception $e) {
            \Log::error('Bayi müşteriler hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Müşteriler yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Profil
     */
    public function profil()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            return view('admin.bayi.profil', compact('bayi'));
        } catch (\Exception $e) {
            \Log::error('Bayi profil hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Profil bilgileri yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Profil Güncelle
     */
    public function profilGuncelle(Request $request)
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            $request->validate([
                'adres' => 'nullable|string|max:500',
                'adres_tarifi' => 'nullable|string|max:500',
                'banka_adi' => 'nullable|string|max:100',
                'iban' => 'nullable|string|max:34|regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/',
                'hesap_sahibi' => 'nullable|string|max:255',
            ]);
            
            DB::table('bayiler')->where('id', $bayi->id)->update([
                'adres' => $request->adres ?? null,
                'adres_tarifi' => $request->adres_tarifi ?? null,
                'banka_adi' => $request->banka_adi ?? null,
                'iban' => $request->iban ? strtoupper(str_replace(' ', '', $request->iban)) : null,
                'hesap_sahibi' => $request->hesap_sahibi ?? null,
                'updated_at' => now(),
            ]);
            
            return redirect()->back()->with('success', 'Profil bilgileriniz güncellendi!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Bayi profil güncelleme hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Profil güncellenirken bir hata oluştu.');
        }
    }
    
    /**
     * Yeni Satış Ekle (Form)
     */
    public function satisEkle()
    {
        // Paketler listesi
        $paketler = DB::table('yazilimlar')
            ->where('durum', 1)
            ->orderBy('adi', 'asc')
            ->get();
        
        return view('admin.bayi.satis.ekle', compact('paketler'));
    }
    
    /**
     * Yeni Satış Ekle (Post)
     */
    public function satisEklePost(Request $request)
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            $request->validate([
                'musteri_adi' => 'required|string|max:255',
                'musteri_email' => 'required|email|max:255',
                'paket_id' => 'required|integer|exists:yazilimlar,id',
                'satis_tutari' => 'required|numeric|min:0|max:999999999',
            ]);
            
            // Paket kontrolü
            $paket = DB::table('yazilimlar')
                ->where('id', $request->paket_id)
                ->where('durum', 1)
                ->first();
            
            if (!$paket) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Seçilen paket bulunamadı veya aktif değil!');
            }
            
            $satisTutari = (float) $request->satis_tutari;
            
            // Komisyon hesapla
            $komisyonOrani = $bayi->komisyon_orani ?? $bayi->komisyon_oran ?? 10;
            $komisyonTutari = ($satisTutari * $komisyonOrani) / 100;
            
            $satisId = DB::table('bayi_satislar')->insertGetId([
                'bayi_id' => $bayi->id,
                'fatura_id' => 0, // Manuel satış
                'satis_tutari' => $satisTutari,
                'komisyon_orani' => $komisyonOrani,
                'komisyon_tutari' => $komisyonTutari,
                'odendi' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Satış bilgisini al ve bildirim gönder
            $satis = DB::table('bayi_satislar')->where('id', $satisId)->first();
            $satis->musteri_adi = $request->musteri_adi;
            
            // Email bildirimi gönder
            try {
                BildirimHelper::yeniSatisBildirimi($bayi->id, $satis);
            } catch (\Exception $e) {
                \Log::warning('Satış bildirimi gönderilemedi', [
                    'satis_id' => $satisId,
                    'error' => $e->getMessage()
                ]);
            }
            
            return redirect()->route('admin.bayi.satislar')
                ->with('success', 'Satış başarıyla eklendi! Bildirim gönderildi.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Bayi satış ekleme hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Satış eklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Satış Detay
     */
    public function satisDetay($id)
    {
        try {
            // Route parametresini validate et
            $id = validateRouteId($id);
            
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            // Satışı kontrol et - sadece bu bayinin satışı olmalı
            $satis = DB::table('bayi_satislar')
                ->where('id', $id)
                ->where('bayi_id', $bayi->id)
                ->first();
            
            if (!$satis) {
                \Log::warning('Yetkisiz satış detay erişim denemesi', [
                    'admin_id' => session('admin_id'),
                    'bayi_id' => $bayi->id,
                    'satis_id' => $id
                ]);
                return redirect()->route('admin.bayi.satislar')
                    ->with('error', 'Satış bulunamadı veya bu satışa erişim yetkiniz yok!');
            }
            
            return view('admin.bayi.satis.detay', compact('satis'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.bayi.satislar')
                ->with('error', 'Geçersiz satış ID!');
        } catch (\Exception $e) {
            \Log::error('Bayi satış detay hatası', [
                'admin_id' => session('admin_id'),
                'satis_id' => $id ?? null,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.satislar')
                ->with('error', 'Satış detayı yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Yeni Müşteri Ekle (Form)
     */
    public function musteriEkle()
    {
        return view('admin.bayi.musteri.ekle');
    }
    
    /**
     * Yeni Müşteri Ekle (Post)
     */
    public function musteriEklePost(Request $request)
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            $request->validate([
                'adi' => 'required|string|max:255',
                'soyad' => 'nullable|string|max:255',
                'email' => 'required|email|max:255|unique:uyeler,email',
                'telefon' => 'nullable|string|max:20',
                'sifre' => 'required|string|min:6|confirmed',
                'adres' => 'nullable|string|max:500',
                'durum' => 'nullable|boolean',
            ], [
                'adi.required' => 'Ad alanı zorunludur.',
                'email.required' => 'E-posta alanı zorunludur.',
                'email.email' => 'Geçerli bir e-posta adresi giriniz.',
                'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
                'sifre.required' => 'Şifre alanı zorunludur.',
                'sifre.min' => 'Şifre en az 6 karakter olmalıdır.',
                'sifre.confirmed' => 'Şifreler eşleşmiyor.',
            ]);
            
            // Müşteri ekle
            $musteriData = [
                'ad' => $request->adi,
                'soyad' => $request->soyad ?? null,
                'email' => $request->email,
                'telefon' => $request->telefon ?? null,
                'sifre' => \Illuminate\Support\Facades\Hash::make($request->sifre),
                'durum' => $request->has('durum') ? 1 : 0,
                'bakiye' => '0',
                'tarih' => now()->format('Y-m-d H:i:s'),
                'ktarih' => now()->format('Y-m-d H:i:s'),
            ];
            
            // Adres kolonu varsa ekle
            if (Schema::hasColumn('uyeler', 'adres')) {
                $musteriData['adres'] = $request->adres ?? null;
            } else {
                // Adres yoksa notlar kolonuna ekle
                if ($request->adres) {
                    $musteriData['notlar'] = 'Adres: ' . $request->adres;
                }
            }
            
            $musteriId = DB::table('uyeler')->insertGetId($musteriData);

            // Yönetim panelindeki CRM müşteri listesi bu tablodan beslendiği için
            // bayi tarafından eklenen müşteriyi CRM'e de senkronize et.
            if (!empty($request->email) && !Customer::where('email', $request->email)->exists()) {
                $adiSoyadi = trim(($request->adi ?? '') . ' ' . ($request->soyad ?? ''));
                if ($adiSoyadi === '') {
                    $adiSoyadi = $request->email;
                }

                Customer::create([
                    'adi' => $adiSoyadi,
                    'unvan' => null,
                    'email' => $request->email,
                    'telefon' => $request->telefon ?? null,
                    'durum' => ($request->has('durum') ? 'aktif' : 'pasif'),
                    'kaynak' => 'Bayi Paneli',
                    'sorumlu_id' => session('admin_id'),
                    'adres' => $request->adres ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Bayi-müşteri ilişkisini kaydet (eğer tablo varsa)
            if (Schema::hasTable('bayi_musteriler')) {
                DB::table('bayi_musteriler')->insert([
                    'bayi_id' => $bayi->id,
                    'uye_id' => $musteriId,
                    'created_at' => now(),
                ]);
            }
            
            return redirect()->route('admin.bayi.musteriler')
                ->with('success', 'Müşteri başarıyla eklendi!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Bayi müşteri ekleme hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Müşteri eklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Müşteri Detay
     */
    public function musteriDetay($id)
    {
        try {
            // Route parametresini validate et
            $id = validateRouteId($id);
            
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            // Müşteri bilgisi
            $musteri = DB::table('uyeler')->where('id', $id)->first();
            
            if (!$musteri) {
                return redirect()->route('admin.bayi.musteriler')
                    ->with('error', 'Müşteri bulunamadı!');
            }
            
            // Müşterinin satışları - SADECE bu bayinin satışları
            $satislar = DB::table('bayi_satislar')
                ->join('faturalar', 'bayi_satislar.fatura_id', '=', 'faturalar.id')
                ->where('faturalar.uyeid', $id)
                ->where('bayi_satislar.bayi_id', $bayi->id) // KRİTİK: Bayi kontrolü
                ->select('bayi_satislar.*')
                ->get();
            
            // Eğer bu müşterinin bu bayiden satışı yoksa, yetkisiz erişim
            if ($satislar->isEmpty()) {
                \Log::warning('Yetkisiz müşteri detay erişim denemesi', [
                    'admin_id' => session('admin_id'),
                    'bayi_id' => $bayi->id,
                    'musteri_id' => $id
                ]);
                return redirect()->route('admin.bayi.musteriler')
                    ->with('error', 'Bu müşteriye erişim yetkiniz yok!');
            }
            
            return view('admin.bayi.musteri.detay', compact('musteri', 'satislar'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.bayi.musteriler')
                ->with('error', 'Geçersiz müşteri ID!');
        } catch (\Exception $e) {
            \Log::error('Bayi müşteri detay hatası', [
                'admin_id' => session('admin_id'),
                'musteri_id' => $id ?? null,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.musteriler')
                ->with('error', 'Müşteri detayı yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Ödeme Talepleri
     */
    public function odemeTalepleri()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            // Ödeme talepleri
            $talepler = DB::table('bayi_odeme_talepleri')
                ->where('bayi_id', $bayi->id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Çekilebilir bakiye
            $cekilebilirBakiye = DB::table('bayi_satislar')
                ->where('bayi_id', $bayi->id)
                ->where('odendi', 0)
                ->sum('komisyon_tutari') ?? 0;
            
            return view('admin.bayi.odeme', compact('talepler', 'cekilebilirBakiye', 'bayi'));
        } catch (\Exception $e) {
            \Log::error('Bayi ödeme talepleri hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Ödeme talepleri yüklenirken bir hata oluştu.');
        }
    }
}


