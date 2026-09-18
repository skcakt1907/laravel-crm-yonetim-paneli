<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BayiReferansController extends Controller
{
    /**
     * Güvenli bayi bilgisi alma helper metodu
     */
    private function getBayi()
    {
        try {
            return getAuthenticatedBayi(true);
        } catch (\Exception $e) {
            \Log::error('Bayi bilgisi alınamadı (Referans)', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Referans Link ve QR Kod
     */
    public function link()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
        
        // Referans linki oluştur
        $referansLink = url('/kayit?ref=' . ($bayi->bayi_kodu ?? 'BAY0001'));
        
        // QR kod oluştur (Google Charts API kullanarak - paket gerektirmez)
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($referansLink);
        $qrCode = null; // View'da direkt URL kullanılacak
        
        // Referans istatistikleri
        $bayiKodu = $bayi->bayi_kodu ?? 'BAY0001';
        
        $stats = [
            'toplam_tiklanma' => Schema::hasTable('referans_tiklanmalar') 
                ? DB::table('referans_tiklanmalar')->where('bayi_kodu', $bayiKodu)->count() 
                : 0,
            'toplam_kayit' => Schema::hasTable('referans_kayitlari') 
                ? DB::table('referans_kayitlari')->where('bayi_kodu', $bayiKodu)->count() 
                : 0,
            'aktif_musteri' => Schema::hasTable('referans_kayitlari') 
                ? DB::table('referans_kayitlari')
                    ->where('referans_kayitlari.bayi_kodu', $bayiKodu)
                    ->where('referans_kayitlari.durum', 1)
                    ->join('uyeler', 'referans_kayitlari.uye_id', '=', 'uyeler.id')
                    ->where('uyeler.durum', 1)
                    ->count() 
                : 0,
            'toplam_kazanc' => Schema::hasTable('referans_kayitlari') 
                ? (float) DB::table('referans_kayitlari')->where('bayi_kodu', $bayiKodu)->sum('kazanc') 
                : 0,
        ];
        
            return view('admin.bayi.referans.link', compact('bayi', 'referansLink', 'qrCodeUrl', 'stats'));
        } catch (\Exception $e) {
            \Log::error('Bayi referans link hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Referans linki yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Referans İstatistikleri
     */
    public function istatistik()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
        
        $bayiKodu = $bayi->bayi_kodu ?? 'BAY0001';
        
        // Aylık referans istatistikleri
        $aylikReferans = [];
        if (Schema::hasTable('referans_kayitlari')) {
            $aylikReferans = DB::table('referans_kayitlari')
                ->select(
                    DB::raw('YEAR(kayit_tarihi) as yil'),
                    DB::raw('MONTH(kayit_tarihi) as ay'),
                    DB::raw('COUNT(*) as kayit_sayisi'),
                    DB::raw('SUM(kazanc) as toplam_kazanc')
                )
                ->where('bayi_kodu', $bayiKodu)
                ->groupBy('yil', 'ay')
                ->orderBy('yil', 'desc')
                ->orderBy('ay', 'desc')
                ->get();
        }
        
        // Referanslar listesi (son kayıtlar)
        $referanslar = [];
        $toplamKazanc = 0;
        $aktifMusteriSayisi = 0;
        if (Schema::hasTable('referans_kayitlari')) {
            $referanslar = DB::table('referans_kayitlari')
                ->join('uyeler', 'referans_kayitlari.uye_id', '=', 'uyeler.id')
                ->select(
                    'referans_kayitlari.*',
                    'uyeler.ad',
                    'uyeler.soyad',
                    'uyeler.email',
                    'uyeler.durum as uye_durum'
                )
                ->where('referans_kayitlari.bayi_kodu', $bayiKodu)
                ->orderBy('referans_kayitlari.kayit_tarihi', 'desc')
                ->paginate(20);
            
            // Toplam kazancı hesapla (tüm kayıtlar için)
            $toplamKazanc = (float) DB::table('referans_kayitlari')
                ->where('bayi_kodu', $bayiKodu)
                ->sum('kazanc') ?? 0;
            
            // Aktif müşteri sayısını hesapla
            $aktifMusteriSayisi = DB::table('referans_kayitlari')
                ->where('referans_kayitlari.bayi_kodu', $bayiKodu)
                ->where('referans_kayitlari.durum', 1)
                ->join('uyeler', 'referans_kayitlari.uye_id', '=', 'uyeler.id')
                ->where('uyeler.durum', 1)
                ->count();
        }
        
            return view('admin.bayi.referans.istatistik', compact('bayi', 'aylikReferans', 'referanslar', 'toplamKazanc', 'aktifMusteriSayisi'));
        } catch (\Exception $e) {
            \Log::error('Bayi referans istatistik hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Referans istatistikleri yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Alt Bayiler
     */
    public function altBayiler()
    {
        try {
            $bayi = $this->getBayi();
            
            if (!$bayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
        
        // Alt bayiler listesi
        // Önce tablo kolonlarını kontrol et
        $columns = Schema::hasTable('bayiler') ? Schema::getColumnListing('bayiler') : [];
        
        $altBayiler = collect([]);
        
        if (in_array('ust_bayi_id', $columns)) {
            $altBayiler = DB::table('bayiler')
                ->where('ust_bayi_id', $bayi->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif (in_array('parent_id', $columns)) {
            $altBayiler = DB::table('bayiler')
                ->where('parent_id', $bayi->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif (in_array('ust_bayi', $columns)) {
            $altBayiler = DB::table('bayiler')
                ->where('ust_bayi', $bayi->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        // Alt bayiler için üye bilgilerini ekle
        $altBayilerWithUye = $altBayiler->map(function($altBayi) {
            $uye = DB::table('uyeler')->where('id', $altBayi->uye_id ?? 0)->first();
            $altBayi->uye = $uye;
            return $altBayi;
        });
        
            return view('admin.bayi.referans.alt-bayiler', compact('bayi', 'altBayiler', 'altBayilerWithUye'));
        } catch (\Exception $e) {
            \Log::error('Bayi alt bayiler hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->route('admin.bayi.dashboard')
                ->with('error', 'Alt bayiler yüklenirken bir hata oluştu.');
        }
    }
    
    /**
     * Alt Bayi Ekle
     */
    public function altBayiEkle(Request $request)
    {
        try {
            $ustBayi = $this->getBayi();
            
            if (!$ustBayi) {
                return redirect()->route('admin.giris')
                    ->with('error', 'Bayi kaydınız bulunamadı!');
            }
            
            $request->validate([
                'adi' => 'required|string|max:255',
                'soyadi' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:uyeler,email',
                'telefon' => 'required|string|max:20',
                'tc' => 'nullable|string|size:11',
            ]);
        
        // Önce üye oluştur
        $uyeId = DB::table('uyeler')->insertGetId([
            'ad' => $request->adi,
            'soyad' => $request->soyadi,
            'email' => $request->email,
            'telefon' => $request->telefon,
            'tc' => $request->tc ?? null,
            'durum' => 0, // Onay bekliyor
            'bakiye' => 0,
            'tarih' => now(),
            'ip' => $request->ip(),
            'utipi' => 0,
        ]);
        
        // Bayi kodu oluştur
        $sonBayiNo = DB::table('bayiler')->max('id') ?? 0;
        $yeniBayiNo = $sonBayiNo + 1;
        $bayiKodu = 'BAY' . str_pad($yeniBayiNo, 4, '0', STR_PAD_LEFT);
        
            // Alt bayi kaydı oluştur
            $columns = Schema::hasTable('bayiler') ? Schema::getColumnListing('bayiler') : [];
            $bayiData = [
                'uye_id' => $uyeId,
                'bayi_kodu' => $bayiKodu,
                'onay_durumu' => 0,
                'komisyon_orani' => $ustBayi->komisyon_orani ?? $ustBayi->komisyon_oran ?? 10,
                'created_at' => now(),
            ];
            
            if (in_array('ust_bayi_id', $columns)) {
                $bayiData['ust_bayi_id'] = $ustBayi->id;
            } elseif (in_array('parent_id', $columns)) {
                $bayiData['parent_id'] = $ustBayi->id;
            } elseif (in_array('ust_bayi', $columns)) {
                $bayiData['ust_bayi'] = $ustBayi->id;
            }
        
            DB::table('bayiler')->insert($bayiData);
            
            return redirect()->route('admin.bayi.alt.bayiler')
                ->with('success', 'Alt bayi başvurusu oluşturuldu! Onay bekliyor.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Alt bayi ekleme hatası', [
                'admin_id' => session('admin_id'),
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Alt bayi eklenirken bir hata oluştu.');
        }
    }
}

