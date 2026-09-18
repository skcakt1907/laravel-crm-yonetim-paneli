<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use App\Models\CRM\Customer;
use App\Models\Yonetici;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
 use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->with('sorumlu');

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('adi', 'like', '%' . $search . '%')
                    ->orWhere('unvan', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('telefon', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('durum')) {
            $query->where('durum', $request->string('durum'));
        }

        if ($request->filled('sorumlu_id')) {
            $query->where('sorumlu_id', $request->integer('sorumlu_id'));
        }

        // Aktifler önce + son hizmet/ödeme tarihi subselect
        $customers = $query
            ->addSelect([
                'son_hizmet' => DB::table('faturalar')
                    ->join('uyeler', 'uyeler.id', '=', 'faturalar.uyeid')
                    ->whereColumn('uyeler.email', 'crm_customers.email')
                    ->orderByDesc('faturalar.id')
                    ->limit(1)
                    ->select('faturalar.hizmet'),
                'son_odeme_tarih' => DB::table('faturalar')
                    ->join('uyeler', 'uyeler.id', '=', 'faturalar.uyeid')
                    ->whereColumn('uyeler.email', 'crm_customers.email')
                    ->orderByDesc('faturalar.id')
                    ->limit(1)
                    ->select('faturalar.odenen_tarih'),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Üst stat'lar (filtreden bağımsız toplam sayılar)
        $musteriToplam = Customer::count();
        $musteriSicak  = Customer::whereIn('durum', ['sicak', 'aktif'])->count();
        $musteriIlimli = Customer::whereIn('durum', ['ilimli', 'potansiyel'])->count();
        $musteriSoguk  = max(0, $musteriToplam - $musteriSicak - $musteriIlimli);

        $yoneticiler = Yonetici::query()
            ->select('id', 'adi', 'kullaniciadi', 'rol')
            ->orderBy('adi')
            ->get();

        $mailTemplates = Schema::hasTable('mail_templates')
            ? DB::table('mail_templates')->where('aktif', 1)->orderBy('name')->get()
            : collect();

        // Profil fotoğrafları: müşterinin kendi fotosu yoksa BAĞLI üyenin fotosu
        // gösterilsin. Üye↔CRM eşleşmesi her zaman aynı ID değil (örn. Ahmetcan
        // SELEK: CRM #645, üye farklı) → hem ID hem E-POSTA ile eşleştir.
        $uyeFotolar = [];
        $uyeFotolarEmail = [];
        try {
            if (Schema::hasColumn('uyeler', 'profil_foto')) {
                $items   = collect($customers->items());
                $idler   = $items->pluck('id')->filter()->all();
                $emailler = $items->pluck('email')->filter()->unique()->all();

                if (!empty($idler)) {
                    $uyeFotolar = DB::table('uyeler')
                        ->whereIn('id', $idler)
                        ->whereNotNull('profil_foto')
                        ->pluck('profil_foto', 'id')
                        ->all();
                }
                if (!empty($emailler)) {
                    $uyeFotolarEmail = DB::table('uyeler')
                        ->whereIn('email', $emailler)
                        ->whereNotNull('profil_foto')
                        ->pluck('profil_foto', 'email')
                        ->all();
                }
            }
        } catch (\Throwable $e) {}

        return view('admin.crm.customers.index', compact(
            'customers', 'yoneticiler', 'mailTemplates', 'uyeFotolar', 'uyeFotolarEmail',
            'musteriToplam', 'musteriSicak', 'musteriIlimli', 'musteriSoguk'
        ));
    }

    public function create()
    {
        $yoneticiler = Yonetici::query()
            ->select('id', 'adi', 'kullaniciadi', 'rol')
            ->orderBy('adi')
            ->get();

        $listeler = \App\Models\CRM\CrmListe::where('durum', 1)->orderBy('ad')->get();
        $seciliListeler = [];

        return view('admin.crm.customers.create', compact('yoneticiler', 'listeler', 'seciliListeler'));
    }

     public function store(Request $request)
    {
        $validated = $request->validate([
            'adi' => 'required|string|max:150',
            'unvan' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'telefon' => 'nullable|string|max:50',
            'sektor' => 'nullable|string|max:100',
            'kaynak' => 'nullable|string|max:100',
            'durum' => 'required|string|max:50',
            'sorumlu_id' => 'nullable|integer|exists:yoneticiler,id',
            'adres' => 'nullable|string',
            'mahalle' => 'nullable|string|max:200',
            'dtarih' => 'nullable|date',
            'etiketler' => 'nullable|array',
            'etiketler.*' => 'nullable|string|max:50',
            'etiketler_text' => 'nullable|string',
        ]);
 
        $payload = collect($validated)
            ->except(['etiketler', 'etiketler_text'])
            ->toArray();
 
        // dtarih crm_customers'ta kolon olarak yoksa payload'dan çıkar (insert patlamasın).
        if (!Schema::hasColumn('crm_customers', 'dtarih')) {
            unset($payload['dtarih']);
        }
 
        $etiketler = $this->mergeTags($request);
        $payload['etiketler'] = $etiketler->isEmpty() ? null : $etiketler->all();
 
        // 1) CRM kaydını oluştur (eskisi gibi)
        $customer = Customer::create($payload);
        $this->dcAlanlariKaydet($customer->id, $request);
        $customer->listeler()->sync($request->input('listeler', [])); // Data Center listeleri

        // 2) SENKRON: aynı kişiyi uyeler tablosuna da yaz (giriş + fatura için)
        $this->uyelereSenkronla($customer, $request);
 
        $basariMsj = 'Müşteri başarıyla oluşturuldu.';
        if (session('uye_sifre_bilgi')) {
            $basariMsj .= ' ' . session('uye_sifre_bilgi');
        }
        return redirect()->route('admin.crm.musteriler.index')
            ->with('success', $basariMsj);
    }
    
    /** Data Center bayrağı + Kategori (fillable dışı olabilir → DB::table ile). */
    private function dcAlanlariKaydet($id, Request $request): void
    {
        $guncel = [];
        if (Schema::hasColumn('crm_customers', 'kategori')) {
            $guncel['kategori'] = trim((string) $request->input('kategori')) ?: null;
        }
        if (Schema::hasColumn('crm_customers', 'data_center')) {
            $guncel['data_center'] = $request->boolean('data_center') ? 1 : 0;
        }
        if (!empty($guncel)) {
            DB::table('crm_customers')->where('id', $id)->update($guncel);
        }
    }

    private function uyelereSenkronla(Customer $customer, Request $request): void
    {
        try {
            // Email yoksa giriş yapacak bir hesap kurulamaz; senkron atlanır.
            if (empty($customer->email) || !Schema::hasTable('uyeler')) {
                return;
            }
 
            // Bu email uyeler'de zaten var mı?
            $mevcut = DB::table('uyeler')->where('email', $customer->email)->first();
            if ($mevcut) {
                return; // Zaten üye -> giriş yapabiliyor, dokunma.
            }
 
            // "adi" tek alan; uyeler ad + soyad ister. Son boşluktan böl.
            [$ad, $soyad] = $this->adSoyadAyir($customer->adi);
 
            // Admin formdan şifre girdiyse onu kullan, yoksa rastgele üret.
            $duzSifre = $request->filled('sifre')
                ? (string) $request->input('sifre')
                : \Illuminate\Support\Str::random(10);
 
            $uyeId = DB::table('uyeler')->insertGetId([
                'ad'        => $ad,
                'soyad'     => $soyad,
                'email'     => $customer->email,
                'telefon'   => $customer->telefon,
                'sifre'     => Hash::make($duzSifre),   // bcrypt -> AuthController Hash::check ile uyumlu
                'utipi'     => 0,
                'firmaadi'  => $customer->unvan,
                'dtarih'    => $request->input('dtarih'),
                'durum'     => 1,                        // aktif
                'bakiye'    => 0,
                'cinsiyet'  => 'Erkek',
                'tarih'     => date('Y-m-d H:i:s'),
                'ktarih'    => date('Y-m-d H:i:s'),
            ]);
 
            // CRM müşterisinin oluşturduğu üye şifresini admin'e geri göster
            // (müşteriye iletebilmen için flash mesaja ekliyoruz)
            session()->flash('uye_sifre_bilgi',
                'Bu müşteri için giriş hesabı oluşturuldu. E-posta: ' . $customer->email .
                ' | Geçici şifre: ' . $duzSifre);
 
            // Hoşgeldin maili (UyeController ile aynı mantık; admin checkbox işaretlediyse)
            if ($request->has('mail_gonder') && $request->mail_gonder == 1) {
                try {
                    \App\Services\CustomerNotifier::hosgeldin($uyeId);
                } catch (\Throwable $e) {
                    \Log::warning('CRM senkron hoşgeldin maili gönderilemedi', ['err' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            // Senkron başarısız olsa bile CRM kaydı durur; sadece logla.
            \Log::warning('CRM->uyeler senkron hatası', [
                'email' => $customer->email ?? null,
                'err'   => $e->getMessage(),
            ]);
        }
    }
    
    
    private function adSoyadAyir(?string $tamAd): array
    {
        $tamAd = trim((string) $tamAd);
        if ($tamAd === '') {
            return ['', ''];
        }
        $pos = mb_strrpos($tamAd, ' ');
        if ($pos === false) {
            return [$tamAd, ''];
        }
        return [
            trim(mb_substr($tamAd, 0, $pos)),
            trim(mb_substr($tamAd, $pos + 1)),
        ];
    }

    public function show(int $id, Request $request)
    {
        $customer = Customer::with([
            'sorumlu:id,adi,kullaniciadi',
            'opportunities.stage',
            'opportunities.pipeline',
            'notes' => fn ($q) => $q->latest(),
            'tasks' => fn ($q) => $q->latest(),
            'bakiyeHareketleri',
        ])->findOrFail($id);

        /*
         * BAĞLI ÜYE (giriş hesabı) — DÜZELTME (31.07.2026)
         *
         * Eskiden üye SADECE e-posta ile aranıyordu; `crm_customers.uye_id`
         * kolonu hiç kullanılmıyordu. CRM kartındaki e-posta ile üyenin e-postası
         * farklıysa (örn. CRM'de info@firma.com, üyede kisisel@gmail.com) üye
         * bulunamıyor ve üyeye bağlı BÜTÜN sekmeler sessizce boş kalıyordu:
         * faturalar, hostingler, hizmetler, destek, e-faturalar, teklifler, bayi.
         *
         * Artık önce uye_id, bulunamazsa e-posta.
         */
        $uye = null;
        $customerEmail = trim((string) ($customer->email ?? ''));

        if (Schema::hasTable('uyeler')) {
            if (!empty($customer->uye_id)) {
                $uye = DB::table('uyeler')->where('id', $customer->uye_id)->first();
            }

            if (!$uye && $customerEmail !== '') {
                $uye = DB::table('uyeler')
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($customerEmail)])
                    ->first();
            }
        }

        // Aktif sekme
        $activeTab = $request->get('tab', 'bilgiler');

        // Faturalar — uyeid veya email eşleşmesi ile
        $faturalar = collect([]);
        if (Schema::hasTable('faturalar')) {
            $faturaQuery = DB::table('faturalar');
            $hasFilter = false;

            if ($uye) {
                $faturaQuery->where('uyeid', $uye->id);
                $hasFilter = true;
            }

            if ($customerEmail !== '') {
                $method = $hasFilter ? 'orWhereRaw' : 'whereRaw';
                $faturaQuery->{$method}('LOWER(mail) = ?', [mb_strtolower($customerEmail)]);
                $hasFilter = true;
            }

            $faturalar = $hasFilter
                ? $faturaQuery->orderByDesc('id')->get()
                : collect([]);
        }

        // Hostingler (tipi=2)
        $hostingler = collect([]);
        if (Schema::hasTable('satilanlar')) {
            $hostingler = DB::table('satilanlar')
                ->where('tipi', '2')
                ->where(function ($q) use ($uye, $id) {
                    if ($uye) $q->where('uyeid', $uye->id);
                    $q->orWhere('crm_musteri_id', $id);
                })
                ->orderByDesc('id')
                ->get();
        }

        /*
         * ALAN ADLARI
         *
         * DÜZELTME (31.07.2026) — müşteri kartında domainler görünmüyordu:
         *  1) Üye kaydı olan müşterilerde SADECE `domain_orders` okunuyordu; o tablo
         *     BOŞ (0 kayıt) ve `elseif` yüzünden `satilanlar`a hiç düşülmüyordu.
         *  2) `satilanlar`da domainler iki tiple duruyor: tipi='3' (228 kayıt) ve
         *     tipi='0' (342 kayıt, hepsinde domain dolu). Yalnızca '3' alınıyordu,
         *     342 domain hiçbir sekmede görünmüyordu.
         * Artık iki kaynak birleştiriliyor ve her iki tip de kapsanıyor.
         */
        $alanAdlari = collect([]);

        if (Schema::hasTable('satilanlar')) {
            $alanAdlari = DB::table('satilanlar')
                ->whereIn('tipi', ['0', '3'])
                ->whereNotNull('domain')->where('domain', '<>', '')
                ->where(function ($q) use ($uye, $id) {
                    if ($uye) $q->where('uyeid', $uye->id);
                    $q->orWhere('crm_musteri_id', $id);
                })
                ->orderByDesc('id')
                ->get();
        }

        if (Schema::hasTable('domain_orders') && $uye) {
            $siparisler = DB::table('domain_orders')
                ->where('user_id', $uye->id)
                ->orderByDesc('id')
                ->get();

            if ($siparisler->isNotEmpty()) {
                $alanAdlari = $alanAdlari->concat($siparisler);
            }
        }

        // Hizmetler / Web Paketleri (tipi=1)
        $hizmetler = collect([]);
        if (Schema::hasTable('satilanlar')) {
            $hizmetler = DB::table('satilanlar')
                ->where('tipi', '1')
                ->where(function ($q) use ($uye, $id) {
                    if ($uye) $q->where('uyeid', $uye->id);
                    $q->orWhere('crm_musteri_id', $id);
                })
                ->orderByDesc('id')
                ->get();
        }

        // Destek Talepleri
        $destekTalepleri = collect([]);
        if ($uye && Schema::hasTable('destek')) {
            $destekTalepleri = DB::table('destek')
                ->where('uyeid', $uye->id)
                ->where('ustid', 0)
                ->orderByDesc('id')
                ->get();
        }

        // E-Faturalar (eÄŸer tablo varsa)
        /*
         * E-FATURALAR — DÜZELTME (31.07.2026)
         *
         * Eskiden `e_faturalar` tablosu okunuyordu; o tablo BOŞ (0 kayıt).
         * Gerçek veri `musteri_efaturalar` içinde (270 kayıt) ve hem `uyeid`
         * hem `crm_musteri_id` ile bağlanıyor. Sekme her müşteride boş görünüyordu.
         */
        $eFaturalar = collect([]);

        if (Schema::hasTable('musteri_efaturalar')) {
            $eFaturalar = DB::table('musteri_efaturalar')
                ->where(function ($q) use ($uye, $id) {
                    if ($uye) $q->where('uyeid', $uye->id);
                    $q->orWhere('crm_musteri_id', $id);
                })
                ->orderByDesc('id')
                ->get();
        }

        // Eski tablo hâlâ duruyorsa oradaki kayıtlar da eklensin (veri kaybolmasın)
        if ($uye && Schema::hasTable('e_faturalar')) {
            $columns = Schema::getColumnListing('e_faturalar');
            $userIdColumn = in_array('uye_id', $columns) ? 'uye_id' : (in_array('uyeid', $columns) ? 'uyeid' : null);

            if ($userIdColumn) {
                $eskiler = DB::table('e_faturalar')
                    ->where($userIdColumn, $uye->id)
                    ->orderByDesc('id')
                    ->get();

                if ($eskiler->isNotEmpty()) {
                    $eFaturalar = $eFaturalar->concat($eskiler);
                }
            }
        }

        // Referanslar (referanslar tablosu proje referansları için, müşteri referansları için referans_kayitlari tablosu kullanılabilir)
        $referanslar = collect([]);
        if ($uye && Schema::hasTable('referans_kayitlari')) {
            $referanslar = DB::table('referans_kayitlari')
                ->where('uye_id', $uye->id)
                ->orderByDesc('id')
                ->get();
        }

        /*
         * TEKLİFLER — DÜZELTME (31.07.2026)
         *
         * Eskiden `$teklifler = collect([])` atanıp bırakılmıştı; hiçbir sorgu
         * yoktu. Teklifler sekmesi her müşteride kalıcı olarak boştu, oysa
         * `musteri_teklifler` tablosunda 367 kayıt var.
         */
        $teklifler = collect([]);
        if ($uye && Schema::hasTable('musteri_teklifler')) {
            $teklifler = DB::table('musteri_teklifler')
                ->where('uyeid', $uye->id)
                ->orderByDesc('id')
                ->get();
        }

        // Müşteri bayi mi?
        $bayi = null;
        if ($uye && Schema::hasTable('bayiler')) {
            $bayi = DB::table('bayiler')->where('uye_id', $uye->id)->first();
        }

        $mailTemplates = Schema::hasTable('mail_templates')
            ? DB::table('mail_templates')->where('aktif', 1)->orderBy('name')->get()
            : collect();

        $bayiPaketleri = Schema::hasTable('bayilikler')
            ? DB::table('bayilikler')->orderBy('sira')->get()->unique('paketadi')->values()
            : collect();

        $webPaketleri = Schema::hasTable('yazilimlar')
            ? DB::table('yazilimlar')->where('durum', 1)->orderBy('sira')->select('id','adi','tutar')->get()
            : collect();

        // Teklif modalı için hosting paketleri (hosting_paketler tercih, fallback hostingler)
        $hostingPaketleri = collect();
        if (Schema::hasTable('hosting_paketler') && DB::table('hosting_paketler')->count() > 0) {
            $hostingPaketleri = DB::table('hosting_paketler')->where('durum', 1)->orderBy('id')
                ->select('id','adi', DB::raw('CAST(fiyat AS DECIMAL(12,2)) AS tutar'))->get();
        } elseif (Schema::hasTable('hostingler')) {
            $hostingPaketleri = DB::table('hostingler')->where('durum', 1)->orderBy('sira')
                ->select('id','adi', DB::raw('CAST(tutar AS DECIMAL(12,2)) AS tutar'))->get();
        }

        $musteriTeklifleri = Schema::hasTable('crm_musteri_teklifleri')
            ? DB::table('crm_musteri_teklifleri')->where('customer_id', $customer->id)->orderByDesc('id')->get()
            : collect();

        // Admin panelinden oluşturulan "Paket Teklifleri" (paket_teklifleri.uye_id ile eşleşir)
        $paketTeklifleri = collect();
        if ($uye && Schema::hasTable('paket_teklifleri')) {
            $paketTeklifleri = DB::table('paket_teklifleri')
                ->where('uye_id', $uye->id)
                ->orderByDesc('id')
                ->get();
        }

        return view('admin.crm.customers.show', compact(
            'customer',
            'uye',
            'bayi',
            'activeTab',
            'faturalar',
            'hostingler',
            'alanAdlari',
            'hizmetler',
            'destekTalepleri',
            'eFaturalar',
            'referanslar',
            'teklifler',
            'mailTemplates',
            'bayiPaketleri',
            'webPaketleri',
            'hostingPaketleri',
            'musteriTeklifleri',
            'paketTeklifleri'
        ));
    }

    public function edit(int $id)
    {
        $customer = Customer::findOrFail($id);
        $yoneticiler = Yonetici::query()
            ->select('id', 'adi', 'kullaniciadi', 'rol')
            ->orderBy('adi')
            ->get();

        $listeler = \App\Models\CRM\CrmListe::where('durum', 1)->orderBy('ad')->get();
        $seciliListeler = $customer->listeler()->pluck('crm_listeler.id')->all();

        return view('admin.crm.customers.edit', compact('customer', 'yoneticiler', 'listeler', 'seciliListeler'));
    }

    public function update(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'adi' => 'required|string|max:150',
            'unvan' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'telefon' => 'nullable|string|max:50',
            'gsm' => 'nullable|string|max:50',
            'vergi_no' => 'nullable|string|max:50',
            'vergi_dairesi' => 'nullable|string|max:100',
            'web_sitesi' => 'nullable|string|max:190',
            'sektor' => 'nullable|string|max:100',
            'kaynak' => 'nullable|string|max:100',
            'durum' => 'required|string|max:50',
            'sorumlu_id' => 'nullable|integer|exists:yoneticiler,id',
            'il' => 'nullable|string|max:80',
            'ilce' => 'nullable|string|max:80',
            'mahalle' => 'nullable|string|max:200',
            'adres' => 'nullable|string',
            'not_icerik' => 'nullable|string|max:5000',
            'dtarih' => 'nullable|date',
            // Bakiye — 29 Mayıs 2026 ekleme (eksikti, kaydedilmiyordu)
            'bakiye' => 'nullable|numeric',
            'etiketler' => 'nullable|array',
            'etiketler.*' => 'nullable|string|max:50',
            'etiketler_text' => 'nullable|string',
            'sifre' => 'nullable|string|min:6|max:100',
        ]);

        $payload = collect($validated)
            ->except(['etiketler', 'etiketler_text', 'bakiye', 'sifre'])
            ->toArray();

        // dtarih crm_customers'ta yoksa çıkar (insert/update patlamasın)
        if (!Schema::hasColumn('crm_customers', 'dtarih')) {
            unset($payload['dtarih']);
        }

        $etiketler = $this->mergeTags($request);
        $payload['etiketler'] = $etiketler->isEmpty() ? null : $etiketler->all();

        // ========== BAKİYE GÜNCELLEMESİ (Hareket logu ile) — 29 Mayıs 2026 ==========
        if ($request->filled('bakiye') || $request->has('bakiye')) {
            $yeniBakiye = (float) str_replace([',', ' '], ['.', ''], (string) $request->input('bakiye'));
            $eskiBakiye = (float) ($customer->bakiye ?? 0);
            $fark = $yeniBakiye - $eskiBakiye;

            if (abs($fark) > 0.001) {
                $payload['bakiye'] = $yeniBakiye;

                // Bakiye hareketi logu (varsa)
                try {
                    if (Schema::hasTable('crm_customer_bakiye_hareketleri')) {
                        DB::table('crm_customer_bakiye_hareketleri')->insert([
                            'customer_id' => $customer->id,
                            'tip'         => $fark > 0 ? 'yukleme' : 'iade',
                            'tutar'       => abs($fark),
                            'bakiye_oncesi' => $eskiBakiye,
                            'bakiye_sonrasi' => $yeniBakiye,
                            'aciklama'    => 'Müşteri düzenleme ekranından güncelleme',
                            'yonetici_id' => session('admin_id') ?? null,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Bakiye hareketi log yazılamadı: ' . $e->getMessage());
                }

                // SENKRON: Müşteri paneli bakiyeyi uyeler.bakiye'den okuyor (HomeController@bakiyem).
                // Admin CRM'de bakiyeyi NE YAZARSA uyeler.bakiye de TAM O DEGER olur (mutlak senkron).
                // Boylece iki tablo her zaman esitlenir; farkli baslangiclar sorun cikarmaz.
                try {
                    if (!empty($customer->email) && Schema::hasTable('uyeler')) {
                        $uye = DB::table('uyeler')->where('email', $customer->email)->first();
                        if ($uye && Schema::hasColumn('uyeler', 'bakiye')) {
                            $uyeEski = (float) ($uye->bakiye ?? 0);
                            $uyeYeni = $yeniBakiye; // CRM'deki yeni mutlak deger
                            $uyeFark = $uyeYeni - $uyeEski;

                            DB::table('uyeler')->where('id', $uye->id)->update(['bakiye' => $uyeYeni]);

                            // Musteri panelinin okudugu gecmis tablosu (sadece gercek degisiklik varsa)
                            if (abs($uyeFark) > 0.001 && Schema::hasTable('bakiye_gecmisi')) {
                                DB::table('bakiye_gecmisi')->insert([
                                    'uye_id'       => $uye->id,
                                    'tip'          => $uyeFark > 0 ? 'yukleme' : 'harcama',
                                    'tutar'        => abs($uyeFark),
                                    'bakiye_once'  => $uyeEski,
                                    'bakiye_sonra' => $uyeYeni,
                                    'aciklama'     => 'Yönetici tarafından bakiye güncellemesi',
                                    'tarih'        => now(),
                                ]);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Bakiye uyeler senkron hatası: ' . $e->getMessage());
                }
            }
        }

        $customer->update($payload);
        $this->dcAlanlariKaydet($customer->id, $request);
        $customer->listeler()->sync($request->input('listeler', [])); // Data Center listeleri

        // ── PROFİL FOTOĞRAFI (kolon varsa) ──
        // Customer modelinin $fillable'ında olmayabilir -> dogrudan DB ile guncellenir.
        if (Schema::hasColumn('crm_customers', 'profil_foto')) {
            $eskiFoto = $customer->profil_foto ?? null;

            if ($request->hasFile('profil_foto')) {
                $request->validate([
                    'profil_foto' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
                ], [
                    'profil_foto.image' => 'Profil fotoğrafı bir resim dosyası olmalı.',
                    'profil_foto.mimes' => 'Sadece JPG, PNG veya WEBP yükleyebilirsiniz.',
                    'profil_foto.max'   => 'Fotoğraf en fazla 2MB olabilir.',
                ]);

                $klasor = public_path('tema/uploads/profil');
                if (!is_dir($klasor)) { @mkdir($klasor, 0755, true); }

                $uzanti = strtolower($request->file('profil_foto')->getClientOriginalExtension() ?: 'jpg');
                $dosyaAdi = 'musteri_' . $customer->id . '_' . time() . '.' . $uzanti;
                $request->file('profil_foto')->move($klasor, $dosyaAdi);

                if ($eskiFoto && str_starts_with($eskiFoto, 'tema/uploads/profil/') && is_file(public_path($eskiFoto))) {
                    @unlink(public_path($eskiFoto));
                }

                DB::table('crm_customers')->where('id', $customer->id)
                    ->update(['profil_foto' => 'tema/uploads/profil/' . $dosyaAdi]);
            } elseif ($request->boolean('foto_kaldir')) {
                if ($eskiFoto && str_starts_with($eskiFoto, 'tema/uploads/profil/') && is_file(public_path($eskiFoto))) {
                    @unlink(public_path($eskiFoto));
                }
                DB::table('crm_customers')->where('id', $customer->id)->update(['profil_foto' => null]);
            }
        }

        // SENKRON: Güncellemeden sonra email varsa ve henüz üye değilse üye oluştur.
        // (uyelereSenkronla yeni üye oluştururken 'sifre' alanını zaten kullanır.)
        $this->uyelereSenkronla($customer->fresh(), $request);

        // ŞİFRE DEĞİŞTİR: Üye zaten varsa, admin yeni şifre girdiyse onu güncelle.
        $sifreMesaj = null;
        if ($request->filled('sifre') && !empty($customer->email) && Schema::hasTable('uyeler')) {
            $uye = DB::table('uyeler')->where('email', $customer->email)->first();
            if ($uye) {
                DB::table('uyeler')->where('id', $uye->id)->update([
                    'sifre' => Hash::make((string) $request->input('sifre')),
                ]);
                $sifreMesaj = ' Giriş şifresi güncellendi.';
            }
        }

        return redirect()->route('admin.crm.musteriler.edit', $customer->id)
            ->with('success', 'Müşteri bilgileri güncellendi.' . ($sifreMesaj ?? ''));
    }

    public function updateBilgiler(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'adi'           => 'required|string|max:150',
            'unvan'         => 'nullable|string|max:150',
            'firma_tipi'    => 'nullable|in:sahis,limited,anonim,kollektif,komandit,kooperatif,diger',
            'email'         => 'nullable|email|max:150',
            'telefon'       => 'nullable|string|max:50',
            'gsm'           => 'nullable|string|max:50',
            'web_sitesi'    => 'nullable|string|max:190',
            'sektor'        => 'nullable|string|max:100',
            'kaynak'        => 'nullable|string|max:100',
            'durum'         => 'nullable|string|max:50',
            'sorumlu_id'    => 'nullable|integer|exists:yoneticiler,id',
            'vergi_dairesi' => 'nullable|string|max:100',
            'vergi_no'      => 'nullable|string|max:50',
            'tc_kimlik'     => 'nullable|string|max:20',
            'il'            => 'nullable|string|max:80',
            'ilce'          => 'nullable|string|max:80',
            'adres'         => 'nullable|string|max:1000',
            'dogum_tarihi'  => 'nullable|date',
            'bayi_mi'       => 'nullable|boolean',
            'bayi_tarihi'   => 'nullable|date',
            'bayi_komisyon' => 'nullable|numeric|min:0|max:100',
            'not_icerik'    => 'nullable|string|max:5000',
        ]);

        $validated['bayi_mi'] = $request->boolean('bayi_mi');
        if ($validated['bayi_mi'] && empty($customer->bayi_tarihi) && empty($validated['bayi_tarihi'])) {
            $validated['bayi_tarihi'] = now()->toDateString();
        }

        $customer->update($validated);

        return back()->with('success', 'Müşteri bilgileri güncellendi.');
    }

    public function bakiyeHareketi(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'tutar'    => 'required|numeric|min:0.01',
            'tip'      => 'required|in:ekle,dus',
            'aciklama' => 'nullable|string|max:255',
        ]);

        $miktar = round((float) $validated['tutar'], 2);
        $delta  = $validated['tip'] === 'dus' ? -$miktar : $miktar;

        DB::transaction(function () use ($customer, $delta, $validated) {
            $customer->bakiye = (float) ($customer->bakiye ?? 0) + $delta;
            $customer->save();

            DB::table('crm_customer_bakiye_hareketleri')->insert([
                'musteri_id'  => $customer->id,
                'tutar'       => $delta,
                'tip'         => $validated['tip'],
                'aciklama'    => $validated['aciklama'] ?? null,
                'yonetici_id' => session('admin_id'),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        });

        // Bakiye EKLENDİYSE müşteriye bilgi maili (düşme bildirilmez)
        if ($validated['tip'] === 'ekle' && !empty($customer->email)) {
            try {
                $mesaj = "<p style='margin:0 0 14px'>Hesabınıza bakiye eklendi:</p>"
                       . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border:1px solid #eceee6;border-radius:10px;overflow:hidden'>"
                       . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;border-bottom:1px solid #eceee6;font-size:13px;color:#7a8270;width:45%'>Eklenen Tutar</td><td style='padding:12px 16px;border-bottom:1px solid #eceee6;font-size:16px;font-weight:700;color:#6f7320'>₺" . number_format($miktar, 2, ',', '.') . "</td></tr>"
                       . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;font-size:13px;color:#7a8270'>Güncel Bakiye</td><td style='padding:12px 16px;font-size:14px;font-weight:600'>₺" . number_format((float) $customer->bakiye, 2, ',', '.') . "</td></tr>"
                       . "</table>";
                \App\Services\CustomerNotifier::crmMusteriyeMail(
                    $customer->id,
                    '💰 Bakiyeniz Güncellendi',
                    $mesaj,
                    null,
                    null,
                    'bakiye_eklendi'
                );
            } catch (\Throwable $e) {
                \Log::warning('Bakiye hareketi maili gönderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return back()->with('success', $validated['tip'] === 'ekle'
            ? 'Bakiye eklendi.'
            : 'Bakiyeden düşüldü.');
    }

    /**
     * Müşteriye bağlı ÜYE (giriş hesabı) kaydındaki veri sayıları.
     * Silme kararını bunlar belirler: hepsi 0 ise üye kaydı da silinebilir.
     */
    protected function uyeVeriSayilari(?int $uyeId): array
    {
        $bos = ['fatura' => 0, 'hizmet' => 0, 'destek' => 0, 'bayi' => 0, 'toplam' => 0];
        if (!$uyeId) return $bos;

        $say = function (string $tablo, string $kolon) use ($uyeId): int {
            try {
                if (!Schema::hasTable($tablo) || !Schema::hasColumn($tablo, $kolon)) return 0;
                return (int) DB::table($tablo)->where($kolon, $uyeId)->count();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $s = [
            'fatura' => $say('faturalar', 'uyeid'),
            'hizmet' => $say('satilanlar', 'uyeid'),
            'destek' => $say('destek', 'uyeid'),
            'bayi'   => $say('bayiler', 'uye_id'),
        ];
        $s['toplam'] = array_sum($s);

        return $s;
    }

    /** "3 fatura, 2 hizmet" gibi okunur özet */
    protected function uyeVeriOzeti(array $s): string
    {
        $etiket = ['fatura' => 'fatura', 'hizmet' => 'hizmet/domain', 'destek' => 'destek talebi', 'bayi' => 'bayi hesabı'];
        $parca = [];
        foreach ($etiket as $anahtar => $ad) {
            if (!empty($s[$anahtar])) $parca[] = $s[$anahtar] . ' ' . $ad;
        }
        return implode(', ', $parca);
    }

    /**
     * Bu üye kaydını BAŞKA bir CRM müşterisi de kullanıyor mu?
     *
     * Sistemde aynı e-postayı taşıyan mükerrer CRM kayıtları var. Birini silerken
     * ortak üye hesabını da silersek diğer CRM kaydı sahipsiz kalır. Bu yüzden
     * üye kaydı yalnızca BAŞKA hiçbir CRM kaydı ona bağlı değilse silinir.
     */
    protected function uyeBaskaMusteridemiKullaniliyor(int $uyeId, int $haricMusteriId, ?string $email): bool
    {
        try {
            $sorgu = DB::table('crm_customers')->where('id', '<>', $haricMusteriId);

            $sorgu->where(function ($w) use ($uyeId, $email) {
                if (Schema::hasColumn('crm_customers', 'uye_id')) {
                    $w->orWhere('uye_id', $uyeId);
                }
                if (!empty($email)) {
                    $w->orWhereRaw('LOWER(email) = ?', [mb_strtolower($email, 'UTF-8')]);
                }
            });

            return $sorgu->exists();
        } catch (\Throwable $e) {
            // Emin olamıyorsak silme — güvenli taraf
            return true;
        }
    }

    /**
     * Müşteri sil — CRM kaydı + (veri yoksa) bağlı ÜYE kaydı birlikte.
     *
     * NEDEN: crm_customers ve uyeler iki AYRI tablo. Eskiden burada yalnızca CRM
     * kaydı siliniyordu; üye (giriş hesabı) yerinde kalıyordu. Sonuç: müşteri
     * panelden görünmez oluyor ama hesabı çalışmaya devam ediyor, mükerrer
     * kayıtlar da hiç azalmıyordu. Artık:
     *   - üyede hiç veri yoksa  → CRM + üye birlikte silinir
     *   - üyede veri varsa      → yalnızca CRM kaydı silinir + UYARI gösterilir
     */
    public function destroy(int $id)
    {
        $customer = Customer::findOrFail($id);

        /*
         * GERİ ALINABİLİR SİLME
         *
         * Silmeden ÖNCE müşteri kartının ve birlikte silinecek alt kayıtların
         * (görev, not, fırsat) tamamı İşlem Geçmişi'ne yazılır. Yanlışlıkla
         * silinirse İşlem Geçmişi → "Geri Al" ile hepsi geri gelir.
         */
        \App\Services\IslemGecmisi::silmeKaydet(
            'crm_customers',
            $id,
            'Müşteri silindi: ' . mb_substr((string) $customer->adi, 0, 80),
            [
                ['tablo' => 'crm_tasks',         'kolon' => 'musteri_id'],
                ['tablo' => 'crm_notes',         'kolon' => 'musteri_id'],
                ['tablo' => 'crm_opportunities', 'kolon' => 'musteri_id'],
            ]
        );

        $uyeId   = $this->uyeIdBul($customer);
        $sayilar = $this->uyeVeriSayilari($uyeId);
        $paylasimli = $uyeId
            ? $this->uyeBaskaMusteridemiKullaniliyor((int) $uyeId, (int) $customer->id, $customer->email)
            : false;
        $uyeSilindi = false;

        DB::transaction(function () use ($customer, $uyeId, $sayilar, $paylasimli, &$uyeSilindi) {
            $customer->tasks()->delete();
            $customer->notes()->delete();
            $customer->opportunities()->delete();
            $customer->delete();

            // Üye kaydı yalnızca TAMAMEN boşsa VE başka müşteri kullanmıyorsa silinir
            if ($uyeId && $sayilar['toplam'] === 0 && !$paylasimli) {
                DB::table('uyeler')->where('id', $uyeId)->delete();
                $uyeSilindi = true;
            }
        });

        if (!$uyeSilindi && $uyeId && $paylasimli) {
            return redirect()->route('admin.crm.musteriler.index')->with('warning',
                'Müşteri kaydı silindi. Giriş hesabı (üye #' . $uyeId . ') SİLİNMEDİ, çünkü aynı hesaba bağlı '
                . 'başka bir müşteri kaydı daha var (mükerrer kayıt). Diğer kayıt da silinince hesap temizlenecek.');
        }

        if ($uyeSilindi) {
            $mesaj = 'Müşteri kaydı ve giriş hesabı (üye #' . $uyeId . ') birlikte silindi.';
            return redirect()->route('admin.crm.musteriler.index')->with('success', $mesaj);
        }

        if ($uyeId) {
            $mesaj = 'Müşteri kaydı silindi. ANCAK giriş hesabı (üye #' . $uyeId . ') SİLİNMEDİ, çünkü bağlı kayıtları var: '
                . $this->uyeVeriOzeti($sayilar) . '. Bu veriler kaybolmasın diye hesap korundu.';
            return redirect()->route('admin.crm.musteriler.index')->with('warning', $mesaj);
        }

        return redirect()->route('admin.crm.musteriler.index')
            ->with('success', 'Müşteri kaydı silindi. (Bağlı bir giriş hesabı yoktu.)');
    }

    public function importFromUyeler()
    {
        try {
            $uyeler = DB::table('uyeler')
                ->where('durum', 1)
                ->get();
            
            $imported = 0;
            $skipped = 0;
            
            foreach ($uyeler as $uye) {
                // Email'e göre zaten var mı kontrol et
                if ($uye->email && Customer::where('email', $uye->email)->exists()) {
                    $skipped++;
                    continue;
                }
                
                // Ad ve soyad birleÅŸtir
                $adi = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? ''));
                if (empty($adi)) {
                    $adi = $uye->email ?? 'İsimsiz Müşteri';
                }
                
                // Durum belirle
                $durum = 'aktif';
                if ($uye->durum == 0) {
                    $durum = 'pasif';
                }
                
                Customer::create([
                    'adi' => $adi,
                    'unvan' => $uye->firmaadi ?? null,
                    'email' => $uye->email ?? null,
                    'telefon' => $uye->telefon ?? null,
                    'durum' => $durum,
                    'kaynak' => 'Sistem İçe Aktarım',
                    'created_at' => $uye->tarih ?? now(),
                    'updated_at' => now(),
                ]);
                
                $imported++;
            }
            
            return redirect()->route('admin.crm.musteriler.index')
                ->with('success', "İçe aktarma tamamlandı! {$imported} müşteri eklendi, {$skipped} müşteri atlandı (zaten mevcut).");
                
        } catch (\Exception $e) {
            \Log::error('CRM müşteri içe aktarma hatası', ['error' => $e->getMessage()]);
            return redirect()->route('admin.crm.musteriler.index')
                ->with('error', 'İçe aktarma sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    public function durumToggle(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);
        // İstenen durum gönderildiyse onu kullan, yoksa sıradaki sıcaklığa geç (soğuk→ılımlı→sıcak)
        $istenen = $request->input('durum');
        $yeniDurum = array_key_exists($istenen, Customer::DURUMLAR)
            ? $istenen
            : Customer::durumSonraki($customer->durum);
        $customer->durum = $yeniDurum;
        $customer->save();

        $label = Customer::durumBilgi($yeniDurum)['label'];
        return redirect()->back()->with('success', 'Müşteri durumu "' . $label . '" yapıldı.');
    }

    public function storeNote(Request $request, int $id)
    {
        $request->validate([
            'baslik' => 'nullable|string|max:150',
            'icerik' => 'required|string',
            'dosya'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ]);

        $customer = Customer::findOrFail($id);
        $olusturanId = session('admin_id');
        $olusturanAdi = session('admin_adi', 'Admin');
        $icerik = $request->input('icerik');

        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $dosyaYolu = $request->file('dosya')->store('notlar', 'public');
        }

        $note = \App\Models\CRM\Note::create([
            'musteri_id'   => $customer->id,
            'olusturan_id' => $olusturanId,
            'baslik'       => $request->baslik,
            'icerik'       => $icerik,
            'dosya'        => $dosyaYolu,
        ]);

        // @mention parse — kullanici_adi'na göre yonetici bul + mail
        if (preg_match_all('/@([a-zA-Z0-9_\.]+)/', $icerik, $matches)) {
            $usernames = array_unique($matches[1]);
            $mentioned = DB::table('yoneticiler')
                ->whereIn('kullaniciadi', $usernames)
                ->where('durum', 1)
                ->select('id', 'kullaniciadi', 'adi', 'email', 'eposta')
                ->get();

            foreach ($mentioned as $m) {
                $to = $m->email ?: $m->eposta;
                if (!$to) continue;
                try {
                    $url = url('/admin/crm/musteriler/' . $customer->id . '?tab=notlar');
                    $subject = '🔔 ' . $olusturanAdi . ' sizi bir notta etiketledi';
                    $html = '<h3 style="color:#1f2419">Merhaba ' . e($m->adi ?? $m->kullaniciadi) . ',</h3>'
                          . '<p><strong style="color:#6f7320">' . e($olusturanAdi) . '</strong> seni "<strong>' . e($customer->ad . ' ' . ($customer->soyad ?? '')) . '</strong>" müşterisinin notunda etiketledi.</p>'
                          . '<blockquote style="border-left:4px solid #b8b62e;padding:12px 16px;background:#f7f8f3;color:#3a4133;border-radius:8px;margin:14px 0">' . nl2br(e($icerik)) . '</blockquote>'
                          . '<p><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#b8b62e;color:#1f2419;text-decoration:none;border-radius:10px;font-weight:700">Notu Gör →</a></p>';
                    \App\Services\EmailNotificationService::send($to, $subject, $html);
                } catch (\Throwable $e) {
                    \Log::warning('Mention mail gonderilemedi', ['to' => $to, 'err' => $e->getMessage()]);
                }
            }
        }

        return redirect()->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'notlar'])
            ->with('success', 'Not eklendi' . (isset($mentioned) && $mentioned->isNotEmpty() ? ' + ' . count($mentioned) . ' admin etiketlendi (mail gönderildi)' : ''));
    }

    public function deleteNote(int $id, int $noteId)
    {
        \App\Models\CRM\Note::where('id', $noteId)->where('musteri_id', $id)->delete();
        return redirect()->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'notlar'])
            ->with('success', 'Not silindi.');
    }

    public function storeRapor(Request $request, int $id)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'icerik' => 'required|string',
            'tutar'  => 'nullable|numeric|min:0',
            'dosya'  => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);
 
        $customer = Customer::findOrFail($id);
 
        // FIX: Email kontrolü
        $uye = null;
        if (!empty($customer->email)) {
            $uye = DB::table('uyeler')
                ->whereRaw('LOWER(email) = ?', [strtolower(trim($customer->email))])
                ->first();
        }
 
        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            // FIX: Recursive klasör oluşturma + permission
            $dir = public_path('uploads/raporlar');
 
            // Üst klasörü garanti et
            $uploadsDir = public_path('uploads');
            if (!is_dir($uploadsDir)) {
                if (!@mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
                    return back()
                        ->withInput()
                        ->with('error', 'uploads klasörü oluşturulamadı. cPanel\'den public/uploads klasörünü manuel oluşturun ve 755 izni verin.');
                }
                @chmod($uploadsDir, 0775);
            }
 
            // raporlar klasörü
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    return back()
                        ->withInput()
                        ->with('error', 'raporlar klasörü oluşturulamadı. cPanel\'den public/uploads/raporlar klasörünü manuel oluşturun ve 755 izni verin.');
                }
                @chmod($dir, 0775);
            }
 
            // Yazma izni kontrolü
            if (!is_writable($dir)) {
                return back()
                    ->withInput()
                    ->with('error', 'raporlar klasörü yazılabilir değil. cPanel\'den 755 izni verin.');
            }
 
            try {
                $file = $request->file('dosya');
                $ad = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'rapor';
                $fileName = $ad . '-' . time() . '.' . strtolower($file->getClientOriginalExtension());
                $file->move($dir, $fileName);
                $dosyaYolu = 'uploads/raporlar/' . $fileName;
            } catch (\Throwable $e) {
                return back()
                    ->withInput()
                    ->with('error', 'Dosya yüklenemedi: ' . $e->getMessage());
            }
        }
 
        $raporId = DB::table('musteri_raporlar')->insertGetId([
            'uyeid'          => $uye?->id,
            'crm_musteri_id' => $id,
            'baslik'         => $request->baslik,
            'icerik'         => $request->icerik,
            'tutar'          => $request->tutar,
            'dosya'          => $dosyaYolu,
            'durum'          => 'yeni',
            'tarih'          => date('Y-m-d H:i:s'),
            'dil'            => 1,
        ]);
 
        // Bildirim gönderimi (sadece uye varsa)
        if ($uye) {
            try {
                $this->raporBildirimGonder($raporId, $customer, $uye);
            } catch (\Throwable $e) {}
        }
 
        return redirect()
            ->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'raporlar'])
            ->with('success', $uye
                ? 'Rapor eklendi ve müşteriye bildirim gönderildi.'
                : 'Rapor eklendi. (Müşterinin üye kaydı olmadığı için bildirim gönderilmedi.)');
    }

    public function resendRapor(int $id, int $raporId)
    {
        $customer = Customer::findOrFail($id);
        $uye = DB::table('uyeler')->where('email', $customer->email)->first();
        $this->raporBildirimGonder($raporId, $customer, $uye);
        return back()->with('success', 'Rapor bildirimi tekrar gönderildi.');
    }

    public function deleteRapor(int $id, int $raporId)
    {
        DB::table('musteri_raporlar')->where('id', $raporId)->delete();
        return back()->with('success', 'Rapor silindi.');
    }

    protected function raporBildirimGonder(int $raporId, $customer, $uye): void
    {
        $rapor = DB::table('musteri_raporlar')->where('id', $raporId)->first();
        if (!$rapor) return;

        // 1) Üye bildirim (in-app)
        if ($uye && function_exists('uye_bildirim_gonder')) {
            try {
                uye_bildirim_gonder(
                    $uye->id,
                    '📊 Yeni Rapor: ' . $rapor->baslik,
                    'Size yeni bir rapor gönderildi. Detayları görmek için panele giriş yapın.',
                    'info',
                    url('/raporlarim'),
                    'mdi-chart-bar'
                );
            } catch (\Throwable $e) {}
        }

        // 2) E-posta
        if (!empty($customer->email)) {
            try {
                $mesaj = "<p style='margin:0 0 14px'>Size yeni bir rapor gönderildi:</p>"
                       . "<div style='background-color:#f7f8f3;border-left:4px solid #b8b62e;border-radius:8px;padding:16px 18px'>"
                       . "<strong style='font-size:16px;color:#6f7320'>" . e($rapor->baslik) . "</strong>"
                       . "<div style='margin-top:8px;font-size:14px;color:#3a4133;line-height:1.6'>" . nl2br(e(\Illuminate\Support\Str::limit($rapor->icerik, 500))) . "</div>"
                       . (!empty($rapor->tutar) ? "<div style='margin-top:12px;font-weight:700;color:#1f2419'>Tutar: ₺" . number_format((float) $rapor->tutar, 2, ',', '.') . "</div>" : "")
                       . "</div>";
                \App\Services\CustomerNotifier::musteriyeMail(
                    $uye->id ?? ($customer->uye_id ?? $customer->id),
                    '📊 Yeni Rapor: ' . $rapor->baslik,
                    $mesaj,
                    url('/raporlarim'),
                    'Raporlarım Sayfasını Aç',
                    'rapor_gonderildi'
                );
            } catch (\Throwable $e) {
                \Log::warning('Rapor mail gonderilemedi', ['rapor_id' => $raporId, 'err' => $e->getMessage()]);
            }
        }
    }

    public function storeKredi(Request $request, int $id)
    {
        $validated = $request->validate([
            'ana_para'         => 'required|numeric|min:1',
            'faiz_orani'       => 'required|numeric|min:0|max:100',
            'vade_ay'          => 'required|integer|min:1|max:120',
            'baslangic_tarihi' => 'nullable|date',
            'aciklama'         => 'nullable|string|max:500',
        ]);
 
        $customer = Customer::findOrFail($id);
 
        // FIX: Email kontrolü
        if (empty($customer->email)) {
            return back()
                ->withInput()
                ->with('error', 'Müşterinin e-posta adresi tanımlı değil. Kredi için email zorunludur.');
        }
 
        // FIX: Üye yoksa otomatik oluştur
        $uye = DB::table('uyeler')->where('email', $customer->email)->first();
        if (!$uye) {
            $uyeId = $this->uyeOlustur($customer);
            $uye = DB::table('uyeler')->where('id', $uyeId)->first();
            if (!$uye) {
                return back()->with('error',
                'Müşteri için üye kaydı oluşturulamadı.' .
                (session('uyeOlusturHata') ? ' Detay: ' . session('uyeOlusturHata') : '')
    );
            }
        }
 
        try {
            $krediId = \App\Services\KrediService::krediAc(
                $uye->id, $customer->id,
                (float) $validated['ana_para'],
                (float) $validated['faiz_orani'],
                (int) $validated['vade_ay'],
                $validated['baslangic_tarihi'] ?? null,
                $validated['aciklama'] ?? null,
                session('admin_id')
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Kredi oluşturulamadı: ' . $e->getMessage());
        }
 
        $kredi = DB::table('musteri_krediler')->where('id', $krediId)->first();
 
        if (function_exists('uye_bildirim_gonder')) {
            try {
                uye_bildirim_gonder($uye->id, '💳 Yeni Kredi: ' . $kredi->kredi_no,
                    'Size ₺' . number_format((float) $kredi->ana_para, 2, ',', '.')
                    . ' kredi tanımlandı. Aylık taksit: ₺' . number_format((float) $kredi->aylik_taksit, 2, ',', '.')
                    . ' × ' . $kredi->vade_ay . ' ay.',
                    'success', url('/kredilerim'), 'mdi-cash-multiple');
            } catch (\Throwable $e) {}
        }
 
        try {
            $mesaj = "<p style='margin:0 0 14px'>Hesabınıza yeni bir kredi tanımlandı:</p>"
                   . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border:1px solid #eceee6;border-radius:10px;overflow:hidden'>"
                   . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;border-bottom:1px solid #eceee6;font-size:13px;color:#7a8270;width:40%'>Kredi No</td><td style='padding:12px 16px;border-bottom:1px solid #eceee6;font-size:14px;font-weight:600'>" . e($kredi->kredi_no) . "</td></tr>"
                   . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;border-bottom:1px solid #eceee6;font-size:13px;color:#7a8270'>Toplam Tutar</td><td style='padding:12px 16px;border-bottom:1px solid #eceee6;font-size:14px;font-weight:600'>₺" . number_format((float) $kredi->ana_para, 2, ',', '.') . "</td></tr>"
                   . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;border-bottom:1px solid #eceee6;font-size:13px;color:#7a8270'>Vade</td><td style='padding:12px 16px;border-bottom:1px solid #eceee6;font-size:14px;font-weight:600'>" . $kredi->vade_ay . " ay " . (((float) $kredi->faiz_orani) == 0 ? "<span style='color:#16a34a'>(faizsiz)</span>" : "") . "</td></tr>"
                   . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;font-size:13px;color:#7a8270'>Aylık Taksit</td><td style='padding:12px 16px;font-size:16px;font-weight:700;color:#6f7320'>₺" . number_format((float) $kredi->aylik_taksit, 2, ',', '.') . "</td></tr>"
                   . "</table>";
            \App\Services\CustomerNotifier::musteriyeMail(
                $uye->id,
                '💳 Yeni Kredi: ' . $kredi->kredi_no,
                $mesaj,
                url('/kredilerim'),
                'Kredilerim Sayfasını Aç',
                'kredi_tanimlandi'
            );
        } catch (\Throwable $e) {}
 
        // FIX: Krediler tab'ine yönlendir
        return redirect()
            ->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'krediler'])
            ->with('success', "Kredi açıldı: {$kredi->kredi_no} • Aylık ₺" . number_format((float) $kredi->aylik_taksit, 2, ',', '.') . " × {$kredi->vade_ay} ay");
    }

    public function odemeTaksit(Request $request, int $id, int $taksitId)
    {
        $validated = $request->validate([
            'tutar' => 'required|numeric|min:0.01',
            'not'   => 'nullable|string|max:255',
        ]);
        \App\Services\KrediService::taksitOde($taksitId, (float) $validated['tutar'], null, $validated['not'] ?? null);
        return back()->with('success', 'Taksit ödemesi kaydedildi.');
    }

    public function deleteKredi(int $id, int $krediId)
    {
        DB::table('musteri_kredi_taksitleri')->where('kredi_id', $krediId)->delete();
        DB::table('musteri_krediler')->where('id', $krediId)->delete();
        return back()->with('success', 'Kredi ve tüm taksitleri silindi.');
    }

    // ============================================================
    // Tab Quick-Add: müşteri profili içinde her tab'a "Yeni" formu
    // ============================================================

    protected function uyeIdBul($customer): ?int
    {
        if (empty($customer->email)) return null;
        return DB::table('uyeler')->where('email', $customer->email)->value('id');
    }
    
    protected function uyeOlustur($customer): ?int
    {
        if (empty($customer->email)) return null;
 
        // Tekrar kontrol — race condition güvenliği
        $existing = DB::table('uyeler')->where('email', $customer->email)->value('id');
        if ($existing) return (int) $existing;
 
        try {
            // Müşteri adı parse et
            $tamAd = trim(($customer->adi ?? '') . ' ' . ($customer->soyad ?? ''));
            if (empty($tamAd)) $tamAd = $customer->unvan ?? $customer->email;
 
            $parcalar = explode(' ', $tamAd, 2);
            $ad    = $parcalar[0] ?? '';
            $soyad = $parcalar[1] ?? '';
 
            // Otomatik şifre — admin sonra reset edebilir
            $rastgele = \Illuminate\Support\Str::random(12);
 
            // FIX: Hangi kolonlar gerçekten varsa onları öğren
            $kolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('uyeler');
 
            // Tüm POTANSİYEL alanlar (tabloda olmayanlar otomatik filtrelenecek)
            $potansiyel = [
                'ad'           => $ad,
                'soyad'        => $soyad,
                'adi_soyadi'   => $tamAd,
                'email'        => $customer->email,
                'eposta'       => $customer->email,
                'telefon'      => $customer->telefon ?? '',
                'gsm'          => $customer->telefon ?? '',
                'sifre'        => \Illuminate\Support\Facades\Hash::make($rastgele),
                'utipi'        => 0,
                'tc'           => null,
                'tc_no'        => null,
                'dtarih'       => null,
                'cinsiyet'     => 'Erkek',
                'firmaadi'     => $customer->unvan ?? $customer->firma ?? '',
                'firma'        => $customer->unvan ?? $customer->firma ?? '',
                'vergino'      => null,
                'vergidairesi' => null,
                'il'           => $customer->il ?? '',
                'sehir'        => $customer->il ?? '',
                'ilce'         => $customer->ilce ?? '',
                'pkodu'        => null,
                'adres'        => $customer->adres ?? '',
                'durum'        => 1,
                'bakiye'       => 0,
                'tarih'        => date('Y-m-d H:i:s'),
                'ktarih'       => date('Y-m-d H:i:s'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
 
            // FIX: Sadece var olan kolonları al — şema bağımsız insert
            $data = array_intersect_key($potansiyel, array_flip($kolonlar));
 
            $uyeId = DB::table('uyeler')->insertGetId($data);
 
            return (int) $uyeId;
 
        } catch (\Throwable $e) {
            // Hata mesajını session'a koy ki kullanıcı detaylı görebilsin
            $mesaj = 'Otomatik üye oluşturma başarısız: ' . $e->getMessage();
 
            \Log::error($mesaj, [
                'customer_id' => $customer->id ?? null,
                'email'       => $customer->email ?? null,
            ]);
 
            session()->flash('uyeOlusturHata', $mesaj);
            return null;
        }
    }
 
 

    public function storeFatura(Request $request, int $id)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'tutar'  => 'required|numeric|min:0',
            'hizmet' => 'nullable|string|max:255',
            'odeme_yontemi' => 'nullable|string|max:50',
            'durum'  => 'nullable|integer',
            'bitis_tarih'   => 'nullable|date',
            'aciklama'      => 'nullable|string',
            'dosya'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ]);
 
        $customer = Customer::findOrFail($id);
 
        // FIX: Email kontrolü — email yoksa fatura kaydedilemez
        if (empty($customer->email)) {
            return back()
                ->withInput()
                ->with('error', 'Müşterinin e-posta adresi tanımlı değil. Fatura için email zorunludur. Önce müşteri bilgilerini güncelleyin.');
        }
 
        // FIX: Üye yoksa otomatik oluştur (fatura sistemi uyeid bekliyor)
        $uyeId = $this->uyeIdBul($customer);
        if (!$uyeId) {
            $uyeId = $this->uyeOlustur($customer);
        }
 
        $faturaNo = 'F-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
 
        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $dosyaYolu = $request->file('dosya')->store('faturalar', 'public');
        }
 
        $faturaId = DB::table('faturalar')->insertGetId([
            'fatura_no'     => $faturaNo,
            'uyeid'         => $uyeId,
            'mail'          => $customer->email,
            'baslik'        => $request->baslik,
            'tutar'         => $request->tutar,
            'hizmet'        => $request->hizmet,
            'bitis_tarih'   => $request->bitis_tarih,
            'durum'         => (int) ($request->durum ?? 0),
            'aciklama'      => $request->aciklama,
            'odeme_yontemi' => $request->odeme_yontemi ?? 'havale',
            'dosya'         => $dosyaYolu,
            'tarih'         => date('Y-m-d H:i:s'),
        ]);
 
        $ekMesaj = '';

        // 📧 Müşteriye fatura maili (sadece checkbox işaretliyse)
        if ($request->mail_gonder == 1) {
            try {
                \App\Services\CustomerNotifier::faturaKesildi($uyeId, $faturaId);
                $ekMesaj .= ' Bilgilendirme maili gönderildi.';
            } catch (\Throwable $e) {
                \Log::warning('Fatura maili gönderilemedi', ['err' => $e->getMessage()]);
                $ekMesaj .= ' (Mail gönderilemedi.)';
            }
        }

        // 📱 GÖREV #217/5 — Müşteriye fatura SMS'i (mail'den bağımsız seçenek)
        if ($request->sms_gonder == 1) {
            $tel = trim((string) ($uyeId ? DB::table('uyeler')->where('id', $uyeId)->value('telefon') : ''))
                ?: trim((string) ($customer->telefon ?? ''));

            if ($tel === '') {
                $ekMesaj .= ' (SMS gönderilemedi: telefon kayıtlı değil.)';
            } else {
                try {
                    $tutarYazi = number_format((float) $request->tutar, 2, ',', '.');
                    $vade = $request->bitis_tarih
                        ? \Carbon\Carbon::parse($request->bitis_tarih)->format('d.m.Y') : null;

                    $mesaj = 'Sayin ' . ($customer->adi ?: 'Musterimiz') . ', ' . $faturaNo
                        . ' nolu faturaniz olusturuldu. Tutar: ' . $tutarYazi . ' TL.'
                        . ($vade ? ' Son odeme tarihi: ' . $vade . '.' : '');

                    $sonuc = (new \App\Services\SmsService())->send($tel, $mesaj);
                    $ekMesaj .= !empty($sonuc['success'])
                        ? ' Bilgilendirme SMS\'i gönderildi (' . $tel . ').'
                        : ' (SMS gönderilemedi: ' . ($sonuc['message'] ?? 'servis kapalı olabilir') . ')';
                } catch (\Throwable $e) {
                    \Log::warning('Fatura SMS gönderilemedi', ['fatura' => $faturaId, 'err' => $e->getMessage()]);
                    $ekMesaj .= ' (SMS gönderilemedi.)';
                }
            }
        }
 
        // FIX: Faturalar tab'ine yönlendir (görmek istediği yer)
        return redirect()
            ->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'faturalar'])
            ->with('success', "Fatura oluşturuldu: {$faturaNo}." . $ekMesaj);
    }

    public function storeHizmet(Request $request, int $id)
    {
        $request->validate([
            'hizmet_id' => 'nullable|integer',
            'baslik'    => 'required|string|max:255',
            'tutar'     => 'required|numeric|min:0',
            'baslangic_tarih' => 'nullable|date',
            'bitis_tarih'     => 'nullable|date',
            'aciklama'  => 'nullable|string',
            'dosya'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ]);

        $customer = Customer::findOrFail($id);
        $uyeId = $this->uyeIdBul($customer);

        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $dosyaYolu = $request->file('dosya')->store('hizmetler', 'public');
        }

        $satisId = DB::table('satilanlar')->insertGetId([
            'uyeid'           => $uyeId,
            'crm_musteri_id'  => $id,
            'tipi'            => '1', // hizmet
            'paket'           => $request->hizmet_id,
            'paket_baslik'    => $request->baslik,
            'tutar'           => $request->tutar,
            'baslangic_tarih' => $request->baslangic_tarih ?? date('Y-m-d'),
            'bitis_tarih'     => $request->bitis_tarih,
            'mesaj'           => $request->aciklama,
            'dosya'           => $dosyaYolu,
            'durum'           => 1,
            'tarih'           => date('Y-m-d H:i:s'),
        ]);

        // 📧 Müşteriye hizmet maili (sadece checkbox işaretliyse; satilanlar ortak)
        if ($request->mail_gonder == 1 && $uyeId) {
            try {
                \App\Services\CustomerNotifier::hostingAlindi($uyeId ?: $customer->email, $satisId, $request->baslik);
            } catch (\Throwable $e) {
                \Log::warning('Hizmet maili gönderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Hizmet satışı eklendi.');
    }

    public function storeHosting(Request $request, int $id)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'tutar'  => 'required|numeric|min:0',
            'domain' => 'nullable|string|max:255',
            'baslangic_tarih' => 'nullable|date',
            'bitis_tarih'     => 'nullable|date',
            'dosya'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ]);

        $customer = Customer::findOrFail($id);
        $uyeId = $this->uyeIdBul($customer);

        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $dosyaYolu = $request->file('dosya')->store('hostingler', 'public');
        }

        $satisId = DB::table('satilanlar')->insertGetId([
            'uyeid'           => $uyeId,
            'crm_musteri_id'  => $id,
            'tipi'            => '2', // hosting
            'hosting_baslik'  => $request->baslik,
            'domain'          => $request->domain,
            'tutar'           => $request->tutar,
            'baslangic_tarih' => $request->baslangic_tarih ?? date('Y-m-d'),
            'bitis_tarih'     => $request->bitis_tarih ?? date('Y-m-d', strtotime('+1 year')),
            'dosya'           => $dosyaYolu,
            'durum'           => 1,
            'tarih'           => date('Y-m-d H:i:s'),
        ]);

        // 📧 Müşteriye hosting maili (sadece checkbox işaretliyse)
        if ($request->mail_gonder == 1) {
            try {
                \App\Services\CustomerNotifier::hostingAlindi($uyeId ?: $customer->email, $satisId, $request->baslik);
            } catch (\Throwable $e) {
                \Log::warning('Hosting maili gönderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Hosting satışı eklendi.');
    }

    public function storeDomain(Request $request, int $id)
    {
        $request->validate([
            'domain' => 'required|string|max:255',
            'tutar'  => 'nullable|numeric|min:0',
            'baslangic_tarih' => 'nullable|date',
            'bitis_tarih'     => 'nullable|date',
            'saglayici'       => 'nullable|string|max:100',
            'dosya'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ]);

        $customer = Customer::findOrFail($id);
        $uyeId = $this->uyeIdBul($customer);

        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $dosyaYolu = $request->file('dosya')->store('domainler', 'public');
        }

        $satisId = DB::table('satilanlar')->insertGetId([
            'uyeid'           => $uyeId,
            'crm_musteri_id'  => $id,
            'tipi'            => '3', // domain
            'domain'          => strtolower(trim($request->domain)),
            'tutar'           => $request->tutar ?? 0,
            'baslangic_tarih' => $request->baslangic_tarih ?? date('Y-m-d'),
            'bitis_tarih'     => $request->bitis_tarih ?? date('Y-m-d', strtotime('+1 year')),
            'dosya'           => $dosyaYolu,
            'durum'           => 1,
            'tarih'           => date('Y-m-d H:i:s'),
        ]);

        // 📧 Müşteriye domain maili (sadece checkbox işaretliyse)
        if ($request->mail_gonder == 1) {
            try {
                \App\Services\CustomerNotifier::domainAlindi($uyeId, $satisId);
            } catch (\Throwable $e) {
                \Log::warning('Domain maili gönderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Domain eklendi.');
    }

    public function storeEfatura(Request $request, int $id)
    {
        $request->validate([
            'baslik' => 'required|string|max:255',
            'tutar'  => 'required|numeric|min:0',
            'icerik' => 'nullable|string',
            'dosya'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ]);

        $customer = Customer::findOrFail($id);
        $uyeId = $this->uyeIdBul($customer);

        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $dosyaYolu = $request->file('dosya')->store('efaturalar', 'public');
        }

        $efaturaId = DB::table('musteri_efaturalar')->insertGetId([
            'uyeid'          => $uyeId,
            'crm_musteri_id' => $id,
            'baslik' => $request->baslik,
            'icerik' => $request->icerik,
            'tutar'  => $request->tutar,
            'dosya'  => $dosyaYolu,
            'durum'  => 'beklemede',
            'tarih'  => date('Y-m-d H:i:s'),
            'dil'    => 1,
        ]);

        // 📧 Müşteriye e-fatura maili (sadece checkbox işaretliyse; faturaKesildi şablonu)
        if ($request->mail_gonder == 1 && $uyeId) {
            try {
                \App\Services\CustomerNotifier::faturaKesildi($uyeId, $efaturaId, ['tip' => 'efatura', 'baslik' => $request->baslik, 'tutar' => $request->tutar]);
            } catch (\Throwable $e) {
                \Log::warning('E-Fatura maili gönderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'E-Fatura kaydı eklendi.');
    }

     public function storeDestekMesaj(Request $request, int $id)
    {
        $request->validate([
            'baslik' => 'required_without:ustid|nullable|string|max:255',
            'mesaj'  => 'required|string',
            'ustid'  => 'nullable|integer',
        ]);
 
        $customer = Customer::findOrFail($id);
 
        if (empty($customer->email)) {
            return back()
                ->withInput()
                ->with('error', 'Müşterinin e-posta adresi tanımlı değil. Destek talebi için email zorunludur.');
        }
 
        // Üye yoksa otomatik oluştur
        $uyeId = $this->uyeIdBul($customer);
        if (!$uyeId) {
            $uyeId = $this->uyeOlustur($customer);
        }
        if (!$uyeId) {
            return back()->with('error',
                'Müşteri için üye kaydı oluşturulamadı.' .
                (session('uyeOlusturHata') ? ' Detay: ' . session('uyeOlusturHata') : '')
            );
        }
 
        // Schema-aware: gonderen_tip ve admin_id kolonları var mı?
        $kolonlar = \Illuminate\Support\Facades\Schema::getColumnListing('destek');
        $hasGonderenTip = in_array('gonderen_tip', $kolonlar);
        $hasAdminId     = in_array('admin_id', $kolonlar);
 
        $adminId = (int) (session('admin_id') ?? 0);
 
        // Yardımcı: temel veriye admin işaretlerini ekle
        $adminMark = function($data) use ($hasGonderenTip, $hasAdminId, $adminId) {
            if ($hasGonderenTip) $data['gonderen_tip'] = 'admin';
            if ($hasAdminId)     $data['admin_id']     = $adminId ?: null;
            return $data;
        };
 
        $ustid = $request->ustid;
 
        if (!$ustid) {
            // ═════ YENİ TALEP — ADMIN AÇIYOR ═════
            $talepData = $adminMark([
                'uyeid'      => $uyeId,
                'baslik'     => $request->baslik,
                'mesaj'      => $request->mesaj,
                'departman'  => 'Genel',
                'oncelik'    => 'Normal',
                'durum'      => 0,
                'ip'         => $request->ip(),
                'tarih'      => date('Y-m-d H:i:s'),
                'son_tarih'  => date('Y-m-d H:i:s'),
            ]);
 
            $ustid = DB::table('destek')->insertGetId($talepData);
 
            // Mail bildirimi: admin → müşteri
            $talep = DB::table('destek')->where('id', $ustid)->first();
            $uye   = DB::table('uyeler')->where('id', $uyeId)->first();
            if ($talep && $uye) {
                \App\Services\DestekMailHelper::yeniTalepAdmindenMusteriye($talep, $uye);
            }
 
        } else {
            // ═════ MEVCUT TALEBE ADMIN CEVABI ═════
            $cevapData = $adminMark([
                'uyeid'     => 0,
                'ustid'     => $ustid,
                'baslik'    => 'RE: cevap',
                'mesaj'     => $request->mesaj,
                'durum'     => 1,
                'tarih'     => date('Y-m-d H:i:s'),
                'son_cevap' => date('Y-m-d H:i:s'),
            ]);
 
            $cevapId = DB::table('destek')->insertGetId($cevapData);
 
            DB::table('destek')->where('id', $ustid)->update([
                'son_cevap' => date('Y-m-d H:i:s'),
                'durum'     => 1,
            ]);
 
            // Mail bildirimi: admin → müşteri
            $talep = DB::table('destek')->where('id', $ustid)->first();
            $cevap = DB::table('destek')->where('id', $cevapId)->first();
            $uye   = DB::table('uyeler')->where('id', $talep->uyeid ?? 0)->first();
            if ($talep && $cevap && $uye) {
                \App\Services\DestekMailHelper::cevapAdmindenMusteriye($talep, $cevap, $uye);
            }
        }
 
        return redirect()
            ->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'destek'])
            ->with('success', 'Destek mesajı gönderildi.');
    }
 

    public function storeReferans(Request $request, int $id)
    {
        $request->validate([
            'adi'         => 'required|string|max:255',
            'kisa'        => 'nullable|string|max:500',
            'aciklama'    => 'nullable|string',
            'durum'       => 'nullable|in:0,1',
            'resim_dosya' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $customer = Customer::findOrFail($id);

        $resimYol = null;
        if ($request->hasFile('resim_dosya')) {
            $file = $request->file('resim_dosya');
            $klasor = public_path('uploads/referans');
            $ad = \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'referans';
            $baseName = $ad . '-' . time();
            $dosya = ImageHelper::saveAsWebp($file, $klasor, $baseName);
            $resimYol = 'uploads/referans/' . $dosya;
        }

        $cols = Schema::getColumnListing('referanslar');
        $data = [
            'adi'      => $request->adi,
            'seo'      => \Illuminate\Support\Str::slug($request->adi) . '-' . substr(uniqid(), -4),
            'kisa'     => $request->kisa,
            'aciklama' => $request->aciklama,
            'resim'    => $resimYol,
            'durum'    => (int) $request->input('durum', 1),
            'tarih'    => date('Y-m-d H:i:s'),
            'dil'      => 1,
        ];
        if (in_array('musteri_id', $cols)) $data['musteri_id'] = $customer->id;
        if (in_array('uyeid', $cols)) $data['uyeid'] = $this->uyeIdBul($customer);

        $data = array_intersect_key($data, array_flip($cols));
        DB::table('referanslar')->insert($data);

        // ── REFERANS BONUSU: secilen yeni musterinin ilk faturasinin %10'u, bu musteriye bakiye ──
        $bonusMesaj = '';
        $bonusMusteriId = $request->input('bonus_musteri_id');
        if (!empty($bonusMusteriId)) {
            try {
                $bonusMesaj = $this->referansBonusuUygula($customer, (int) $bonusMusteriId);
            } catch (\Throwable $e) {
                \Log::warning('Referans bonusu uygulanamadi', ['err' => $e->getMessage()]);
                $bonusMesaj = ' (Bonus uygulanamadı: ' . $e->getMessage() . ')';
            }
        }

        return back()->with('success', 'Referans eklendi.' . $bonusMesaj);
    }

    /**
     * Referans getiren musteriye, referans olan yeni musterinin
     * ILK kesilmis faturasinin %10'u kadar bakiye odulu yukler (tek seferlik).
     * Bakiyeyi crm_customers + uyeler + bakiye_gecmisi uzerinde senkron tutar.
     *
     * @param  Customer $alanMusteri  Odulu alacak (referansi getiren) musteri
     * @param  int      $yeniMusteriId Referans olan (yeni gelen) musterinin crm_customers.id'si
     * @return string Sonuc mesaji (success'e eklenir)
     */
    protected function referansBonusuUygula(Customer $alanMusteri, int $yeniMusteriId): string
    {
        // 1) Yeni musteriyi bul
        $yeniMusteri = Customer::find($yeniMusteriId);
        if (!$yeniMusteri) {
            return ' (Bonus: seçilen müşteri bulunamadı.)';
        }

        // 2) Yeni musterinin uye id'si (faturalar uyeid ile bagli)
        $yeniUyeId = $this->uyeIdBul($yeniMusteri);
        if (!$yeniUyeId) {
            return ' (Bonus: seçilen müşterinin üye kaydı yok, fatura bulunamadı.)';
        }

        // 3) ILK kesilmis fatura (en eski)
        $ilkFatura = DB::table('faturalar')
            ->where('uyeid', $yeniUyeId)
            ->orderBy('tarih', 'asc')
            ->orderBy('id', 'asc')
            ->first(['id', 'tutar', 'fatura_no']);

        if (!$ilkFatura || (float) $ilkFatura->tutar <= 0) {
            return ' (Bonus: seçilen müşterinin kesilmiş faturası yok, ödül uygulanmadı.)';
        }

        $bonus = round((float) $ilkFatura->tutar * 0.10, 2);
        if ($bonus <= 0) {
            return ' (Bonus: hesaplanan tutar 0, uygulanmadı.)';
        }

        // 4) Bakiyeyi yukle — crm_customers (mutlak yeni deger)
        $eskiBakiye = (float) ($alanMusteri->bakiye ?? 0);
        $yeniBakiye = round($eskiBakiye + $bonus, 2);

        DB::table('crm_customers')->where('id', $alanMusteri->id)->update(['bakiye' => $yeniBakiye]);

        // 5) CRM bakiye hareketi logu (varsa)
        try {
            if (Schema::hasTable('crm_customer_bakiye_hareketleri')) {
                DB::table('crm_customer_bakiye_hareketleri')->insert([
                    'customer_id'    => $alanMusteri->id,
                    'tip'            => 'yukleme',
                    'tutar'          => $bonus,
                    'bakiye_oncesi'  => $eskiBakiye,
                    'bakiye_sonrasi' => $yeniBakiye,
                    'aciklama'       => 'Referans bonusu (%10) — ' . ($yeniMusteri->adi ?? 'yeni müşteri') . ' ilk fatura: ' . ($ilkFatura->fatura_no ?? ('#' . $ilkFatura->id)),
                    'yonetici_id'    => session('admin_id') ?? null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Referans bonusu CRM hareket log yazilamadi: ' . $e->getMessage());
        }

        // 6) uyeler + bakiye_gecmisi senkron (musteri panelinin okudugu yer)
        try {
            $alanUyeId = $this->uyeIdBul($alanMusteri);
            if ($alanUyeId && Schema::hasColumn('uyeler', 'bakiye')) {
                $uye = DB::table('uyeler')->where('id', $alanUyeId)->first(['id', 'bakiye']);
                if ($uye) {
                    $uyeEski = (float) ($uye->bakiye ?? 0);
                    $uyeYeni = round($uyeEski + $bonus, 2);
                    DB::table('uyeler')->where('id', $alanUyeId)->update(['bakiye' => $uyeYeni]);

                    if (Schema::hasTable('bakiye_gecmisi')) {
                        DB::table('bakiye_gecmisi')->insert([
                            'uye_id'       => $alanUyeId,
                            'tip'          => 'yukleme',
                            'tutar'        => $bonus,
                            'bakiye_once'  => $uyeEski,
                            'bakiye_sonra' => $uyeYeni,
                            'aciklama'     => 'Referans bonusu (%10)',
                            'tarih'        => now(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Referans bonusu uyeler senkron hatasi: ' . $e->getMessage());
        }

        return ' 🎁 Referans bonusu: ₺' . number_format($bonus, 2, ',', '.')
             . ' (' . ($yeniMusteri->adi ?? 'yeni müşteri') . ' ilk faturasının %10\'u) '
             . ($alanMusteri->adi ?? 'müşteri') . ' bakiyesine yüklendi.';
    }

    public function toggleDurum(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);
        // İstenen durum gönderildiyse onu kullan (soguk/ilimli/sicak), yoksa sıradaki sıcaklığa geç
        $istenen = $request->input('durum');
        $yeni = array_key_exists($istenen, Customer::DURUMLAR)
            ? $istenen
            : Customer::durumSonraki($customer->durum);
        $customer->durum = $yeni;
        $customer->save();

        $bilgi = Customer::durumBilgi($yeni);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'durum' => $yeni, 'label' => $bilgi['label'], 'ikon' => $bilgi['ikon'], 'class' => $bilgi['class'], 'renk' => $bilgi['renk']]);
        }
        return back()->with('success', 'Müşteri durumu "' . $bilgi['label'] . '" yapıldı.');
    }

    public function topluIslem(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
            'islem' => 'required|string|in:aktif,pasif,potansiyel,sicak,ilimli,soguk,sil',
        ]);
        $ids = $request->ids;
        $sayi = 0;
        if ($request->islem === 'sil') {
            // Tek tek silmeyle AYNI mantık: alt kayıtları temizle, üye kaydı boşsa onu da sil.
            // (Eskiden burada sadece Customer::delete() vardı — görev/not/fırsat ve üye kaydı
            //  arkada yetim kalıyordu.)
            $uyeSilinen = 0;
            $korunan    = [];

            foreach (Customer::whereIn('id', $ids)->get() as $musteri) {
                $uyeId   = $this->uyeIdBul($musteri);
                $sayilar = $this->uyeVeriSayilari($uyeId);
                $paylasimli = $uyeId
                    ? $this->uyeBaskaMusteridemiKullaniliyor((int) $uyeId, (int) $musteri->id, $musteri->email)
                    : false;

                DB::transaction(function () use ($musteri, $uyeId, $sayilar, $paylasimli, &$uyeSilinen) {
                    $musteri->tasks()->delete();
                    $musteri->notes()->delete();
                    $musteri->opportunities()->delete();
                    $musteri->delete();

                    if ($uyeId && $sayilar['toplam'] === 0 && !$paylasimli) {
                        DB::table('uyeler')->where('id', $uyeId)->delete();
                        $uyeSilinen++;
                    }
                });

                if ($uyeId && $sayilar['toplam'] > 0) {
                    $korunan[] = trim((string) $musteri->adi) . ' (' . $this->uyeVeriOzeti($sayilar) . ')';
                }
                $sayi++;
            }

            $mesaj = "{$sayi} müşteri silindi. Bunlardan {$uyeSilinen} tanesinin giriş hesabı da silindi.";
            if ($korunan) {
                $mesaj .= ' Şu müşterilerin giriş hesabı bağlı kayıtları olduğu için KORUNDU: '
                    . implode(' · ', array_slice($korunan, 0, 10))
                    . (count($korunan) > 10 ? ' ve ' . (count($korunan) - 10) . ' tane daha' : '') . '.';
                return back()->with('warning', $mesaj);
            }

            return back()->with('success', $mesaj);
        }
        // Eski anahtarlar gelirse yeni karşılığına çevir (geriye dönük uyumluluk)
        $legacy = ['sicak' => 'aktif', 'ilimli' => 'potansiyel', 'soguk' => 'pasif'];
        $islem = $legacy[$request->islem] ?? $request->islem;
        $sayi = Customer::whereIn('id', $ids)->update(['durum' => $islem]);
        return back()->with('success', "{$sayi} müşteri {$islem} yapıldı.");
    }

    public function bayiTeklifGonder(Request $request, int $id)
    {
        return $this->teklifGonderInternal($request, $id, 'bayi');
    }

    public function ozelTeklifGonder(Request $request, int $id)
    {
        return $this->teklifGonderInternal($request, $id, 'ozel');
    }

    private function teklifGonderInternal(Request $request, int $id, string $tip)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'kaynak_id'     => 'nullable|integer',
            'urun_tipi'     => 'nullable|string|max:30',
            'paket_adi'     => 'required|string|max:190',
            'tutar'         => 'required|numeric|min:0',
            'odeme_yontemi' => 'required|in:online,havale',
            'mesaj'         => 'nullable|string|max:2000',
        ]);

        if (empty($customer->email)) {
            return back()->with('error', 'Müşterinin e-postası yok, teklif gönderilemez.');
        }

        $token = bin2hex(random_bytes(24));

        DB::table('crm_musteri_teklifleri')->insert([
            'customer_id'   => $customer->id,
            'tip'           => $tip,
            'kaynak_id'     => $validated['kaynak_id'] ?? null,
            'urun_tipi'     => $validated['urun_tipi'] ?? ($tip === 'bayi' ? 'bayi_paket' : 'ozel'),
            'paket_adi'     => $validated['paket_adi'],
            'tutar'         => $validated['tutar'],
            'odeme_yontemi' => $validated['odeme_yontemi'],
            'token'         => $token,
            'durum'         => 'gonderildi',
            'mesaj'         => $validated['mesaj'] ?? null,
            'sent_at'       => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $paymentUrl = url('/teklif/' . $token);

        try {
            $subject = ($tip === 'bayi' ? 'Bayilik Teklifimiz: ' : 'Size Özel Teklifimiz: ') . $validated['paket_adi'];
            $html = $this->buildOfferMail($customer, $validated, $paymentUrl, $tip);
            EmailNotificationService::send($customer->email, $subject, $html, true);
            return back()->with('success', '✅ Teklif oluşturuldu ve e-posta gönderildi: ' . $customer->email . ' (Tutar: ₺' . number_format($validated['tutar'], 2, ',', '.') . ')');
        } catch (\Throwable $e) {
            \Log::warning('Teklif mail gönderilemedi', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
            return back()->with('warning', '⚠️ Teklif kaydedildi ancak e-posta gönderilemedi. Mail ayarlarınızı kontrol edin. Müşteri linki: ' . $paymentUrl);
        }
    }

    public function bayiTeklifOnayla(Request $request, int $id, int $talepId)
    {
        $customer = Customer::findOrFail($id);
        $talep = DB::table('crm_musteri_teklifleri')->where('id', $talepId)->where('customer_id', $id)->first();
        abort_if(!$talep, 404);

        DB::table('crm_musteri_teklifleri')->where('id', $talepId)->update([
            'durum'       => 'onaylandi',
            'paid_at'     => $talep->paid_at ?? now(),
            'approved_at' => now(),
            'approved_by' => session('admin_id'),
            'updated_at'  => now(),
        ]);

        if ($talep->tip === 'bayi') {
            $customer->update([
                'bayi_mi'     => true,
                'bayi_tarihi' => $customer->bayi_tarihi ?: now()->toDateString(),
            ]);
            $msg = 'Ödeme onaylandı ve müşteri bayi olarak işaretlendi.';
        } else {
            $msg = 'Ödeme onaylandı.';
        }

        return back()->with('success', $msg);
    }

    /**
     * CRM müşteri teklifini sil (crm_musteri_teklifleri).
     * DELETE crm/musteriler/{id}/teklif/{talepId}/sil
     */
    public function ozelTeklifSil(Request $request, int $id, int $talepId)
    {
        $talep = DB::table('crm_musteri_teklifleri')
            ->where('id', $talepId)
            ->where('customer_id', $id)
            ->first();

        if (!$talep) {
            return back()->with('error', 'Teklif bulunamadı veya bu müşteriye ait değil.');
        }

        DB::table('crm_musteri_teklifleri')->where('id', $talepId)->delete();

        return back()->with('success', 'Teklif silindi.');
    }

    private function buildOfferMail(Customer $customer, array $data, string $url, string $tip = 'ozel'): string
    {
        $ayarlar  = DB::table('ayarlar')->first();
        $firma    = e($ayarlar->firma_adi ?? 'DN İş Ortağım');
        $siteUrl  = e($ayarlar->site_url ?? 'https://crm.ornek.com/');
        $logoUrl  = mail_logo_url();
        $musteri  = e($customer->adi);
        $paket    = e($data['paket_adi']);
        $tutar    = number_format($data['tutar'], 2, ',', '.');
        $yontem   = $data['odeme_yontemi'] === 'online' ? 'Online Ödeme (Kredi Kartı)' : 'Havale / EFT';
        $yil      = date('Y');
        $mesaj    = !empty($data['mesaj']) ? '<div style="background-color:#f7f8f3;padding:14px 18px;border-radius:8px;margin:16px 0;border-left:4px solid #b8b62e;font-size:14px;color:#3a4133;line-height:1.6"><strong style="color:#6f7320">Mesajımız:</strong><br>' . nl2br(e($data['mesaj'])) . '</div>' : '';

        if ($tip === 'bayi') {
            $icon = '⭐';
            $title = 'Bayilik Teklifimiz';
            $intro = "{$firma} olarak sizi bayilik programımıza davet ediyoruz.";
        } else {
            $icon = '🎁';
            $title = 'Size Özel Teklifimiz';
            $intro = "{$firma} olarak sizin için özel bir teklif hazırladık.";
        }

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:32px 36px 20px;text-align:center;border-bottom:1px solid #f0f1ec">
      <img src="' . $logoUrl . '" alt="' . $firma . '" width="148" style="display:block;margin:0 auto 16px;max-width:148px;height:auto;border:0">
      <div style="font-size:40px;line-height:1">' . $icon . '</div>
      <h2 style="margin:8px 0 0;font-size:21px;font-weight:800;color:#1f2419">' . $title . '</h2>
    </td></tr>
    <tr><td style="padding:28px 36px;font-size:15px;line-height:1.7;color:#3a4133">
      <p style="margin:0 0 12px">Merhaba <strong style="color:#6f7320">' . $musteri . '</strong>,</p>
      <p style="margin:0 0 20px">' . $intro . '</p>
      <div style="background-color:#f7f8f3;padding:22px;border-radius:14px;margin:0 0 20px;border:1px solid #eceee6;text-align:center">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#9aa08e;font-weight:600">Seçilen Paket</div>
        <div style="font-size:20px;font-weight:700;color:#1f2419;margin:8px 0">' . $paket . '</div>
        <div style="font-size:32px;font-weight:800;color:#6f7320">' . $tutar . ' ₺</div>
        <div style="font-size:12px;color:#7a8270;margin-top:8px">Ödeme Yöntemi: ' . $yontem . '</div>
      </div>
      ' . $mesaj . '
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0"><tr><td align="center">
        <a href="' . $url . '" style="display:inline-block;background-color:#b8b62e;color:#1f2419;text-decoration:none;padding:15px 42px;border-radius:10px;font-weight:700;font-size:16px;box-shadow:0 6px 16px rgba(184,182,46,0.32)">ÖDEME YAP →</a>
      </td></tr></table>
      <p style="color:#9aa08e;font-size:12px;text-align:center;margin:0">Buton çalışmazsa bu linki kopyalayıp tarayıcınıza yapıştırın:<br><span style="color:#8a8a1f">' . $url . '</span></p>
      <p style="margin:24px 0 0">Saygılarımızla,<br><strong style="color:#6f7320">' . $firma . '</strong></p>
    </td></tr>
    <tr><td style="padding:22px 36px;background-color:#f7f8f3;border-top:1px solid #eceee6;text-align:center">
      <p style="margin:0;font-size:11px;color:#b3b8a8">© ' . $yil . ' ' . $firma . ' &middot; <a href="' . $siteUrl . '" style="color:#8a8a1f;text-decoration:none">' . $siteUrl . '</a></p>
    </td></tr>
  </table>
</td></tr>
</table></body></html>';
    }

    public function sendMail(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'subject' => 'required|string|max:200',
            'body'    => 'required|string',
        ]);

        if (empty($customer->email)) {
            return back()->with('error', 'Müşterinin e-posta adresi yok.');
        }

        try {
            $subject = $this->replacePlaceholders($request->subject, $customer);
            $body    = $this->replacePlaceholders($request->body, $customer);
            $html    = $this->wrapMailHtml($body, $customer);
            EmailNotificationService::send($customer->email, $subject, $html, true);
            return back()->with('success', 'E-posta gönderildi: ' . $customer->email);
        } catch (\Throwable $e) {
            return back()->with('error', 'E-posta gönderilemedi: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Tüm tablarda görünen tek buton: müşteriye SMS veya E-POSTA gönderir.
     * Yüklenen bir rapor/teklif/belge de "ek_link" ile mesaja iliştirilebilir.
     */
    public function hizliGonder(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $data = $request->validate([
            'kanal'     => 'required|in:mail,sms',
            'mesaj'     => 'required|string|max:2000',
            'konu'      => 'nullable|string|max:200',
            'ek_link'   => 'nullable|string|max:600',
            'ek_baslik' => 'nullable|string|max:200',
        ]);

        // Ek belge linki yalnızca http(s) şemasıyla kabul edilir (mail HTML'ine güvenli iliştirme)
        if (!empty($data['ek_link']) && !preg_match('#^https?://#i', $data['ek_link'])) {
            $data['ek_link'] = null;
        }

        $mesaj = $this->replacePlaceholders($data['mesaj'], $customer);

        // ── E-POSTA ──────────────────────────────────────────────
        if ($data['kanal'] === 'mail') {
            if (empty($customer->email)) {
                return back()->with('error', 'Müşterinin e-posta adresi yok, mail gönderilemez.');
            }
            $konu  = $this->replacePlaceholders(($data['konu'] ?: 'Bilgilendirme'), $customer);
            $govde = nl2br(e($mesaj));
            if (!empty($data['ek_link'])) {
                $label = e($data['ek_baslik'] ?: 'Belgeyi görüntüle');
                $govde .= '<p style="margin-top:18px"><a href="' . e($data['ek_link'])
                        . '" style="display:inline-block;background:#b8b62e;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600">'
                        . $label . '</a></p>';
            }
            try {
                EmailNotificationService::send($customer->email, $konu, $this->wrapMailHtml($govde, $customer), true);
                return back()->with('success', 'E-posta gönderildi: ' . $customer->email);
            } catch (\Throwable $e) {
                return back()->with('error', 'E-posta gönderilemedi: ' . $e->getMessage())->withInput();
            }
        }

        // ── SMS ──────────────────────────────────────────────────
        if (empty($customer->telefon)) {
            return back()->with('error', 'Müşterinin telefon numarası yok, SMS gönderilemez.');
        }
        $sms = $mesaj;
        if (!empty($data['ek_link'])) {
            $sms .= ' ' . $data['ek_link'];
        }
        try {
            $res = (new \App\Services\SmsService())->send($customer->telefon, $sms);
            if (!empty($res['success'])) {
                return back()->with('success', 'SMS gönderildi: ' . $customer->telefon);
            }
            return back()->with('error', 'SMS gönderilemedi: ' . ($res['message'] ?? 'Bilinmeyen NetGSM hatası'))->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'SMS gönderilemedi: ' . $e->getMessage())->withInput();
        }
    }

    public function sendBulkMail(Request $request)
    {
        $validated = $request->validate([
            'ids'     => 'required|array|min:1',
            'ids.*'   => 'integer|exists:crm_customers,id',
            'subject' => 'required|string|max:200',
            'body'    => 'required|string',
        ]);

        $customers = Customer::whereIn('id', $validated['ids'])->get();
        $sent = 0; $skip = 0; $fail = 0; $lastError = null;

        foreach ($customers as $c) {
            if (empty($c->email)) { $skip++; continue; }
            try {
                $subject = $this->replacePlaceholders($validated['subject'], $c);
                $body    = $this->replacePlaceholders($validated['body'], $c);
                $html    = $this->wrapMailHtml($body, $c);
                EmailNotificationService::send($c->email, $subject, $html, true);
                $sent++;
            } catch (\Throwable $e) {
                $fail++;
                $lastError = $e->getMessage();
            }
        }

        $msg = "Gönderim tamamlandı. Başarılı: {$sent}, atlandı: {$skip}, başarısız: {$fail}";
        if ($fail > 0 && $lastError) $msg .= " — Son hata: {$lastError}";

        return back()->with($fail > 0 && $sent === 0 ? 'error' : 'success', $msg);
    }

    private function replacePlaceholders(string $text, Customer $customer): string
    {
        $ayarlar = DB::table('ayarlar')->first();
        $map = [
            '{{musteri_adi}}'    => $customer->adi ?? '',
            '{{musteri_unvan}}'  => $customer->unvan ?? '',
            '{{musteri_email}}'  => $customer->email ?? '',
            '{{musteri_telefon}}'=> $customer->telefon ?? '',
            '{{firma_adi}}'      => $ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım',
            '{{firma_email}}'    => $ayarlar->firma_email ?? '',
            '{{firma_telefon}}'  => $ayarlar->firma_telefon ?? '',
            '{{site_url}}'       => $ayarlar->site_url ?? url('/'),
            '{{tarih}}'          => date('d.m.Y'),
            // Kısa alias'lar (hızlı yazım / SMS için)
            '{ad}'               => $customer->adi ?? '',
            '{email}'            => $customer->email ?? '',
            '{telefon}'          => $customer->telefon ?? '',
            '{firma}'            => $ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım',
        ];
        return str_replace(array_keys($map), array_values($map), $text);
    }
    
    /**
     * CRM Müşteri Detay → Destek tab → Talep tamamen sil
     * (ana talep + tüm cevaplar + ekli dosyalar)
     */
    public function destekTalepSil($id, $talepId)
    {
        $talep = \Illuminate\Support\Facades\DB::table('destek')
            ->where('id', $talepId)
            ->whereIn('ustid', [0, null])  // sadece ana talep
            ->first();
 
        if (!$talep) {
            return redirect()
                ->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'destek'])
                ->with('error', 'Talep bulunamadı.');
        }
 
        // Tüm cevapların dosyalarını sil
        $cevaplar = \Illuminate\Support\Facades\DB::table('destek')
            ->where('ustid', $talepId)
            ->get();
 
        foreach ($cevaplar as $c) {
            if (!empty($c->dosya)) {
                $abs = public_path($c->dosya);
                if (file_exists($abs)) @unlink($abs);
            }
        }
 
        // Ana talep dosyası da varsa sil
        if (!empty($talep->dosya)) {
            $abs = public_path($talep->dosya);
            if (file_exists($abs)) @unlink($abs);
        }
 
        // DB'den sil: önce cevaplar, sonra ana talep
        \Illuminate\Support\Facades\DB::table('destek')->where('ustid', $talepId)->delete();
        \Illuminate\Support\Facades\DB::table('destek')->where('id', $talepId)->delete();
 
        return redirect()
            ->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'destek'])
            ->with('success', 'Destek talebi silindi.');
    }
 
 
    /**
     * CRM Müşteri Detay → Destek tab → Tek mesaj sil (talep değil)
     */
    public function destekMesajSil($id, $talepId, $mesajId)
    {
        // Güvenlik: bu mesaj gerçekten bu talebe mi ait?
        $mesaj = \Illuminate\Support\Facades\DB::table('destek')
            ->where('id', $mesajId)
            ->where(function ($q) use ($talepId) {
                // Ya bizzat ana talep, ya da onun cevaplarından biri
                $q->where('id', $talepId)
                  ->orWhere('ustid', $talepId);
            })
            ->first();
 
        if (!$mesaj) {
            return redirect()
                ->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'destek', 'talep_id' => $talepId])
                ->with('error', 'Mesaj bulunamadı.');
        }
 
        // ANA TALEBİ silmek istiyorsa → tüm cevaplar da gitsin (talep silmeye eşit)
        if ((int) $mesaj->id === (int) $talepId) {
            return $this->destekTalepSil($id, $talepId);
        }
 
        // Cevap dosyası varsa onu da sil
        if (!empty($mesaj->dosya)) {
            $abs = public_path($mesaj->dosya);
            if (file_exists($abs)) @unlink($abs);
        }
 
        \Illuminate\Support\Facades\DB::table('destek')->where('id', $mesajId)->delete();
 
        return redirect()
            ->route('admin.crm.musteriler.show', ['id' => $id, 'tab' => 'destek', 'talep_id' => $talepId])
            ->with('success', 'Mesaj silindi.');
    }

    private function wrapMailHtml(string $body, Customer $customer): string
    {
        $ayarlar  = DB::table('ayarlar')->first();
        $firmaAdi = e($ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım');
        $siteUrl  = e($ayarlar->site_url ?? 'https://crm.ornek.com/');
        $logoUrl  = mail_logo_url();
        $icerik   = nl2br(e($body));
        $yil      = date('Y');

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:32px 36px 24px;text-align:center;border-bottom:1px solid #f0f1ec">
      <img src="' . $logoUrl . '" alt="' . $firmaAdi . '" width="148" style="display:block;margin:0 auto;max-width:148px;height:auto;border:0">
    </td></tr>
    <tr><td style="padding:30px 36px;font-size:15px;line-height:1.7;color:#3a4133">' . $icerik . '</td></tr>
    <tr><td style="padding:22px 36px;background-color:#f7f8f3;border-top:1px solid #eceee6;text-align:center">
      <p style="margin:0 0 8px;font-size:13px;color:#5a6150;font-weight:600">' . $firmaAdi . '</p>
      <p style="margin:0;font-size:11px;color:#b3b8a8">© ' . $yil . ' ' . $firmaAdi . ' &middot; <a href="' . $siteUrl . '" style="color:#8a8a1f;text-decoration:none">' . $siteUrl . '</a></p>
    </td></tr>
  </table>
</td></tr>
</table></body></html>';
    }

    private function mergeTags(Request $request): \Illuminate\Support\Collection
    {
        $explicitTags = collect($request->input('etiketler', []));
        $textTags = collect(preg_split('/[,;]+/', (string) $request->input('etiketler_text')))
            ->map(fn ($tag) => trim($tag))
            ->filter();

        return $explicitTags->merge($textTags)
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values();
    }

    // ── Export ──────────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $customers = Customer::query()
            ->when($request->durum, fn ($q) => $q->where('durum', $request->durum))
            ->orderBy('id', 'desc')
            ->get();

        $headers = [
            ['Ad / Unvan', 22], ['Firma / Unvan', 22], ['Firma Tipi', 14],
            ['Email', 28], ['Telefon', 16], ['GSM', 16],
            ['Web Sitesi', 24], ['Sektör', 16], ['Kaynak', 16],
            ['Durum', 12], ['İl', 14], ['İlçe', 14],
            ['Adres', 32], ['Bakiye (₺)', 14],
            ['Vergi No', 14], ['Vergi Dairesi', 18], ['TC Kimlik', 14],
            ['Doğum Tarihi', 14], ['Etiketler', 24],
            ['Kayıt Tarihi', 16],
        ];

        $rows = $customers->map(function ($c) {
            return [
                $c->adi ?? '',
                $c->unvan ?? '',
                $c->firma_tipi ?? '',
                $c->email ?? '',
                $c->telefon ?? '',
                $c->gsm ?? '',
                $c->web_sitesi ?? '',
                $c->sektor ?? '',
                $c->kaynak ?? '',
                $c->durum ?? '',
                $c->il ?? '',
                $c->ilce ?? '',
                $c->adres ?? '',
                (float) ($c->bakiye ?? 0),
                $c->vergi_no ?? '',
                $c->vergi_dairesi ?? '',
                $c->tc_kimlik ?? '',
                $c->dogum_tarihi ?? '',
                is_array($c->etiketler) ? implode(', ', $c->etiketler) : ($c->etiketler ?? ''),
                $c->created_at ? $c->created_at->format('d.m.Y H:i') : '',
            ];
        })->all();

        return $this->buildStyledXlsx('CRM Müşteriler', $headers, $rows, ['N' => '#,##0.00 "₺"'], 'crm_musteriler');
    }

    public function exportTemplate()
    {
        $filename = 'crm_musteriler_sablon.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $handle = fopen('php://output', 'w');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, [
            'Ad / Unvan', 'Firma / Unvan', 'Firma Tipi', 'Email', 'Telefon', 'GSM',
            'Web Sitesi', 'Sektör', 'Kaynak', 'Durum', 'İl', 'İlçe', 'Adres',
            'Vergi No', 'Vergi Dairesi', 'TC Kimlik', 'Doğum Tarihi', 'Etiketler',
        ], ';');
        fputcsv($handle, [
            'Ahmet Yılmaz', 'Yılmaz Ltd.', 'Şirket', 'ahmet@example.com', '05551234567', '',
            '', 'Teknoloji', 'Web Sitesi', 'aktif', 'İstanbul', 'Kadıköy', 'Örnek Mahalle No:1',
            '1234567890', 'Kadıköy VD', '', '1990-01-01', 'VIP, Aktif',
        ], ';');
        fclose($handle);
        exit;
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);

        $file   = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // UTF-8 BOM temizle
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        fgetcsv($handle, 0, ';') ?: fgetcsv($handle); // başlık satırını atla

        $imported = 0;
        $errors   = [];

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if (empty($data[0])) {
                continue;
            }
            try {
                $etiketRaw = trim($data[17] ?? '');
                $etiketler = $etiketRaw
                    ? array_values(array_filter(array_map('trim', explode(',', $etiketRaw))))
                    : null;

                Customer::create([
                    'adi'            => $data[0] ?? '',
                    'unvan'          => $data[1] ?? null,
                    'firma_tipi'     => $data[2] ?? null,
                    'email'          => $data[3] ?? null,
                    'telefon'        => $data[4] ?? null,
                    'gsm'            => $data[5] ?? null,
                    'web_sitesi'     => $data[6] ?? null,
                    'sektor'         => $data[7] ?? null,
                    'kaynak'         => $data[8] ?? null,
                    'durum'          => in_array($data[9] ?? '', ['aktif', 'pasif', 'potansiyel']) ? $data[9] : 'aktif',
                    'il'             => $data[10] ?? null,
                    'ilce'           => $data[11] ?? null,
                    'adres'          => $data[12] ?? null,
                    'vergi_no'       => $data[13] ?? null,
                    'vergi_dairesi'  => $data[14] ?? null,
                    'tc_kimlik'      => $data[15] ?? null,
                    'dogum_tarihi'   => !empty($data[16]) ? $data[16] : null,
                    'etiketler'      => $etiketler,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = ($data[0] ?? '?') . ': ' . $e->getMessage();
            }
        }
        fclose($handle);

        $msg = $imported . ' müşteri içe aktarıldı.';
        if ($errors) {
            $msg .= ' Hata: ' . count($errors);
        }

        return redirect()->route('admin.crm.musteriler.index')->with('success', $msg);
    }

    private function buildStyledXlsx(string $title, array $headers, array $rows, array $numberFormats, string $slug)
    {
        $sp    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $sp->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // Banner satırı
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->setCellValue('A1', 'İŞ ORTAĞIM — ' . mb_strtoupper($title) . ' (' . date('d.m.Y H:i') . ')');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FACC15']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Header satırı
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '2', is_array($h) ? $h[0] : $h);
            if (is_array($h)) {
                $sheet->getColumnDimension($col)->setWidth($h[1]);
            }
        }
        $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '92400E']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center', 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'DDDDDD']]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // Veri
        $row = 3;
        foreach ($rows as $r) {
            $sheet->fromArray($r, null, 'A' . $row);
            if ($row % 2 === 1) {
                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FAFAFA');
            }
            $row++;
        }

        if ($row > 3) {
            $sheet->getStyle('A3:' . $lastCol . ($row - 1))->applyFromArray([
                'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'EEEEEE']]],
                'alignment' => ['vertical' => 'center'],
                'font'      => ['size' => 10],
            ]);
        }

        foreach ($numberFormats as $col => $fmt) {
            $sheet->getStyle($col . '3:' . $col . max($row - 1, 3))->getNumberFormat()->setFormatCode($fmt);
        }

        $sheet->freezePane('A3');
        if ($row > 3) {
            $sheet->setAutoFilter('A2:' . $lastCol . ($row - 1));
        }

        $filename = $slug . '_' . date('Y-m-d_His') . '.xlsx';
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sp);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Tek Kullanımlık / Geçici Şifre Gönder.
     * Müşterinin uyeler tablosundaki giriş hesabını bulur, yeni bir geçici şifre
     * üretir, hash'leyerek kaydeder ve e-posta ile müşteriye iletir.
     * Giriş hesabı yoksa uyarır (önce "Müşteri Yap" ile hesap açılmalı).
     */
    public function geciciSifreGonder($id)
    {
        $customer = DB::table('crm_customers')->where('id', $id)->first();
        if (!$customer) {
            return back()->with('error', 'Müşteri bulunamadı.');
        }

        $email = trim((string) ($customer->email ?? ''));
        if ($email === '') {
            return back()->with('error', 'Bu müşterinin e-posta adresi yok. Önce "Düzenle" ile e-posta ekleyin.');
        }

        // Giriş hesabı (uyeler) — önce uye_id, yoksa e-posta ile bul
        $uye = null;
        if (Schema::hasTable('uyeler')) {
            if (!empty($customer->uye_id)) {
                $uye = DB::table('uyeler')->where('id', $customer->uye_id)->first();
            }
            if (!$uye) {
                $uye = DB::table('uyeler')->where('email', $email)->first();
            }
        }

        if (!$uye) {
            return back()->with('error', 'Bu müşterinin giriş hesabı yok. Önce "Müşteri Yap" ile hesap oluşturun, sonra şifre gönderin.');
        }

        // Yeni geçici şifre üret + kaydet
        $duzSifre = \Illuminate\Support\Str::random(10);
        try {
            DB::table('uyeler')->where('id', $uye->id)->update([
                'sifre' => Hash::make($duzSifre),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Geçici şifre güncellenemedi', ['err' => $e->getMessage(), 'uye_id' => $uye->id]);
            return back()->with('error', 'Şifre güncellenemedi. Lütfen tekrar deneyin.');
        }

        // Müşteriye e-posta ile ilet
        $mailGitti = false;
        try {
            $ad = trim((string) ($uye->ad ?? '') . ' ' . (string) ($uye->soyad ?? '')) ?: ($customer->adi ?? '');
            $html = "<p>Merhaba <strong>" . e($ad) . "</strong>,</p>"
                . "<p>İş Ortağım hesabınız için yeni bir geçici şifre oluşturuldu:</p>"
                . "<div style='background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin:14px 0'>"
                . "<div>E-posta: <strong>" . e($email) . "</strong></div>"
                . "<div>Geçici şifre: <strong style='font-family:monospace;font-size:16px'>" . e($duzSifre) . "</strong></div>"
                . "</div>"
                . "<p>Giriş yaptıktan sonra şifrenizi değiştirmenizi öneririz.</p>";
            if (class_exists(\App\Services\EmailNotificationService::class)) {
                \App\Services\EmailNotificationService::send($email, 'İş Ortağım — Geçici Şifreniz', $html);
                $mailGitti = true;
            }
        } catch (\Throwable $e) {
            \Log::warning('Geçici şifre maili gönderilemedi', ['err' => $e->getMessage(), 'email' => $email]);
        }

        $mesaj = $mailGitti
            ? 'Geçici şifre oluşturuldu ve müşteriye e-posta ile gönderildi.'
            : 'Geçici şifre oluşturuldu (e-posta gönderilemedi) — aşağıdaki kutudan iletebilirsiniz.';

        /*
         * ŞİFREYİ AYRI GÖSTER (04.08.2026)
         *
         * Eskiden şifre genel "success" bildirimine gömülüyordu; bildirim 5 saniyede
         * kayboluyordu ve şifre bir bcrypt hash'e dönüştüğü için bir daha HİÇBİR YERDEN
         * geri okunamıyordu — kaçıran, tekrar üretmek zorunda kalıyordu.
         *
         * Artık ayrı bir flash veriyle taşınıyor; show.blade.php bunu görünce elle
         * kapatılana kadar açık kalan bir kutuda gösteriyor (kopyala butonuyla).
         */
        session()->flash('gecici_sifre_goster', [
            'ad'     => trim((string) ($uye->ad ?? '') . ' ' . (string) ($uye->soyad ?? '')) ?: ($customer->adi ?? ''),
            'email'  => $email,
            'sifre'  => $duzSifre,
            'mail'   => $mailGitti,
        ]);

        return back()->with('success', $mesaj);
    }
    /**
     * "Bu müşteriyi kim getirdi" bağını kurar.
     * Bağ kurulunca, bu müşterinin YAPTIĞI HER ALIMIN %10'u öneren kişiye
     * DN Coin olarak yatar (eski sistem yalnızca ilk faturaya tek sefer veriyordu).
     */
    public function referansBagla(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $data = $request->validate([
            'oneren_musteri_id' => 'required|integer|exists:crm_customers,id',
        ], [], ['oneren_musteri_id' => 'öneren müşteri']);

        $onerilenUyeId = $this->uyeIdBul($customer);
        if (!$onerilenUyeId) {
            return back()->with('error', 'Bu müşterinin üye kaydı yok; referans bağı kurulamaz.');
        }

        $oneren = Customer::find($data['oneren_musteri_id']);
        $onerenUyeId = $oneren ? $this->uyeIdBul($oneren) : null;
        if (!$onerenUyeId) {
            return back()->with('error', 'Seçilen müşterinin üye kaydı yok; kazanç yatırılamaz.');
        }

        [$ok, $mesaj] = \App\Services\MusteriReferansi::bagla(
            $onerenUyeId, $onerilenUyeId, session('admin_id')
        );

        return back()->with($ok ? 'success' : 'error',
            $ok ? (($oneren->adi ?? 'Öneren') . ' bağlandı. ' . $mesaj) : $mesaj);
    }

    /** Referans bağını iptal eder (geçmiş kazançlar defterde kalır). */
    public function referansBagiKaldir(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);
        $uyeId = $this->uyeIdBul($customer);

        if (!$uyeId) {
            return back()->with('error', 'Üye kaydı bulunamadı.');
        }

        \App\Services\MusteriReferansi::bagiKaldir($uyeId);

        return back()->with('success', 'Referans bağı kaldırıldı. Bundan sonraki alımlardan kazanç yatmayacak.');
    }

}