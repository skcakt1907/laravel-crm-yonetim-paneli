<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BayiController extends Controller
{
    public function ekle(Request $request)
    {
        $varsayilanKomisyon = 10;
        $varsayilanMusteriIndirim = 5;
        $varsayilanMinSepet = 0;

        if (Schema::hasTable('bayi_ayarlari')) {
            $ayar = DB::table('bayi_ayarlari')->first();
            if ($ayar) {
                $varsayilanKomisyon = (float) ($ayar->varsayilan_komisyon_orani ?? 10);
                $varsayilanMusteriIndirim = (float) ($ayar->varsayilan_musteri_indirim_orani ?? 5);
                $varsayilanMinSepet = (float) ($ayar->min_sepet_tutari ?? 0);
            }
        }

        $uyeler = DB::table('uyeler')
            ->leftJoin('bayiler', 'uyeler.id', '=', 'bayiler.uye_id')
            ->whereNull('bayiler.uye_id')
            ->select('uyeler.id', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email', 'uyeler.telefon')
            ->orderBy('uyeler.ad')
            ->orderBy('uyeler.soyad')
            ->get();

        $presecilenUyeId = $request->integer('uye_id') ?: null;
        $crmId = $request->integer('crm_id') ?: null;

        // CRM'den gelen müşteri bilgilerini ön doldur
        $crmMusteri = null;
        if ($crmId) {
            $crmMusteri = DB::table('crm_customers')->find($crmId);
        }

        return view('admin.bayiler.ekle', compact(
            'uyeler',
            'varsayilanKomisyon',
            'varsayilanMusteriIndirim',
            'varsayilanMinSepet',
            'presecilenUyeId',
            'crmId',
            'crmMusteri'
        ));
    }
    
    /**
     * AJAX — Bayi olmayan üyeler arasında arama
     * GET /admin/bayiler/ara-uye?q=ahmet
     */
    public function aramaUye(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $uyeler = DB::table('uyeler')
            ->leftJoin('bayiler', 'uyeler.id', '=', 'bayiler.uye_id')
            ->whereNull('bayiler.uye_id')
            ->where(function ($w) use ($q) {
                $w->where('uyeler.ad', 'like', "%{$q}%")
                  ->orWhere('uyeler.soyad', 'like', "%{$q}%")
                  ->orWhere('uyeler.email', 'like', "%{$q}%")
                  ->orWhere('uyeler.telefon', 'like', "%{$q}%")
                  ->orWhereRaw("CONCAT(uyeler.ad, ' ', uyeler.soyad) LIKE ?", ["%{$q}%"]);
            })
            ->select('uyeler.id', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email', 'uyeler.telefon')
            ->orderBy('uyeler.ad')
            ->orderBy('uyeler.soyad')
            ->limit(20)
            ->get();

        $items = $uyeler->map(function ($u) {
            $ad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? '')) ?: '—';
            return [
                'id'      => $u->id,
                'ad'      => $ad,
                'email'   => $u->email ?? '',
                'telefon' => $u->telefon ?? '',
                'harf'    => mb_strtoupper(mb_substr($u->ad ?? '?', 0, 1, 'UTF-8'), 'UTF-8'),
            ];
        });

        return response()->json(['items' => $items]);
    }
    public function eklePost(Request $request)
    {
        if (!$request->filled('kayit_tipi')) {
            $request->merge(['kayit_tipi' => $request->filled('uye_id') ? 'mevcut' : 'yeni']);
        }
        $validator = Validator::make($request->all(), [
            'kayit_tipi' => 'required|in:mevcut,yeni',
            'uye_id' => 'nullable|integer|exists:uyeler,id|unique:bayiler,uye_id|required_if:kayit_tipi,mevcut',
            'yeni_ad' => 'nullable|string|max:255|required_if:kayit_tipi,yeni',
            'yeni_soyad' => 'nullable|string|max:255',
            'yeni_email' => 'nullable|email|max:255|unique:uyeler,email|required_if:kayit_tipi,yeni',
            'yeni_telefon' => 'nullable|string|max:30',
            'yeni_sifre' => 'nullable|string|min:6|required_if:kayit_tipi,yeni',
            'yeni_kullanici_adi' => 'nullable|string|min:3|max:50',
            'bayi_kodu' => 'nullable|string|max:20|unique:bayiler,bayi_kodu',
            'komisyon_orani' => 'required|numeric|min:0|max:100',
            'pesin_komisyon_orani' => 'nullable|numeric|min:0|max:100',
            'vadeli_komisyon_orani' => 'nullable|numeric|min:0|max:100',
            'musteri_indirim_orani' => 'nullable|numeric|min:0|max:100',
            'min_sepet_tutari' => 'nullable|numeric|min:0',
            'durum' => 'nullable|boolean',
            'onay_durumu' => 'nullable|boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with('error', $validator->errors()->first());
        }
        
        $validated = $validator->validated();

        if ($validated['kayit_tipi'] === 'yeni') {
            if (empty($validated['yeni_ad']) || empty($validated['yeni_email']) || empty($validated['yeni_sifre'])) {
                return redirect()->back()->withInput()->with('error', 'Yeni hesap için ad, e-posta ve şifre zorunludur.');
            }

            $uyeData = [
                'ad' => $validated['yeni_ad'],
                'soyad' => $validated['yeni_soyad'] ?? null,
                'email' => $validated['yeni_email'],
                'telefon' => $validated['yeni_telefon'] ?? null,
                'sifre' => Hash::make($validated['yeni_sifre']),
                'durum' => $request->boolean('durum', true) ? 1 : 0,
                'bakiye' => 0,
                'tarih' => now()->format('Y-m-d H:i:s'),
                'ktarih' => now()->format('Y-m-d H:i:s'),
                'ip' => $request->ip(),
                'utipi' => 0,
            ];

            if (Schema::hasColumn('uyeler', 'kullanici_adi') && !empty($validated['yeni_kullanici_adi'])) {
                if (DB::table('uyeler')->where('kullanici_adi', $validated['yeni_kullanici_adi'])->exists()) {
                    return redirect()->back()->withInput()->with('error', 'Bu kullanıcı adı zaten kullanılıyor.');
                }
                $uyeData['kullanici_adi'] = $validated['yeni_kullanici_adi'];
            }

            // Farklı veritabanı şemalarında olmayan kolonları otomatik ele.
            $uyeColumns = Schema::getColumnListing('uyeler');
            foreach (array_keys($uyeData) as $key) {
                if (!in_array($key, $uyeColumns, true)) {
                    unset($uyeData[$key]);
                }
            }

            $uyeId = DB::table('uyeler')->insertGetId($uyeData);
            $uye = DB::table('uyeler')->where('id', $uyeId)->first();
        } else {
            if (empty($validated['uye_id'])) {
                return redirect()->back()->withInput()->with('error', 'Lütfen mevcut bir üye seçin.');
            }
            $uye = DB::table('uyeler')->where('id', $validated['uye_id'])->first();
            if (!$uye) {
                return redirect()->back()->withInput()->with('error', 'Üye bulunamadı.');
            }
        }

        $bayiKodu = trim((string) ($validated['bayi_kodu'] ?? ''));
        if ($bayiKodu === '') {
            do {
                $bayiKodu = strtoupper(Str::random(8));
            } while (DB::table('bayiler')->where('bayi_kodu', $bayiKodu)->exists());
        }

        $data = [
            'uye_id' => $uye->id,
            'bayi_kodu' => $bayiKodu,
            'komisyon_orani' => $validated['komisyon_orani'],
            'pesin_komisyon_orani' => $validated['pesin_komisyon_orani'] ?? 0,
            'vadeli_komisyon_orani' => $validated['vadeli_komisyon_orani'] ?? 0,
            'musteri_indirim_orani' => $validated['musteri_indirim_orani'] ?? 5,
            'min_sepet_tutari' => $validated['min_sepet_tutari'] ?? 0,
            'il'  => $request->input('il'),
            'ilce' => $request->input('ilce'),
            'durum' => $request->boolean('durum', true) ? 1 : 0,
            'onay_durumu' => $request->boolean('onay_durumu', true) ? 1 : 0,
            'onay_tarihi' => $request->boolean('onay_durumu', true) ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Bayiler tablosunda olmayan alanları düşür (şema farklarına tolerans).
        $bayiColumns = Schema::getColumnListing('bayiler');
        foreach (array_keys($data) as $key) {
            if (!in_array($key, $bayiColumns, true)) {
                unset($data[$key]);
            }
        }

        // Bayi girişinin çalışması için yönetici (rol=3) kaydı ile bağla.
        if (Schema::hasColumn('bayiler', 'yonetici_id')) {
            $yonetici = DB::table('yoneticiler')
                ->where(function ($q) use ($uye) {
                    $q->where('email', $uye->email)
                        ->orWhere('eposta', $uye->email)
                        ->orWhere('kullaniciadi', $uye->email);
                })
                ->where('rol', 3)
                ->first();

            if (!$yonetici) {
                $kullaniciAdi = $uye->email;
                if (DB::table('yoneticiler')->where('kullaniciadi', $kullaniciAdi)->exists()) {
                    $kullaniciAdi = 'bayi_' . $uye->id;
                }

                $yoneticiColumns = Schema::getColumnListing('yoneticiler');
                $yoneticiInsert = [
                    'adi' => trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: ('Bayi ' . $uye->id),
                    'kullaniciadi' => $kullaniciAdi,
                    'email' => $uye->email,
                    'eposta' => $uye->email,
                    'sifre' => Hash::make(Str::random(16)),
                    'rol' => 3,
                    'durum' => $request->boolean('durum', true) ? 1 : 0,
                    'yetki' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Bazı kurulumlarda bu kolonlar olmayabiliyor, sadece var olanları yaz.
                if (!in_array('email', $yoneticiColumns, true)) unset($yoneticiInsert['email']);
                if (!in_array('eposta', $yoneticiColumns, true)) unset($yoneticiInsert['eposta']);
                if (!in_array('yetki', $yoneticiColumns, true)) unset($yoneticiInsert['yetki']);
                if (!in_array('created_at', $yoneticiColumns, true)) unset($yoneticiInsert['created_at']);
                if (!in_array('updated_at', $yoneticiColumns, true)) unset($yoneticiInsert['updated_at']);

                $yoneticiId = DB::table('yoneticiler')->insertGetId($yoneticiInsert);
                $data['yonetici_id'] = $yoneticiId;
            } else {
                $data['yonetici_id'] = $yonetici->id;

                $yoneticiUpdate = [
                    'rol' => 3,
                    'durum' => $request->boolean('durum', true) ? 1 : 0,
                    'updated_at' => now(),
                ];
                if (!Schema::hasColumn('yoneticiler', 'updated_at')) {
                    unset($yoneticiUpdate['updated_at']);
                }

                DB::table('yoneticiler')->where('id', $yonetici->id)->update($yoneticiUpdate);
            }
        }

        try {
            DB::table('bayiler')->insert($data);
        } catch (\Throwable $e) {
            \Log::error('Bayi ekleme hatası', [
                'error' => $e->getMessage(),
                'data_keys' => array_keys($data),
                'kayit_tipi' => $validated['kayit_tipi'] ?? null,
            ]);
            return redirect()->back()->withInput()->with('error', 'Bayi eklenemedi: ' . $e->getMessage());
        }

        // Üyeyi bayi olarak işaretle (onaylıysa) — "Bayi Paneline Geç" butonu için
        if (isset($uye->id) && Schema::hasColumn('uyeler', 'bayi')) {
            $bayiMi = $request->boolean('onay_durumu', true) ? 1 : 0;
            DB::table('uyeler')->where('id', $uye->id)->update(['bayi' => $bayiMi]);
        }

        // CRM senkronu: müşteri CRM listesinde (crm_customers) görünsün + bayi olarak işaretlensin
        if (Schema::hasTable('crm_customers') && !empty($uye->email)) {
            try {
                $crmAdi = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? ''));
                if ($crmAdi === '') {
                    $crmAdi = $uye->email;
                }

                $mevcutCrm = DB::table('crm_customers')->where('email', $uye->email)->first();

                $crmVeri = [
                    'adi'           => $crmAdi,
                    'email'         => $uye->email,
                    'telefon'       => $uye->telefon ?? null,
                    'durum'         => $request->boolean('durum', true) ? 'aktif' : 'pasif',
                    'bayi_mi'       => 1,
                    'bayi_tarihi'   => now()->format('Y-m-d'),
                    'bayi_komisyon' => $validated['komisyon_orani'] ?? 10,
                    'updated_at'    => now(),
                ];

                if ($mevcutCrm) {
                    // Var olan CRM müşterisini bayi olarak güncelle
                    DB::table('crm_customers')->where('id', $mevcutCrm->id)->update($crmVeri);
                } else {
                    // Yeni CRM müşterisi oluştur
                    $crmVeri['kaynak'] = 'Bayi Kaydı';
                    $crmVeri['created_at'] = now();
                    DB::table('crm_customers')->insert($crmVeri);
                }
            } catch (\Throwable $e) {
                \Log::warning('Bayi -> CRM senkron hatası', [
                    'uye_id' => $uye->id ?? null,
                    'err' => $e->getMessage(),
                ]);
            }
        }

        $crmId = $request->integer('crm_id') ?: null;
        if ($crmId) {
            return redirect()->route('admin.crm.musteriler.show', ['id' => $crmId])
                ->with('success', 'Bayi başarıyla eklendi. Müşteri artık bayi olarak tanımlandı.');
        }

        return redirect()->route('admin.bayiler.index')->with('success', 'Bayi başarıyla eklendi.');
    }

    public function index()
    {
        $bayiler = DB::table('bayiler')
    ->leftJoin('uyeler', 'bayiler.uye_id', '=', 'uyeler.id')
    ->select('bayiler.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email', 'uyeler.telefon')
    ->orderBy('bayiler.id', 'desc')
    ->paginate(20);

        $statCards = \App\Support\AdminStats::standart('bayiler', [
            'labels' => ['toplam' => 'TOPLAM BAYİ', 'aktif' => 'AKTİF', 'pasif' => 'PASİF', 'buay' => 'BU AY YENİ'],
            'icons'  => ['toplam' => '🏪', 'aktif' => '✅', 'pasif' => '⏸️', 'buay' => '📅'],
        ]);

        return view('admin.bayiler.index', compact('bayiler', 'statCards'));
    }
    
    public function dashboard()
    {
        $admin_id = session('admin_id');
        
        // Yönetici bilgisi
        $yonetici = DB::table('yoneticiler')->where('id', $admin_id)->first();
        
        if (!$yonetici) {
            session()->flush();
            return redirect()->route('admin.giris')->with('error', 'Oturum bilgilerinize ulaşılamadı.');
        }
        
        // Yönetici email'i ile uyeler tablosundan uye_id bul
        $email = $yonetici->eposta ?? $yonetici->email ?? null;
        
        if (!$email) {
            // Email yoksa kullanıcı adı ile ara
            $uye = DB::table('uyeler')->where('email', $yonetici->kullaniciadi)->first();
        } else {
            $uye = DB::table('uyeler')->where('email', $email)->first();
        }
        
        if (!$uye) {
            // Üye bulunamazsa boş istatistikler göster
            $stats = [
                'toplam_satis' => 0,
                'toplam_kazanc' => 0,
                'bekleyen_odeme' => 0,
                'odenen_tutar' => 0,
            ];
            $son_satislar = collect([]);
            $son_odemeler = collect([]);
            $bayi = null;
            
            return view('admin.bayi.dashboard', compact('yonetici', 'bayi', 'stats', 'son_satislar', 'son_odemeler'));
        }
        
        // Bayiler tablosundan bayi bilgisini al
        $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
        
        if (!$bayi) {
            // Bayi kaydı yoksa boş göster
            $stats = [
                'toplam_satis' => 0,
                'toplam_kazanc' => 0,
                'bekleyen_odeme' => 0,
                'odenen_tutar' => 0,
            ];
            $son_satislar = collect([]);
            $son_odemeler = collect([]);
            
            return view('admin.bayi.dashboard', compact('yonetici', 'bayi', 'stats', 'son_satislar', 'son_odemeler'));
        }
        
        // Bayi istatistikleri
        $stats = [
            'toplam_satis' => DB::table('bayi_satislar')->where('bayi_id', $bayi->id)->count(),
            'toplam_kazanc' => $bayi->toplam_kazanc ?? 0,
            'bekleyen_odeme' => DB::table('bayi_odeme_talepleri')->where('bayi_id', $bayi->id)->where('durum', 'beklemede')->count(),
            'odenen_tutar' => $bayi->cekilen_toplam ?? 0,
        ];
        
        // Son satışlar
        $son_satislar = DB::table('bayi_satislar')
            ->where('bayi_id', $bayi->id)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        
        // Son ödeme talepleri
        $son_odemeler = DB::table('bayi_odeme_talepleri')
            ->where('bayi_id', $bayi->id)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        
        return view('admin.bayi.dashboard', compact('yonetici', 'bayi', 'stats', 'son_satislar', 'son_odemeler'));
    }
    
    public function satislarim()
    {
        $admin_id = session('admin_id');
        
        // Yönetici email'i ile uye_id bul
        $yonetici = DB::table('yoneticiler')->where('id', $admin_id)->first();
        
        $email = $yonetici->eposta ?? $yonetici->email ?? null;
        
        if (!$email) {
            $uye = DB::table('uyeler')->where('email', $yonetici->kullaniciadi)->first();
        } else {
            $uye = DB::table('uyeler')->where('email', $email)->first();
        }
        
        if (!$uye) {
            // Boş query builder döndür (paginate için)
            $satislar = DB::table('bayi_satislar')->where('id', 0)->paginate(20);
            return view('admin.bayi.satislar', compact('satislar'));
        }
        
        $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
        
        if (!$bayi) {
            // Boş query builder döndür (paginate için)
            $satislar = DB::table('bayi_satislar')->where('id', 0)->paginate(20);
            return view('admin.bayi.satislar', compact('satislar'));
        }
        
        $satislar = DB::table('bayi_satislar')
            ->where('bayi_id', $bayi->id)
            ->orderBy('id', 'desc')
            ->paginate(20);
        
        return view('admin.bayi.satislar', compact('satislar'));
    }
    
    public function odeme()
    {
        $admin_id = session('admin_id');
        
        // Yönetici email'i ile uye_id bul
        $yonetici = DB::table('yoneticiler')->where('id', $admin_id)->first();
        
        $email = $yonetici->eposta ?? $yonetici->email ?? null;
        
        if (!$email) {
            $uye = DB::table('uyeler')->where('email', $yonetici->kullaniciadi)->first();
        } else {
            $uye = DB::table('uyeler')->where('email', $email)->first();
        }
        
        if (!$uye) {
            $bekleyen_kazanc = 0;
            $odeme_talepleri = collect([]);
            return view('admin.bayi.odeme', compact('bekleyen_kazanc', 'odeme_talepleri'));
        }
        
        $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
        
        if (!$bayi) {
            $bekleyen_kazanc = 0;
            $odeme_talepleri = collect([]);
            return view('admin.bayi.odeme', compact('bekleyen_kazanc', 'odeme_talepleri'));
        }
        
        // Bekleyen kazanç (çekilebilir bakiye)
        $bekleyen_kazanc = $bayi->cekilebilir_bakiye ?? $bayi->bekleyen_kazanc ?? 0;
        
        // Ödeme talepleri
        $odeme_talepleri = DB::table('bayi_odeme_talepleri')
            ->where('bayi_id', $bayi->id)
            ->orderBy('id', 'desc')
            ->get();
        
        return view('admin.bayi.odeme', compact('bekleyen_kazanc', 'odeme_talepleri'));
    }
    
    public function odemeTalepleri()
    {
        $admin_id = session('admin_id');
        
        // Yönetici email'i ile uye_id bul
        $yonetici = DB::table('yoneticiler')->where('id', $admin_id)->first();
        
        $email = $yonetici->eposta ?? $yonetici->email ?? null;
        
        if (!$email) {
            $uye = DB::table('uyeler')->where('email', $yonetici->kullaniciadi)->first();
        } else {
            $uye = DB::table('uyeler')->where('email', $email)->first();
        }
        
        if (!$uye) {
            // Boş query builder döndür (paginate için)
            $talepler = DB::table('bayi_odeme_talepleri')->where('id', 0)->paginate(20);
            return view('admin.bayi.odeme-talepleri', compact('talepler'));
        }
        
        $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
        
        if (!$bayi) {
            // Boş query builder döndür (paginate için)
            $talepler = DB::table('bayi_odeme_talepleri')->where('id', 0)->paginate(20);
            return view('admin.bayi.odeme-talepleri', compact('talepler'));
        }
        
        $talepler = DB::table('bayi_odeme_talepleri')
            ->where('bayi_id', $bayi->id)
            ->orderBy('id', 'desc')
            ->paginate(20);
        
        return view('admin.bayi.odeme-talepleri', compact('talepler'));
    }
    
    public function odemeTalepOlustur(Request $request)
    {
        if ($request->isMethod('post')) {
            // Talep oluşturma
            $validated = $request->validate([
                'tutar' => 'required|numeric|min:50',
                'banka_adi' => 'required|string|max:255',
                'iban' => 'required|string|max:26',
                'hesap_sahibi' => 'required|string|max:255',
            ], [
                'tutar.min' => 'Minimum çekim tutarı 50 TL olmalıdır.',
                'iban.max' => 'IBAN en fazla 26 karakter olabilir.',
            ]);
            
            $admin_id = session('admin_id');
            $yonetici = DB::table('yoneticiler')->where('id', $admin_id)->first();
            
            $email = $yonetici->eposta ?? $yonetici->email ?? null;
            if (!$email) {
                $uye = DB::table('uyeler')->where('email', $yonetici->kullaniciadi)->first();
            } else {
                $uye = DB::table('uyeler')->where('email', $email)->first();
            }
            
            if (!$uye) {
                return redirect()->back()->with('error', 'Kullanıcı bilgileriniz bulunamadı.');
            }
            
            $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
            
            if (!$bayi) {
                return redirect()->back()->with('error', 'Bayi kaydınız bulunamadı.');
            }
            
            // Çekilebilir bakiye kontrolü
            if ($validated['tutar'] > $bayi->cekilebilir_bakiye) {
                return redirect()->back()->with('error', 'Yetersiz bakiye! Çekilebilir bakiyeniz: ' . number_format($bayi->cekilebilir_bakiye, 2) . ' TL');
            }
            
            DB::table('bayi_odeme_talepleri')->insert([
                'bayi_id' => $bayi->id,
                'tutar' => $validated['tutar'],
                'banka_adi' => $validated['banka_adi'],
                'iban' => $validated['iban'],
                'hesap_sahibi' => $validated['hesap_sahibi'],
                'durum' => 'beklemede',
                'talep_tarihi' => date('Y-m-d H:i:s'),
            ]);
            
            return redirect()->route('admin.bayi.odeme.talepleri')->with('success', 'Ödeme talebiniz oluşturuldu. En kısa sürede işleme alınacaktır.');
        }
        
        // GET request - Form göster
        $admin_id = session('admin_id');
        $yonetici = DB::table('yoneticiler')->where('id', $admin_id)->first();
        
        $email = $yonetici->eposta ?? $yonetici->email ?? null;
        if (!$email) {
            $uye = DB::table('uyeler')->where('email', $yonetici->kullaniciadi)->first();
        } else {
            $uye = DB::table('uyeler')->where('email', $email)->first();
        }
        
        $bayi = null;
        if ($uye) {
            $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
        }
        
        return view('admin.bayi.odeme-talep-olustur', compact('bayi'));
    }
    
    public function profil(Request $request)
    {
        $admin_id = session('admin_id');
        $yonetici = DB::table('yoneticiler')->where('id', $admin_id)->first();
        
        if ($request->isMethod('post')) {
            // Profil güncelleme
            $validated = $request->validate([
                'banka_adi' => 'nullable|string|max:255',
                'iban' => 'nullable|string|max:26',
                'hesap_sahibi' => 'nullable|string|max:255',
                'adres' => 'nullable|string|max:500',
                'adres_tarifi' => 'nullable|string|max:500',
                'vergi_no' => 'nullable|string|min:10|max:11',
                'vergi_dairesi' => 'nullable|string|max:255',
            ], [
                'iban.max' => 'IBAN en fazla 26 karakter olabilir.',
                'vergi_no.min' => 'Vergi No en az 10 haneli olmalıdır.',
                'vergi_no.max' => 'Vergi No en fazla 11 haneli olabilir.',
            ]);
            
            $email = $yonetici->eposta ?? $yonetici->email ?? null;
            if (!$email) {
                $uye = DB::table('uyeler')->where('email', $yonetici->kullaniciadi)->first();
            } else {
                $uye = DB::table('uyeler')->where('email', $email)->first();
            }
            
            if (!$uye) {
                return redirect()->back()->with('error', 'Kullanıcı bilgileriniz bulunamadı.');
            }
            
            $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
            
            if (!$bayi) {
                return redirect()->back()->with('error', 'Bayi kaydınız bulunamadı.');
            }
            
            DB::table('bayiler')->where('id', $bayi->id)->update([
                'banka_adi' => $validated['banka_adi'],
                'iban' => $validated['iban'],
                'hesap_sahibi' => $validated['hesap_sahibi'],
                'adres' => $validated['adres'],
                'adres_tarifi' => $validated['adres_tarifi'],
                'vergi_no' => $validated['vergi_no'],
                'vergi_dairesi' => $validated['vergi_dairesi'],
            ]);
            
            return redirect()->route('admin.bayi.profil')->with('success', 'Profil bilgileriniz başarıyla güncellendi.');
        }
        
        // GET - Form göster
        $email = $yonetici->eposta ?? $yonetici->email ?? null;
        if (!$email) {
            $uye = DB::table('uyeler')->where('email', $yonetici->kullaniciadi)->first();
        } else {
            $uye = DB::table('uyeler')->where('email', $email)->first();
        }
        
        $bayi = null;
        if ($uye) {
            $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
        }
        
        return view('admin.bayi.profil', compact('bayi', 'yonetici'));
    }
    
    public function detay($id)
    {
        $bayi = DB::table('bayiler')
    ->leftJoin('uyeler', 'bayiler.uye_id', '=', 'uyeler.id')
    ->select('bayiler.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email', 'uyeler.telefon')
    ->where('bayiler.id', $id)
    ->first();
        
        if (!$bayi) {
            return redirect()->route('admin.bayiler.index')->with('error', 'Bayi bulunamadı!');
        }
        
        // Bayi istatistikleri
        $stats = [
            'toplam_satis' => DB::table('bayi_satislar')->where('bayi_id', $bayi->id)->count(),
            'toplam_kazanc' => $bayi->toplam_kazanc ?? 0,
            'cekilebilir_bakiye' => $bayi->cekilebilir_bakiye ?? 0,
            'cekilen_toplam' => $bayi->cekilen_toplam ?? 0,
        ];
        
        // Satışlar
        $satislar = DB::table('bayi_satislar')
            ->where('bayi_id', $bayi->id)
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();
        
        // Ödeme talepleri
        $odemeTalepleri = DB::table('bayi_odeme_talepleri')
            ->where('bayi_id', $bayi->id)
            ->orderBy('id', 'desc')
            ->get();
        
        return view('admin.bayiler.detay', compact('bayi', 'stats', 'satislar', 'odemeTalepleri'));
    }
    
    public function onayDegistir($id, $durum)
    {
        // String → integer dönüştürme
        $durumMap = ['onayli' => 1, 'beklemede' => 0, 'reddedildi' => 2, 'iptal' => 2];
        $durumInt = $durumMap[$durum] ?? (is_numeric($durum) ? (int) $durum : 0);

        $bayi = DB::table('bayiler')->where('id', $id)->first();

        DB::table('bayiler')->where('id', $id)->update([
            'onay_durumu' => $durumInt,
            'onay_tarihi' => $durumInt === 1 ? now() : null,
        ]);

        // Üye ve yönetici kayıtlarını onay durumuyla senkronla
        if ($bayi) {
            $bayiMi = $durumInt === 1 ? 1 : 0;

            // uyeler.bayi → "Bayi Paneline Geç" butonu bunu kontrol ediyor
            if ($bayi->uye_id && Schema::hasColumn('uyeler', 'bayi')) {
                DB::table('uyeler')->where('id', $bayi->uye_id)->update(['bayi' => $bayiMi]);
            }

            // Bağlı yönetici (rol=3) hesabını aktif/pasif yap
            if ($bayi->yonetici_id) {
                DB::table('yoneticiler')->where('id', $bayi->yonetici_id)->update(['durum' => $bayiMi]);
            }

            // CRM senkronu: crm_customers'ta bayi_mi işaretle (email ile eşleştir)
            if (Schema::hasTable('crm_customers') && $bayi->uye_id) {
                $uye = DB::table('uyeler')->where('id', $bayi->uye_id)->first();
                if ($uye && !empty($uye->email)) {
                    $crmGuncelle = ['bayi_mi' => $bayiMi, 'updated_at' => now()];
                    if ($bayiMi === 1) {
                        $crmGuncelle['bayi_tarihi'] = now()->format('Y-m-d');
                        $crmGuncelle['bayi_komisyon'] = $bayi->komisyon_orani ?? 10;
                    }
                    DB::table('crm_customers')->where('email', $uye->email)->update($crmGuncelle);
                }
            }
        }

        return redirect()->back()->with('success', 'Bayi onay durumu güncellendi.');
    }
    
    public function komisyonGuncelle(Request $request, $id)
    {
        $request->validate([
            'komisyon_orani' => 'required|numeric|min:0|max:100',
        ]);
        
        DB::table('bayiler')->where('id', $id)->update([
            'komisyon_orani' => $request->komisyon_orani,
        ]);
        
        return redirect()->back()->with('success', 'Komisyon oranı güncellendi.');
    }
    
    /**
     * Bayi kredi sayfası — kredi yönetimi bayi detayında yapıldığı için oraya yönlendirir.
     * (Önceki sürümde route vardı ama metod eksikti → "undefined method kredi()" 500.)
     */
    public function kredi($id)
    {
        return redirect()->route('admin.bayiler.detay', $id);
    }

    public function krediGuncelle(Request $request, $id)
    {
        $request->validate([
            'kredi_limiti' => 'required|numeric|min:0',
        ]);

        DB::table('bayiler')->where('id', $id)->update([
            'kredi_limiti' => $request->kredi_limiti,
        ]);

        return redirect()->back()->with('success', 'Kredi limiti güncellendi.');
    }

    public function krediSifirla($id)
    {
        DB::table('bayiler')->where('id', $id)->update([
            'kredi_kullanim' => 0,
        ]);

        return redirect()->back()->with('success', 'Kredi kullanımı sıfırlandı.');
    }

    public function odemeTalepOnayla($id)
    {
        $talep = DB::table('bayi_odeme_talepleri')->where('id', $id)->first();
        
        if (!$talep) {
            return redirect()->back()->with('error', 'Ödeme talebi bulunamadı!');
        }
        
        DB::table('bayi_odeme_talepleri')->where('id', $id)->update([
            'durum' => 'onaylandi',
            'onay_tarihi' => now(),
        ]);
        
        // Bayi bakiyesini güncelle
        DB::table('bayiler')->where('id', $talep->bayi_id)->decrement('cekilebilir_bakiye', $talep->tutar);
        DB::table('bayiler')->where('id', $talep->bayi_id)->increment('cekilen_toplam', $talep->tutar);
        
        return redirect()->back()->with('success', 'Ödeme talebi onaylandı.');
    }
    
    public function odemeTalepReddet($id)
    {
        DB::table('bayi_odeme_talepleri')->where('id', $id)->update([
            'durum' => 'reddedildi',
        ]);
        
        return redirect()->back()->with('success', 'Ödeme talebi reddedildi.');
    }
    
    public function sil($id)
    {
        DB::table('bayiler')->where('id', $id)->delete();
        return redirect()->route('admin.bayiler.index')->with('success', 'Bayi silindi.');
    }
    
    /**
     * Bayi komisyon ve indirim ayarları sayfası
     */
    public function ayarlar()
    {
        $ayarlar = DB::table('bayi_ayarlari')->first();
        
        // Eğer ayarlar yoksa varsayılan oluştur
        if (!$ayarlar) {
            DB::table('bayi_ayarlari')->insert([
                'varsayilan_komisyon_orani' => 10.00,
                'varsayilan_musteri_indirim_orani' => 5.00,
                'min_sepet_tutari' => 100,
                'max_musteri_indirim' => 500,
                'bayi_sistemi_aktif' => true,
                'yeni_bayi_otomatik_onay' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ayarlar = DB::table('bayi_ayarlari')->first();
        }
        
        // Tüm bayilerin listesi (toplu güncelleme için)
        // ÖNEMLİ: leftJoin kullan ki uyesi olmayan bayiler de listede çıksın
        $bayiler = DB::table('bayiler')
            ->leftJoin('uyeler', 'bayiler.uye_id', '=', 'uyeler.id')
            ->select('bayiler.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
            ->orderBy('bayiler.id', 'desc')
            ->get();
        
        return view('admin.bayiler.ayarlar', compact('ayarlar', 'bayiler'));
    }
    
    /**
     * Bayi ayarlarını güncelle
     */
    public function ayarlarGuncelle(Request $request)
    {
        $validated = $request->validate([
            'varsayilan_komisyon_orani' => 'required|numeric|min:0|max:100',
            'varsayilan_musteri_indirim_orani' => 'required|numeric|min:0|max:100',
            'min_sepet_tutari' => 'required|numeric|min:0',
            'max_musteri_indirim' => 'required|numeric|min:0',
            'bayi_sistemi_aktif' => 'boolean',
            'yeni_bayi_otomatik_onay' => 'boolean',
            'kullanim_sartlari' => 'nullable|string|max:5000',
        ]);
        
        DB::table('bayi_ayarlari')->update([
            'varsayilan_komisyon_orani' => $validated['varsayilan_komisyon_orani'],
            'varsayilan_musteri_indirim_orani' => $validated['varsayilan_musteri_indirim_orani'],
            'min_sepet_tutari' => $validated['min_sepet_tutari'],
            'max_musteri_indirim' => $validated['max_musteri_indirim'],
            'bayi_sistemi_aktif' => $request->has('bayi_sistemi_aktif') ? 1 : 0,
            'yeni_bayi_otomatik_onay' => $request->has('yeni_bayi_otomatik_onay') ? 1 : 0,
            'kullanim_sartlari' => $validated['kullanim_sartlari'],
            'updated_at' => now(),
        ]);
        
        return redirect()->route('admin.bayiler.ayarlar')->with('success', 'Bayi ayarları başarıyla güncellendi.');
    }
    
    /**
     * Bayi düzenleme sayfası
     */
    public function duzenle($id)
    {
        $bayi = DB::table('bayiler')
    ->leftJoin('uyeler', 'bayiler.uye_id', '=', 'uyeler.id')
    ->select('bayiler.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email', 'uyeler.telefon')
    ->where('bayiler.id', $id)
    ->first();
        
        if (!$bayi) {
            return redirect()->route('admin.bayiler.index')->with('error', 'Bayi bulunamadı!');
        }
        
        return view('admin.bayiler.duzenle', compact('bayi'));
    }
    
    /**
     * Bayi bilgilerini güncelle
     */
    public function guncelle(Request $request, $id)
    {
        $validated = $request->validate([
            'komisyon_orani' => 'required|numeric|min:0|max:100',
            'pesin_komisyon_orani' => 'nullable|numeric|min:0|max:100',
            'vadeli_komisyon_orani' => 'nullable|numeric|min:0|max:100',
            'musteri_indirim_orani' => 'nullable|numeric|min:0|max:100',
            'min_sepet_tutari' => 'nullable|numeric|min:0',
            'durum' => 'boolean',
            'onay_durumu' => 'boolean',
        ]);

        $guncelle = [
            'komisyon_orani' => $validated['komisyon_orani'],
            'pesin_komisyon_orani' => $validated['pesin_komisyon_orani'] ?? 0,
            'vadeli_komisyon_orani' => $validated['vadeli_komisyon_orani'] ?? 0,
            'musteri_indirim_orani' => $validated['musteri_indirim_orani'] ?? 0,
            'min_sepet_tutari' => $validated['min_sepet_tutari'] ?? 0,
            'il'  => $request->input('il'),
            'ilce' => $request->input('ilce'),
            'durum' => $request->has('durum') ? 1 : 0,
            'onay_durumu' => $request->has('onay_durumu') ? 1 : 0,
            'onay_tarihi' => $request->has('onay_durumu') ? now() : null,
            'updated_at' => now(),
        ];

        // bayiler tablosunda olmayan kolonları düşür (şema farklarına tolerans)
        $bayiColumns = Schema::getColumnListing('bayiler');
        foreach (array_keys($guncelle) as $key) {
            if (!in_array($key, $bayiColumns, true)) {
                unset($guncelle[$key]);
            }
        }

        DB::table('bayiler')->where('id', $id)->update($guncelle);

        // Üye ve yönetici kayıtlarını onay durumuyla senkronla
        $bayiMi = $request->has('onay_durumu') ? 1 : 0;
        $bayiKaydi = DB::table('bayiler')->where('id', $id)->first();
        if ($bayiKaydi) {
            if ($bayiKaydi->uye_id && Schema::hasColumn('uyeler', 'bayi')) {
                DB::table('uyeler')->where('id', $bayiKaydi->uye_id)->update(['bayi' => $bayiMi]);
            }
            if ($bayiKaydi->yonetici_id) {
                DB::table('yoneticiler')->where('id', $bayiKaydi->yonetici_id)->update(['durum' => $bayiMi]);
            }
        }

        return redirect()->route('admin.bayiler.index')->with('success', 'Bayi bilgileri başarıyla güncellendi.');
    }
    
    /**
     * Tüm bayilere toplu komisyon/indirim oranı uygula
     */
    public function topluGuncelle(Request $request)
    {
        $validated = $request->validate([
            'komisyon_orani' => 'nullable|numeric|min:0|max:100',
            'musteri_indirim_orani' => 'nullable|numeric|min:0|max:100',
        ]);
        
        $updateData = [];
        
        if (isset($validated['komisyon_orani'])) {
            $updateData['komisyon_orani'] = $validated['komisyon_orani'];
        }
        
        if (isset($validated['musteri_indirim_orani'])) {
            $updateData['musteri_indirim_orani'] = $validated['musteri_indirim_orani'];
        }

        // bayiler tablosunda olmayan kolonları düşür
        $bayiColumns = Schema::getColumnListing('bayiler');
        foreach (array_keys($updateData) as $key) {
            if (!in_array($key, $bayiColumns, true)) {
                unset($updateData[$key]);
            }
        }

        if (!empty($updateData)) {
            DB::table('bayiler')->update($updateData);
            return redirect()->route('admin.bayiler.ayarlar')->with('success', 'Tüm bayilerin oranları başarıyla güncellendi.');
        }
        
        return redirect()->route('admin.bayiler.ayarlar')->with('error', 'Güncellenecek veri bulunamadı.');
    }
}