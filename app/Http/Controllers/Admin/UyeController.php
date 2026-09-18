<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UyeController extends Controller
{
    public function index(Request $request)
    {
        $arama = trim((string) $request->get('q'));
        $durum = $request->get('durum');

        $query = DB::table('uyeler');

        if ($arama !== '') {
            // Sadece tabloda GERÇEKTEN var olan kolonlarda ara (yoksa SQL patlamasın).
            $kolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('uyeler');
            $aranabilir = array_values(array_intersect(
                ['ad', 'soyad', 'adi_soyadi', 'email', 'eposta', 'telefon', 'gsm', 'firmaadi', 'firma'],
                $kolonlar
            ));
            $query->where(function ($q) use ($arama, $aranabilir, $kolonlar) {
                foreach ($aranabilir as $kolon) {
                    $q->orWhere($kolon, 'like', "%{$arama}%");
                }
                // ad + soyad birleşik arama (ikisi de varsa)
                if (in_array('ad', $kolonlar) && in_array('soyad', $kolonlar)) {
                    $q->orWhere(DB::raw("CONCAT(COALESCE(ad,''),' ',COALESCE(soyad,''))"), 'like', "%{$arama}%");
                }
            });
        }

        if ($durum !== null && $durum !== '') {
            $query->where('durum', (int) $durum);
        }

        $uyeler = $query
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();
        
        return view('admin.uyeler.index', compact('uyeler', 'arama', 'durum'));
    }
    
    public function ekle()
    {
        return view('admin.uyeler.ekle');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'ad' => 'required|string|max:255',
            'soyad' => 'required|string|max:255',
            'email' => 'required|email|unique:uyeler,email',
            'sifre' => 'required|string|min:6',
        ], [
            'ad.required' => 'Ad alanı zorunludur.',
            'soyad.required' => 'Soyad alanı zorunludur.',
            'email.required' => 'E-posta alanı zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'sifre.required' => 'Parola alanı zorunludur.',
            'sifre.min' => 'Parola en az 6 karakter olmalıdır.',
        ]);
        
        try {
            $data = [
                'ad' => $request->ad,
                'soyad' => $request->soyad,
                'email' => $request->email,
                'telefon' => $request->telefon,
                'sifre' => Hash::make($request->sifre),
                'utipi' => $request->utipi ?? 0,
                'tc' => $request->tc,
                'dtarih' => $request->dtarih,
                'firmaadi' => $request->firmaadi,
                'vergino' => $request->vergino,
                'vergidairesi' => $request->vergidairesi,
                'cinsiyet' => $request->cinsiyet ?? 'Erkek',
                'il' => $request->il,
                'ilce' => $request->ilce,
                'pkodu' => $request->pkodu,
                'adres' => $request->adres,
                'durum' => $request->durum ?? 1,
                'bakiye' => 0,
                'tarih' => date('Y-m-d H:i:s'),
                'ktarih' => date('Y-m-d H:i:s'),
            ];

            // Sadece tabloda var olan kolonları yaz (il/ilce/pkodu/adres bu tabloda yok)
            $mevcutKolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('uyeler');
            $data = array_intersect_key($data, array_flip($mevcutKolonlar));

            $uyeId = DB::table('uyeler')->insertGetId($data);

            // ÇİFT YÖN SENKRON: admin'den eklenen üyeyi crm_customers'a da düşür (duplicate önlemeli).
            try {
                $yeniUye = DB::table('uyeler')->where('id', $uyeId)->first();
                if ($yeniUye) {
                    \App\Http\Controllers\AuthController::crmMusteriyeSenkronla($yeniUye);
                }
            } catch (\Throwable $e) {
                \Log::warning('Admin üye ekleme -> crm senkron hatası', ['err' => $e->getMessage()]);
            }

            // 📧 Hoşgeldin maili (admin checkbox işaretlemişse)
            if ($request->has('mail_gonder') && $request->mail_gonder == 1) {
                try {
                    \App\Services\CustomerNotifier::hosgeldin($uyeId);
                } catch (\Throwable $e) {
                    \Log::warning('Hoşgeldin maili gönderilemedi', ['err' => $e->getMessage()]);
                }
            }

            // 🔔 Admin bildirimi (her yeni üyede: uygulama içi bildirim + admin maili)
            try {
                \App\Services\CustomerNotifier::yeniUyeAdminBildirim($uyeId);
            } catch (\Throwable $e) {
                \Log::warning('Yeni üye admin bildirimi gönderilemedi', ['err' => $e->getMessage()]);
            }
            
            return redirect()->route('admin.uyeler.index')->with('success', 'Müşteri başarıyla eklendi!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function detay($id)
    {
        $uye = DB::table('uyeler')->where('id', $id)->first();

        if (!$uye) {
            return redirect()->route('admin.uyeler.index')->with('error', 'Üye bulunamadı!');
        }

        // Faturalar
        $faturalar = DB::table('faturalar')
            ->where('uyeid', $id)
            ->orderBy('id', 'desc')
            ->get();

        // Satin alinanlar
        $satinalmalar = collect([]);
        if (\Illuminate\Support\Facades\Schema::hasTable('satilanlar')) {
            $satinalmalar = DB::table('satilanlar')
                ->where('uyeid', $id)
                ->orderBy('id', 'desc')
                ->get();
        }

        // Destek talepleri (sadece ana talepler)
        $destekler = DB::table('destek')
            ->where('uyeid', $id)
            ->where('ustid', 0)
            ->orderBy('id', 'desc')
            ->get();

        // Destek yanitlari (admin cevaplari)
        $destekYanitSayisi = DB::table('destek')
            ->where('uyeid', $id)
            ->where('ustid', '>', 0)
            ->count();

        // Musteri logu - tum islemleri tarihe gore birlesik goster
        $loglar = collect();

        foreach ($faturalar as $f) {
            $loglar->push((object)[
                'tarih' => $f->created_at ?? $f->tarih ?? null,
                'tip' => 'fatura',
                'ikon' => 'mdi-file-document',
                'renk' => '#3b82f6',
                'baslik' => 'Fatura kesildi',
                'detay' => ($f->spno ?? '#' . $f->id) . ' - ' . ($f->durum ?? ''),
                'tutar' => $f->toplam ?? $f->tutar ?? null,
            ]);
        }

        foreach ($satinalmalar as $s) {
            $loglar->push((object)[
                'tarih' => $s->created_at ?? $s->baslangic_tarihi ?? null,
                'tip' => 'satis',
                'ikon' => 'mdi-cart-check',
                'renk' => '#10b981',
                'baslik' => 'Satin alma',
                'detay' => $s->adi ?? '',
                'tutar' => $s->fiyat ?? null,
            ]);
        }

        foreach ($destekler as $d) {
            $cevapVar = DB::table('destek')->where('ustid', $d->id)->exists();
            $loglar->push((object)[
                'tarih' => $d->created_at ?? $d->tarih ?? null,
                'tip' => 'destek',
                'ikon' => $cevapVar ? 'mdi-check-circle' : 'mdi-help-circle',
                'renk' => $cevapVar ? '#10b981' : '#f59e0b',
                'baslik' => 'Destek talebi: ' . ($d->baslik ?? ''),
                'detay' => $cevapVar ? 'Yanitlandi' : 'Yanit bekleniyor',
                'tutar' => null,
            ]);
        }

        $loglar = $loglar->sortByDesc('tarih')->values();

        return view('admin.uyeler.detay', compact('uye', 'faturalar', 'satinalmalar', 'destekler', 'destekYanitSayisi', 'loglar'));
    }
    
    public function durumDegistir($id, $durum)
    {
        // String → integer
        $durumMap = ['aktif' => 1, 'pasif' => 0, 'engelli' => 2, 'iptal' => 2];
        $durumInt = $durumMap[$durum] ?? (is_numeric($durum) ? (int) $durum : 1);

        $oncekiDurum = (int) (DB::table('uyeler')->where('id', $id)->value('durum') ?? 0);

        DB::table('uyeler')->where('id', $id)->update([
            'durum' => $durumInt,
        ]);

        // 📧 Durum değişikliği bildirimi (aktif↔pasif/engelli geçişinde)
        if ($oncekiDurum !== $durumInt) {
            try {
                if ($durumInt === 1) {
                    \App\Services\CustomerNotifier::hesapAktifEdildi($id);
                } elseif ($durumInt === 0 || $durumInt === 2) {
                    \App\Services\CustomerNotifier::hesapEngellendi($id);
                }
            } catch (\Throwable $e) {
                \Log::warning('Durum bildirimi gönderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', 'Üye durumu güncellendi.');
    }
    
    public function sifreGuncelle(Request $request, $id)
    {
        $request->validate([
            'yeni_sifre' => 'required|string|min:6',
        ]);
        
        DB::table('uyeler')->where('id', $id)->update([
            'sifre' => Hash::make($request->yeni_sifre),
        ]);

        // 🔒 Güvenlik bildirimi
        try {
            \App\Services\CustomerNotifier::sifreDegisti($id, $request->ip());
        } catch (\Throwable $e) {
            \Log::warning('Şifre değişim maili gönderilemedi', ['err' => $e->getMessage()]);
        }
        
        return redirect()->back()->with('success', 'Şifre güncellendi.');
    }
    
    public function sil($id)
    {
        // Üyeye ait verileri kontrol et
        $faturaVar = DB::table('faturalar')->where('uyeid', $id)->exists();
        
        if ($faturaVar) {
            return redirect()->back()->with('error', 'Bu üyeye ait faturalar var. Önce faturaları silmelisiniz!');
        }
        
        DB::table('uyeler')->where('id', $id)->delete();
        
        return redirect()->route('admin.uyeler.index')->with('success', 'Üye silindi.');
    }
    
    /**
     * Tüm üyeleri Excel'e aktar
     */
    public function export()
    {
        $uyeler = DB::table('uyeler')->get();
        
        $filename = 'uyeler_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // UTF-8 BOM ekle (Excel için)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Başlıklar
        fputcsv($handle, [
            'Ad', 'Soyad', 'Email', 'Telefon', 'Şifre', 'Üye Tipi', 'TC Kimlik No', 'Doğum Tarihi',
            'Firma Adı', 'Vergi No', 'Vergi Dairesi', 'Cinsiyet', 'İl', 'İlçe', 'Posta Kodu', 'Adres',
            'Durum', 'Bakiye', 'Kayıt Tarihi'
        ], ';'); // Excel için noktalı virgül
        
        // Veriler
        foreach ($uyeler as $uye) {
            fputcsv($handle, [
                $uye->ad ?? '',
                $uye->soyad ?? '',
                $uye->email ?? '',
                $uye->telefon ?? '',
                '', // Şifre export edilmez (güvenlik)
                $uye->utipi ?? 0,
                $uye->tc ?? $uye->tc_no ?? '',
                $uye->dtarih ?? '',
                $uye->firmaadi ?? '',
                $uye->vergino ?? '',
                $uye->vergidairesi ?? '',
                $uye->cinsiyet ?? 'Erkek',
                $uye->il ?? $uye->sehir ?? '',
                $uye->ilce ?? '',
                $uye->pkodu ?? '',
                $uye->adres ?? '',
                $uye->durum ?? 1,
                $uye->bakiye ?? 0,
                $uye->tarih ?? $uye->ktarih ?? '',
            ], ';');
        }
        
        fclose($handle);
        exit;
    }
    
    /**
     * Sayfadaki üyeleri Excel'e aktar
     */
    public function exportPage(Request $request)
    {
        // Sayfalama parametrelerini al
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $uyeler = DB::table('uyeler')
            ->orderBy('id', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        $filename = 'uyeler_sayfa_' . $page . '_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // UTF-8 BOM ekle (Excel için)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Başlıklar
        fputcsv($handle, [
            'Ad', 'Soyad', 'Email', 'Telefon', 'Şifre', 'Üye Tipi', 'TC Kimlik No', 'Doğum Tarihi',
            'Firma Adı', 'Vergi No', 'Vergi Dairesi', 'Cinsiyet', 'İl', 'İlçe', 'Posta Kodu', 'Adres',
            'Durum', 'Bakiye', 'Kayıt Tarihi'
        ], ';'); // Excel için noktalı virgül
        
        // Veriler
        foreach ($uyeler as $uye) {
            fputcsv($handle, [
                $uye->ad ?? '',
                $uye->soyad ?? '',
                $uye->email ?? '',
                $uye->telefon ?? '',
                '', // Şifre export edilmez (güvenlik)
                $uye->utipi ?? 0,
                $uye->tc ?? $uye->tc_no ?? '',
                $uye->dtarih ?? '',
                $uye->firmaadi ?? '',
                $uye->vergino ?? '',
                $uye->vergidairesi ?? '',
                $uye->cinsiyet ?? 'Erkek',
                $uye->il ?? $uye->sehir ?? '',
                $uye->ilce ?? '',
                $uye->pkodu ?? '',
                $uye->adres ?? '',
                $uye->durum ?? 1,
                $uye->bakiye ?? 0,
                $uye->tarih ?? $uye->ktarih ?? '',
            ], ';');
        }
        
        fclose($handle);
        exit;
    }
}