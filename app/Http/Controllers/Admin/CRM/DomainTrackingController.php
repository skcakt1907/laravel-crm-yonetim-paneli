<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DomainTrackingController extends Controller
{
    /**
     * GÖREV #214/3 — Domain/hosting kazancı AYLIK bazda.
     *
     * Kaynak: faturalar (ödenmiş) — başlığı/hizmeti domain veya hosting geçenler.
     * Son 12 ay ve içinde bulunduğumuz ay ayrı ayrı döner.
     *
     * @return array{bu_ay:float, gecen_ay:float, yil:float, aylar:array}
     */
    private function aylikKazanc(): array
    {
        $bos = ['bu_ay' => 0.0, 'gecen_ay' => 0.0, 'yil' => 0.0, 'aylar' => []];

        try {
            if (!Schema::hasTable('faturalar')) return $bos;

            $filtre = function ($q) {
                $q->where('faturalar.durum', 1)   // ödenmiş
                  ->where(function ($w) {
                      foreach (['domain', 'hosting', 'alan ad'] as $kelime) {
                          $w->orWhere('faturalar.baslik', 'like', "%{$kelime}%");
                      }
                  });
            };

            $ay = now()->startOfMonth();

            $buAy = (float) DB::table('faturalar')->where($filtre)
                ->whereRaw('DATE(odenen_tarih) BETWEEN ? AND ?', [$ay->toDateString(), now()->endOfMonth()->toDateString()])->sum('tutar');

            $gecen = (float) DB::table('faturalar')->where($filtre)
                ->whereRaw('DATE(odenen_tarih) BETWEEN ? AND ?', [
                    $ay->copy()->subMonth()->toDateString(),
                    $ay->copy()->subMonth()->endOfMonth()->toDateString(),
                ])->sum('tutar');

            $yil = (float) DB::table('faturalar')->where($filtre)
                ->whereRaw('DATE(odenen_tarih) BETWEEN ? AND ?', [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()])->sum('tutar');

            // Son 6 ayın dökümü (grafik/çubuk için)
            $aylar = [];
            for ($i = 5; $i >= 0; $i--) {
                $bas = $ay->copy()->subMonths($i);
                $aylar[] = [
                    'etiket' => $bas->translatedFormat('M Y'),
                    'tutar'  => (float) DB::table('faturalar')->where($filtre)
                        ->whereRaw('DATE(odenen_tarih) BETWEEN ? AND ?', [$bas->toDateString(), $bas->copy()->endOfMonth()->toDateString()])->sum('tutar'),
                ];
            }

            return ['bu_ay' => $buAy, 'gecen_ay' => $gecen, 'yil' => $yil, 'aylar' => $aylar];
        } catch (\Throwable $e) {
            return $bos;
        }
    }

    public function index(Request $request)
    {
        $search  = trim((string) $request->get('search'));
        $status  = $request->get('status');
        $kaynak  = (string) $request->get('kaynak'); // '' | 'order' | 'manuel' | 'hosting' | 'metunic'

        /*
         * KOMPOZİT SÜZGEÇLER — kaydın kaynağına değil, domainin İKİ
         * TABLODAKİ DURUMUNA bakarlar:
         *   ikisinde   : hem bizde hem Metunic'te var   (canlıda 144)
         *   metunicsiz : bizde var, Metunic'te yok      (canlıda  71)
         * ('metunic' zaten "Metunic'te var, bizde yok" demek — 35)
         *
         * Bunlar sorgu seviyesinde süzülemez: karar iki tablonun
         * kesişimine bakılarak veriler birleştikten SONRA veriliyor.
         * Bu yüzden sorgulara boş kaynak gidiyor, süzme sonradan.
         */
        $kompozit = in_array($kaynak, ['ikisinde', 'metunicsiz'], true) ? $kaynak : null;
        if ($kompozit) {
            $kaynak = '';
        }
        // Dışa aktarmada sayfalama olmamalı — tüm liste tek seferde lazım (bkz. disaAktar())
        $perPage = $request->boolean('tumu') ? 1000000 : 20;

        $all = collect();

        // 1) domain_orders (ResellerClub / online satın alınan domainler)
        if (Schema::hasTable('domain_orders') && ($kaynak === '' || $kaynak === 'order')) {
            $q = DB::table('domain_orders as d')
                ->leftJoin('uyeler as u', 'u.id', '=', 'd.user_id')
                ->select(
                    'd.id as id',
                    'd.domain as domain',
                    'd.status as status',
                    'd.price as fiyat',
                    'd.years as yil',
                    'd.registered_at as baslangic',
                    'd.expires_at as bitis',
                    'd.created_at as created_at',
                    'd.user_id as uye_id',
                    'u.email as uye_email',
                    'u.ad as uye_ad',
                    'u.soyad as uye_soyad',
                    'u.firmaadi as uye_firma',
                    DB::raw("'order' as kaynak")
                );

            if ($search !== '') {
                $q->where(function ($w) use ($search) {
                    $w->where('d.domain', 'like', "%{$search}%")
                      ->orWhere('d.reseller_order_id', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%")
                      ->orWhere('u.firmaadi', 'like', "%{$search}%");
                });
            }
            if ($status) {
                $q->where('d.status', $status);
            }

            $all = $all->concat($q->get());
        }

        // 2) satilanlar (tipi = 3 : manuel eklenen / eski domain satışları)
        if (Schema::hasTable('satilanlar') && ($kaynak === '' || in_array($kaynak, ['manuel', 'hosting', 'mail_hosting', 'business_hosting'], true))) {
            $cols = Schema::getColumnListing('satilanlar');
            $has = fn ($c) => in_array($c, $cols, true);

            $q = DB::table('satilanlar as s')
                ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                ->whereIn('s.tipi', ['1', '2', '3'])
                ->select(
                    's.id as id',
                    's.domain as domain',
                    DB::raw(($has('domain_durum') ? 's.domain_durum' : ($has('durum') ? 's.durum' : "''")) . ' as status'),
                    DB::raw('COALESCE(NULLIF(' . ($has('tutar') ? 's.tutar' : 'NULL') . ',0), NULLIF(' . ($has('fiyat') ? 's.fiyat' : 'NULL') . ',0), 0) as fiyat'),
                    DB::raw('1 as yil'),
                    DB::raw(($has('baslangic_tarih') ? 's.baslangic_tarih' : ($has('baslangic_tarihi') ? 's.baslangic_tarihi' : 'NULL')) . ' as baslangic'),
                    DB::raw(($has('bitis_tarih') ? 's.bitis_tarih' : ($has('bitis_tarihi') ? 's.bitis_tarihi' : 'NULL')) . ' as bitis'),
                    DB::raw(($has('created_at') ? 's.created_at' : ($has('tarih') ? 's.tarih' : 'NULL')) . ' as created_at'),
                    's.uyeid as uye_id',
                    'u.email as uye_email',
                    'u.ad as uye_ad',
                    'u.soyad as uye_soyad',
                    'u.firmaadi as uye_firma',
                    // Hosting türü tespiti için (business/mail hosting) — hosting_baslik + paket_adi
                    DB::raw(($has('hosting_baslik') ? 's.hosting_baslik' : "''") . ' as hosting_baslik'),
                    DB::raw(($has('paket_adi') ? 's.paket_adi' : "''") . ' as paket_adi'),
                    // tipi=3 domain (manuel), tipi=1/2 hosting
                    DB::raw(($has('hizmetler') ? 's.hizmetler' : "''") . ' as hizmetler'),
                    DB::raw("IF(s.tipi = '3', 'manuel', 'hosting') as kaynak")
                );

            if ($search !== '') {
                $q->where(function ($w) use ($search) {
                    $w->where('s.domain', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%")
                      ->orWhere('u.firmaadi', 'like', "%{$search}%");
                });
            }

            $all = $all->concat($q->get());
        }

        // 3) metunic_domains (Metunic'ten senkronlanan domainler)
        if (Schema::hasTable('metunic_domains') && ($kaynak === '' || $kaynak === 'metunic')) {
            $mCols = Schema::getColumnListing('metunic_domains');
            $mHas  = fn ($c) => in_array($c, $mCols, true);

            $q = DB::table('metunic_domains as m')
                ->leftJoin('uyeler as u', 'u.id', '=', 'm.uye_id')
                ->select(
                    'm.id as id',
                    'm.domain as domain',
                    'm.status as status',
                    /*
                     * Eskiden sabit 0 yazılıyordu. Metunic senkronu fiyat
                     * getirmiyor; bizde karşılığı olmayan kayıtlara panelden
                     * elle tutar girilebiliyor, varsa o okunur.
                     */
                    DB::raw(($mHas('tutar') ? 'COALESCE(m.tutar, 0)' : '0') . ' as fiyat'),
                    DB::raw(($mHas('tutar')        ? 'm.tutar'        : 'NULL') . ' as tutar'),
                    DB::raw(($mHas('maliyet')      ? 'm.maliyet'      : 'NULL') . ' as maliyet'),
                    DB::raw(($mHas('satis_tarihi') ? 'm.satis_tarihi' : 'NULL') . ' as satis_tarihi'),
                    DB::raw('1 as yil'),
                    'm.date_added as baslangic',
                    'm.date_renews as bitis',
                    'm.created_at as created_at',
                    'm.uye_id as uye_id',
                    'u.email as uye_email',
                    'u.ad as uye_ad',
                    'u.soyad as uye_soyad',
                    'u.firmaadi as uye_firma',
                    DB::raw("'metunic' as kaynak")
                );

            if ($search !== '') {
                $q->where(function ($w) use ($search) {
                    $w->where('m.domain', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%")
                      ->orWhere('u.firmaadi', 'like', "%{$search}%");
                });
            }
            if ($status) {
                $q->where('m.status', $status);
            }

            $all = $all->concat($q->get());
        }

        // Tarihe göre sırala
        $sorted = $all->sortByDesc(fn ($item) => $item->created_at ?? $item->baslangic ?? '')->values();

        /*
         * TÜR TESPİTİ — `hizmetler` alanından.
         *
         * DÜZELTME (04.08.2026): Eskiden manuel eklenen her kayda koşulsuz
         * tur='domain' deniyordu; hosting bilgisi `hizmetler` JSON alanında
         * durduğu hâlde hiç okunmuyordu — hosting sayaçları hep 0 çıkıyordu.
         * Kontrol edilen hosting_baslik / paket_adi alanları da hep boştu ve
         * aranan kelime 'business' idi; fiyat listesindeki etiket
         * "Bussines Hosting" (yazım hatalı) olduğu için zaten eşleşmezdi.
         *
         * Bir kayıt hem domain hem hosting içerebilir (örn. ["Bussines Hosting",
         * "Domain","SSL"]), bu yüzden bayraklar BAĞIMSIZ tutulur ve sayaçlar da
         * bağımsız sayar. Toplam, bayrakların toplamı DEĞİLDİR.
         */
        $sorted = $sorted->map(function ($d) {
            $kaynak = $d->kaynak ?? '';

            // hizmetler JSON dizi ya da düz metin olabilir
            $ham = $d->hizmetler ?? '';
            $liste = [];
            if (is_string($ham) && $ham !== '') {
                $coz = json_decode($ham, true);
                $liste = is_array($coz) ? $coz : [$ham];
            } elseif (is_array($ham)) {
                $liste = $ham;
            }
            $metin = mb_strtolower(implode(' ', array_map('strval', $liste)), 'UTF-8');

            // Fiyat listesindeki etiket "Bussines Hosting" yazılmış; ikisini de kabul et
            $d->hizMail     = str_contains($metin, 'mail hosting');
            $d->hizBusiness = str_contains($metin, 'bussines hosting') || str_contains($metin, 'business hosting');
            $d->hizHosting  = $d->hizMail || $d->hizBusiness || str_contains($metin, 'hosting');
            $d->hizDomain   = str_contains($metin, 'domain');

            // hizmetler boşsa eski davranış: kaynağa göre karar ver
            if ($metin === '') {
                if (in_array($kaynak, ['order', 'manuel', 'metunic'], true)) {
                    $d->hizDomain = true;
                } else {
                    $ad = mb_strtolower(trim(($d->hosting_baslik ?? '') . ' ' . ($d->paket_adi ?? '')), 'UTF-8');
                    $d->hizMail     = str_contains($ad, 'mail');
                    $d->hizBusiness = str_contains($ad, 'business') || str_contains($ad, 'bussines');
                    $d->hizHosting  = true;
                }
            }

            // Satırdaki rozet için tek bir tür (hosting varsa o öne çıkar)
            if ($d->hizMail)          $d->tur = 'mail_hosting';
            elseif ($d->hizBusiness)  $d->tur = 'business_hosting';
            elseif ($d->hizHosting)   $d->tur = 'hosting';
            else                      $d->tur = 'domain';

            return $d;
        });

        /*
         * ══ TEKRAR ELEME (17.09.2026) ══════════════════════════════════
         *
         * Aynı domain iki kez görünüyordu: bir kez bizim kaydımız
         * (satilanlar, tipi=3), bir kez Metunic senkronundan. Canlıda
         * 144 domain böyle.
         *
         * Tekrar eden Metunic kaydı listeden ELENİR, bizim kaydımız
         * kalır — müşteri bilgisi, tutar ve maliyet yalnızca bizim
         * kayıtta var, Metunic tarafı bunların hiçbirini taşımıyor.
         *
         * Bizde karşılığı OLMAYAN Metunic kayıtları (canlıda 35 adet)
         * listede kalır; kaynak süzgecinden ayrıca seçilebilir.
         *
         * Böylece toplam domain sayısı tekrarı bir kez sayar.
         *
         * Eşleşme DomainAnahtari ile: veride büyük/küçük harf, boşluk ve
         * Türkçe İ farkları var, ham karşılaştırma kopyaları kaçırıyor.
         */
        $anahtar = fn ($d) => \App\Support\DomainAnahtari::of($d->domain ?? null);

        [$metunicHepsi, $anaListe] = $sorted->partition(fn ($d) => ($d->kaynak ?? '') === 'metunic');

        /*
         * "Bizde var mı" sorusu, ekranda yüklü listeden DEĞİL doğrudan
         * tablodan sorulur. Kaynak filtresi (?kaynak=metunic) seçiliyken
         * ana liste boş gelir; listeye bakılsaydı hiçbir tekrar elenmez,
         * 144 kayıt Metunic tablosunda yeniden belirirdi.
         */
        $bizdekiler = collect();
        if (Schema::hasTable('satilanlar')) {
            $bizdekiler = DB::table('satilanlar')
                ->whereNotNull('domain')->where('domain', '<>', '')
                ->pluck('domain')
                ->map(fn ($d) => \App\Support\DomainAnahtari::of($d))
                ->flip();
        }
        $metunicOzel = $metunicHepsi->reject(fn ($d) => $bizdekiler->has($anahtar($d)))->values();

        /*
         * Tek liste: bizim kayıtlarımız + bizde olmayan Metunic kayıtları.
         *
         * SIRALAMA — bizimkiler önce, Metunic sonra.
         * Salt tarihe göre sıralanırsa Metunic kayıtları listeyi ele
         * geçiriyor: hepsi aynı senkron zaman damgasını taşıyor
         * (canlıda 10.09 07:38) ve bizim kayıtlarımızdan yeni, yani
         * ilk iki sayfa tamamen Metunic oluyor, müşterisi ve tutarı
         * olan kendi kayıtlarımız 3. sayfaya düşüyordu.
         *
         * Metunic'e özel kayıtlara kaynak süzgecinden tek tıkla
         * ulaşılıyor, listenin başını tutmalarına gerek yok.
         */
        $sorted = $anaListe->sortByDesc(fn ($d) => $d->created_at ?? $d->baslangic ?? '')
            ->concat($metunicOzel->sortByDesc(fn ($d) => $d->baslangic ?? $d->created_at ?? ''))
            ->values();

        // Metunic'te kayıtlı domainlerin anahtar kümesi — kompozit
        // süzgeçler ve sayaçlar bunun üzerinden karar veriyor.
        $metunicAnahtarlari = collect();
        if (Schema::hasTable('metunic_domains')) {
            $metunicAnahtarlari = DB::table('metunic_domains')
                ->whereNotNull('domain')->where('domain', '<>', '')
                ->pluck('domain')
                ->map(fn ($d) => \App\Support\DomainAnahtari::of($d))
                ->flip();
        }

        // Sayaçlar süzgeçten ÖNCEKİ tam listeden hesaplanır, yoksa
        // bir grubu seçince diğerlerinin sayısı sıfır görünürdü.
        $ikisindeAdet   = $sorted->filter(fn ($d) => ($d->kaynak ?? '') !== 'metunic'
                                && $metunicAnahtarlari->has($anahtar($d)))->count();
        $metunicsizAdet = $sorted->filter(fn ($d) => ($d->kaynak ?? '') !== 'metunic'
                                && ! $metunicAnahtarlari->has($anahtar($d)))->count();

        if ($kompozit === 'ikisinde') {
            $sorted = $sorted->filter(fn ($d) => ($d->kaynak ?? '') !== 'metunic'
                        && $metunicAnahtarlari->has($anahtar($d)))->values();
        } elseif ($kompozit === 'metunicsiz') {
            $sorted = $sorted->filter(fn ($d) => ($d->kaynak ?? '') !== 'metunic'
                        && ! $metunicAnahtarlari->has($anahtar($d)))->values();
        }

        $tumKayitlar = $sorted;

        // Kalan gün hesabı (30/7 gün eşikleri)
        /*
         * Kalan gün, GÜN BAŞINDAN ölçülür.
         * Eskiden time() (o anki saat) kullanılıyordu; "yarın 00:00" biten bir domain
         * öğleden sonra bakıldığında 0 gün, sabah bakıldığında 1 gün çıkıyor ve
         * kart sayıları gün içinde oynuyordu.
         */
        $bugun = Carbon::today()->getTimestamp();
        $kalanGun = function ($bitis) use ($bugun) {
            if (!$bitis) return null;
            try {
                $t = strtotime(substr((string) $bitis, 0, 10) . ' 00:00:00');
                return $t === false ? null : (int) round(($t - $bugun) / 86400);
            } catch (\Throwable $e) {
                return null;
            }
        };
        $altinda = function (int $esik) use ($tumKayitlar, $kalanGun) {
            return $tumKayitlar->filter(function ($d) use ($esik, $kalanGun) {
                $g = $kalanGun($d->bitis ?? null);
                return $g !== null && $g >= 0 && $g <= $esik;
            })->count();
        };
        // Süresi GEÇMİŞ olanlar — eskiden hiçbir kartta görünmüyordu
        $gecmisAdet = $tumKayitlar->filter(function ($d) use ($kalanGun) {
            $g = $kalanGun($d->bitis ?? null);
            return $g !== null && $g < 0;
        })->count();

        // Kart filtreleri: ?kalan=30|7|gecmis ve ?sgrup=aktif|bekleyen|hata
        $kalanSecim = (string) $request->get('kalan');
        $kalan = (int) $kalanSecim;
        $sgrup = $request->get('sgrup');
        $filtered = $sorted;
        if ($kalanSecim === 'gecmis') {
            $filtered = $filtered->filter(function ($d) use ($kalanGun) {
                $g = $kalanGun($d->bitis ?? null);
                return $g !== null && $g < 0;
            })->values();
        } elseif (in_array($kalan, [7, 30], true)) {
            $filtered = $filtered->filter(function ($d) use ($kalan, $kalanGun) {
                $g = $kalanGun($d->bitis ?? null);
                return $g !== null && $g >= 0 && $g <= $kalan;
            })->values();
        }
        if ($sgrup === 'aktif') {
            $filtered = $filtered->whereIn('status', ['active', 'kayitli', 1, '1'])->values();
        } elseif ($sgrup === 'bekleyen') {
            $filtered = $filtered->whereIn('status', ['pending', 'bekliyor', 'paid'])->values();
        } elseif ($sgrup === 'hata') {
            $filtered = $filtered->whereIn('status', ['failed', 'hata', 'expired'])->values();
        }

        // Tür filtresi: ?tur=toplam|domain|hosting|mail_hosting|business_hosting
        // NOT (görev #214/1): "toplam" artık SADECE Business Hosting + Mail Hosting demek.
        $tur = $request->get('tur');
        if (in_array($tur, ['toplam', 'domain', 'hosting', 'mail_hosting', 'business_hosting'], true)) {
            // Bayraklara göre süz — bir kayıt birden fazla türe girebilir
            $filtered = $filtered->filter(function ($d) use ($tur) {
                return match ($tur) {
                    'toplam'           => ($d->hizBusiness ?? false) || ($d->hizMail ?? false),
                    'hosting'          => (bool) ($d->hizHosting  ?? false),
                    'business_hosting' => (bool) ($d->hizBusiness ?? false),
                    'mail_hosting'     => (bool) ($d->hizMail     ?? false),
                    'domain'           => (bool) ($d->hizDomain   ?? false),
                    default            => true,
                };
            })->values();
        }

        // Manuel sayfalama (kart filtresi uygulanmış liste)
        $page    = LengthAwarePaginator::resolveCurrentPage('page');
        $total   = $filtered->count();
        $items   = $filtered->slice(($page - 1) * $perPage, $perPage)->values();
        $domains = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);

        $stats = [
            'toplam'    => $tumKayitlar->count(),
            'gecmis'    => $gecmisAdet,
            'gun30'     => $altinda(30),
            'gun7'      => $altinda(7),
            'musteri'   => $tumKayitlar->pluck('uye_id')->filter()->unique()->count(),
            'aktif'     => $tumKayitlar->whereIn('status', ['active', 'kayitli', 1, '1'])->count(),
            'bekleyen'  => $tumKayitlar->whereIn('status', ['pending', 'bekliyor', 'paid'])->count(),
            'hata'      => $tumKayitlar->whereIn('status', ['failed', 'hata', 'expired'])->count(),
            'manuel'    => $tumKayitlar->where('kaynak', 'manuel')->count(),
            'hosting'   => $tumKayitlar->where('kaynak', 'hosting')->count(),
            'order'     => $tumKayitlar->where('kaynak', 'order')->count(),
            'metunic'   => $tumKayitlar->where('kaynak', 'metunic')->count(),
            'ikisinde'   => $ikisindeAdet,
            'metunicsiz' => $metunicsizAdet,
            // Tür bazlı sayımlar — BAĞIMSIZ sayılır (bir kayıt hem domain hem hosting olabilir)
            'domain_adet'      => $tumKayitlar->filter(fn ($d) => $d->hizDomain   ?? false)->count(),
            'hosting_tum'      => $tumKayitlar->filter(fn ($d) => $d->hizHosting  ?? false)->count(),
            'mail_hosting'     => $tumKayitlar->filter(fn ($d) => $d->hizMail     ?? false)->count(),
            'business_hosting' => $tumKayitlar->filter(fn ($d) => $d->hizBusiness ?? false)->count(),
            // GÖREV #214/1: tür kutucuklarındaki "Toplam" = Business + Mail Hosting
            'toplam_bh_mh'     => $tumKayitlar->filter(fn ($d) => ($d->hizBusiness ?? false) || ($d->hizMail ?? false))->count(),
        ];

        // GÖREV #214/3: Domain toplam kazanç — AYLIK bazda
        $kazanc = $this->aylikKazanc();

        return view('admin.crm.domains.index', compact('domains', 'stats', 'search', 'status', 'kaynak', 'kompozit', 'tur', 'kazanc'));
    }

    /**
     * DOMAIN LİSTESİNİ CSV OLARAK İNDİR.
     *
     * Ekranda hangi filtre seçiliyse (arama, kaynak, tür, durum, kalan gün)
     * aynısı geçerlidir — listede ne görüyorsan o iner, sayfalama olmadan.
     *
     * Biçim: UTF-8 BOM + noktalı virgül ayraç → Excel Türkçe kurulumda
     * çift tıklayınca sütunlara doğru ayrılır.
     */
    public function disaAktar(Request $request)
    {
        // index() ile birebir aynı filtreler; 'tumu' sayfalamayı kapatır
        $request->merge(['tumu' => 1]);
        $veri = $this->index($request)->getData();

        $satirlar = collect($veri['domains']->items());

        $baslik = [
            'Domain', 'Tür', 'Kaynak', 'Müşteri', 'Firma', 'E-posta',
            'Başlangıç', 'Bitiş', 'Kalan Gün', 'Yıl', 'Tutar', 'Durum',
        ];

        $turAdi = [
            'domain' => 'Domain', 'hosting' => 'Hosting',
            'mail_hosting' => 'Mail Hosting', 'business_hosting' => 'Business Hosting',
        ];
        $kaynakAdi = [
            'order' => 'Online Sipariş', 'manuel' => 'Manuel', 'metunic' => 'Metunic', 'hosting' => 'Hosting',
        ];

        $dosyaAdi = 'domain-listesi-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($satirlar, $baslik, $turAdi, $kaynakAdi) {
            $cikti = fopen('php://output', 'w');
            fwrite($cikti, "\xEF\xBB\xBF");            // Excel'in UTF-8 anlaması için BOM
            fputcsv($cikti, $baslik, ';');

            foreach ($satirlar as $d) {
                $bitis = $d->bitis ?? null;
                $kalan = null;
                if ($bitis) {
                    try {
                        $kalan = (int) Carbon::today()->diffInDays(Carbon::parse($bitis)->startOfDay(), false);
                    } catch (\Throwable $e) {
                        $kalan = null;
                    }
                }

                $musteri = trim(($d->uye_ad ?? '') . ' ' . ($d->uye_soyad ?? ''));

                fputcsv($cikti, [
                    $d->domain ?? '',
                    $turAdi[$d->tur ?? ''] ?? ($d->tur ?? ''),
                    $kaynakAdi[$d->kaynak ?? ''] ?? ($d->kaynak ?? ''),
                    $musteri,
                    $d->uye_firma ?? '',
                    $d->uye_email ?? '',
                    $d->baslangic ? date('d.m.Y', strtotime($d->baslangic)) : '',
                    $bitis ? date('d.m.Y', strtotime($bitis)) : '',
                    $kalan ?? '',
                    $d->yil ?? '',
                    $d->fiyat ?? '',
                    $d->status ?? '',
                ], ';');
            }

            fclose($cikti);
        }, $dosyaAdi, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * GÖREV #214/4 — ÖDEME BİLDİRİMİ.
     * Domain/hosting sahibine yenileme ödeme hatırlatması gönderir (mail + SMS).
     * Tutar, satış kaydında yoksa uzantıya göre yenileme fiyatından alınır.
     */
    public function odemeBildirimi(Request $request, string $kaynak, int $id)
    {
        $kayit = $this->kayitBul($kaynak, $id);
        if (!$kayit) {
            return back()->with('error', 'Kayıt bulunamadı.');
        }

        $domain = $kayit->domain ?? '—';
        $bitis  = $kayit->bitis_tarih ?? $kayit->bitis_tarihi ?? null;
        $uyeId  = $kayit->uye_id ?? $kayit->uyeid ?? null;

        $uye = $uyeId ? DB::table('uyeler')->find($uyeId) : null;
        if (!$uye) {
            return back()->with('error', 'Bu kaydın üye bilgisi bulunamadı, bildirim gönderilemedi.');
        }

        $tutar = (float) ($kayit->tutar ?? 0);
        if ($tutar <= 0) {
            $tutar = $this->yenilemeFiyati($domain);
        }

        $ad = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: ($uye->firmaadi ?: 'Değerli Müşterimiz');
        $bitisYazi = $bitis ? \Carbon\Carbon::parse($bitis)->format('d.m.Y') : '—';
        $tutarYazi = $tutar > 0 ? number_format($tutar, 2, ',', '.') . ' TL' : null;

        $gidenler = [];
        $hatalar  = [];

        // E-posta
        $mail = trim((string) ($uye->email ?? ''));
        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            try {
                \App\Services\EmailNotificationService::send(
                    $mail,
                    '⏰ ' . $domain . ' yenileme ödemesi',
                    $this->bildirimGovdesi($ad, $domain, $bitisYazi, $tutarYazi)
                );
                $gidenler[] = 'E-posta (' . $mail . ')';
            } catch (\Throwable $e) {
                $hatalar[] = 'e-posta gönderilemedi';
            }
        } else {
            $hatalar[] = 'e-posta adresi yok';
        }

        // SMS
        $tel = trim((string) ($uye->telefon ?? ''));
        if ($tel !== '') {
            try {
                $mesaj = 'Sayin ' . $ad . ', ' . $domain . ' hizmetinizin bitis tarihi ' . $bitisYazi . '.'
                    . ($tutarYazi ? ' Yenileme bedeli: ' . $tutarYazi . '.' : '')
                    . ' Odemeniz icin tesekkur ederiz.';
                $sonuc = (new \App\Services\SmsService())->send($tel, $mesaj);
                if (!empty($sonuc['success'])) {
                    $gidenler[] = 'SMS (' . $tel . ')';
                } else {
                    $hatalar[] = 'SMS gönderilemedi';
                }
            } catch (\Throwable $e) {
                $hatalar[] = 'SMS gönderilemedi';
            }
        } else {
            $hatalar[] = 'telefon kayıtlı değil';
        }

        if (!empty($gidenler)) {
            return back()->with('success', 'Ödeme bildirimi gönderildi: ' . implode(' + ', $gidenler) . '.'
                . (!empty($hatalar) ? ' (Not: ' . implode(', ', $hatalar) . '.)' : ''));
        }

        return back()->with('error', 'Bildirim gönderilemedi: ' . implode(', ', $hatalar) . '.');
    }

    /**
     * Kaynağa göre kaydı bulur (metunic / order / satilanlar).
     * Üye alanları uye_id, e-posta/telefon uye_email, uye_telefon olarak normalize edilir.
     */
    private function kayitBul(string $kaynak, int $id): ?object
    {
        try {
            if ($kaynak === 'metunic' && Schema::hasTable('metunic_domains')) {
                return DB::table('metunic_domains as m')
                    ->leftJoin('uyeler as u', 'u.id', '=', 'm.uye_id')
                    ->where('m.id', $id)
                    ->select('m.*', 'm.uye_id as uye_id', 'u.email as uye_email', 'u.telefon as uye_telefon')
                    ->first();
            }

            if ($kaynak === 'order' && Schema::hasTable('domain_orders')) {
                return DB::table('domain_orders as d')
                    ->leftJoin('uyeler as u', 'u.id', '=', 'd.user_id')
                    ->where('d.id', $id)
                    ->select('d.*', 'd.user_id as uye_id', 'u.email as uye_email', 'u.telefon as uye_telefon')
                    ->first();
            }

            return DB::table('satilanlar as s')
                ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                ->where('s.id', $id)->whereIn('s.tipi', ['1', '2', '3'])
                ->select('s.*', 's.uyeid as uye_id', 'u.email as uye_email', 'u.telefon as uye_telefon')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Uzantıya göre domain yenileme fiyatı */
    private function yenilemeFiyati(?string $domain): float
    {
        if (empty($domain) || !Schema::hasTable('domain_fiyatlar')) return 0.0;

        $parcalar = explode('.', mb_strtolower(trim($domain)));
        if (count($parcalar) < 2) return 0.0;

        $adaylar = [];
        if (count($parcalar) >= 3) $adaylar[] = '.' . implode('.', array_slice($parcalar, -2));
        $adaylar[] = '.' . end($parcalar);

        foreach ($adaylar as $uzanti) {
            try {
                $f = DB::table('domain_fiyatlar')->where('uzanti', $uzanti)->where('durum', 1)->value('yenileme_fiyat');
                if ($f && (float) $f > 0) return (float) $f;
            } catch (\Throwable $e) {}
        }

        return 0.0;
    }

    /** Bildirim maili gövdesi */
    private function bildirimGovdesi(string $ad, string $domain, string $bitis, ?string $tutar): string
    {
        $sat = function (string $e, ?string $d) {
            if (!$d) return '';
            return '<tr><td style="padding:11px 0;border-bottom:1px solid #f0efe6">'
                . '<div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;margin-bottom:3px">' . htmlspecialchars($e) . '</div>'
                . '<div style="font-size:15px;color:#2b2b1f;font-weight:700">' . htmlspecialchars($d) . '</div></td></tr>';
        };

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:Arial,Helvetica,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0e8;padding:32px 16px"><tr><td align="center">
  <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#fff;border-radius:18px;overflow:hidden">
    <tr><td style="background:#1a2332;padding:24px 32px;text-align:center">
      <img src="https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png" width="150" alt="DN Kreatif" style="display:block;margin:0 auto 10px">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Yenileme Ödemesi ⏰</div>
    </td></tr>
    <tr><td style="height:5px;background:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:28px 32px 14px">
      <h2 style="margin:0 0 8px;font-size:21px;color:#1a1a0e">Sayın ' . htmlspecialchars($ad) . ',</h2>
      <p style="margin:0 0 18px;color:#6b6f63;font-size:14px;line-height:1.7">
        <strong>' . htmlspecialchars($domain) . '</strong> hizmetinizin süresi dolmak üzere.
        Kesintisiz devam edebilmesi için yenileme ödemenizi bekliyoruz.
      </p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">'
        . $sat('Hizmet', $domain) . $sat('Bitiş Tarihi', $bitis) . $sat('Yenileme Bedeli', $tutar) .
      '</table>
      <p style="margin:18px 0 0;color:#6b6f63;font-size:13px;line-height:1.6">
        Ödeme yaptıysanız bu mesajı dikkate almayınız. Sorularınız için bize ulaşabilirsiniz.
      </p>
    </td></tr>
    <tr><td style="background:#1a2332;padding:18px 32px;text-align:center">
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . date('Y') . ' DN Kreatif &middot; İş Ortağım</p>
    </td></tr>
  </table>
</td></tr></table></body></html>';
    }

    public function create(Request $request)
    {
        $uyeler = collect();
        if (Schema::hasTable('uyeler')) {
            $uyeler = DB::table('uyeler')
                ->select('id', 'ad', 'soyad', 'email', 'firmaadi')
                ->orderBy('ad')
                ->get();
        }

        /*
         * DÜZELTME (31.07.2026) — "domain eklerken müşteri listede çıkmıyor".
         *
         * Müşteri seçici yalnızca `uyeler` (giriş hesabı) tablosundan besleniyordu.
         * CRM'de kayıtlı olup üye hesabı OLMAYAN müşteriler (80 kayıt) listeye hiç
         * girmiyordu; örn. "İlhan Bey #726 / info@marmarissunblue.com".
         * Artık onlar da ayrı bir grup olarak listeye ekleniyor; seçilirse domain
         * kaydı `crm_musteri_id` ile bağlanır (üye hesabı açılması gerekmez).
         */
        $crmMusteriler = collect();
        if (Schema::hasTable('crm_customers')) {
            $uyeEpostalar = $uyeler->pluck('email')
                ->filter()->map(fn ($e) => mb_strtolower(trim($e)))->flip();

            $crmMusteriler = DB::table('crm_customers')
                ->whereNull('birlesen_id')   // birleştirilmiş mükerrerler hariç
                ->select('id', 'adi', 'email', 'unvan', 'uye_id', 'durum')
                ->orderBy('adi')
                ->get()
                ->filter(function ($m) use ($uyeEpostalar) {
                    // Üye kaydı olanlar zaten listede — mükerrer gösterme
                    if (!empty($m->uye_id)) return false;
                    $e = mb_strtolower(trim((string) $m->email));
                    return $e === '' || !isset($uyeEpostalar[$e]);
                })
                ->values();
        }

        $selectedUyeId = $request->integer('uyeid') ?: null;

        [$hizmetFiyatlari, $vdsSecenekleri] = $this->fiyatListeleri();

        return view('admin.crm.domains.create', compact('uyeler', 'crmMusteriler', 'selectedUyeId', 'hizmetFiyatlari', 'vdsSecenekleri'));
    }

    public function store(Request $request)
    {
        // Seçim "123" (üye) ya da "crm:726" (üye hesabı olmayan CRM müşterisi) olabilir
        [$secilenUyeId, $secilenCrmId] = $this->musteriSecimiCoz($request->input('uyeid'));
        $request->merge(['uyeid' => $secilenUyeId]);

        $validated = $request->validate([
            'uyeid'           => 'nullable|integer|exists:uyeler,id',
            'domain'          => 'required|string|max:190',
            'tutar'           => 'nullable|numeric|min:0',
            'baslangic_tarih' => 'nullable|date',
            'bitis_tarih'     => 'nullable|date|after_or_equal:baslangic_tarih',
            'durum'           => 'required|in:0,1',
            'mesaj'           => 'nullable|string|max:2000',
            'saglayici'       => 'nullable|string|max:100',
            'hizmetler'       => 'nullable|array',
            'hizmetler.*'     => 'string|max:200',
            'vds'             => 'nullable|string|max:100',
        ]);

        // En az biri olmalı: üye hesabı ya da CRM müşterisi
        if (!$secilenUyeId && !$secilenCrmId) {
            return back()->withInput()->withErrors(['uyeid' => 'Müşteri seçilmedi.']);
        }

        // Fiyat sunucuda, Hizmet Fiyatları tablosundaki sabit fiyatlardan hesaplanır
        $validated['tutar'] = $this->hesaplaTutar($validated['hizmetler'] ?? [], $validated['vds'] ?? null, $validated['tutar'] ?? null);

        $cols = Schema::hasTable('satilanlar') ? Schema::getColumnListing('satilanlar') : [];
        $has  = fn ($c) => in_array($c, $cols, true);

        $baslangic = $validated['baslangic_tarih'] ?? now()->toDateString();
        $bitis     = $validated['bitis_tarih']     ?? \Carbon\Carbon::parse($baslangic)->addYear()->toDateString();

        $payload = [
            'uyeid'  => $secilenUyeId,
            'tipi'   => '3',
            'domain' => strtolower(trim($validated['domain'])),
            'durum'  => (int) $validated['durum'],
        ];
        /*
         * CRM MÜŞTERİ BAĞI (düzeltme 31.07.2026)
         *
         * Eskiden yalnızca `uyeid` yazılıyordu; `crm_musteri_id` hiç doldurulmuyordu
         * (886 kayıttan sadece 1'inde doluydu). Müşteri kartı iki alandan da eşleştirme
         * yaptığı için, üye kaydı OLMAYAN müşterilerde domain hiç görünmüyordu.
         * Artık üye üzerinden CRM müşterisi bulunup bağ da yazılıyor.
         */
        if ($has('crm_musteri_id')) {
            // CRM müşterisi doğrudan seçildiyse onu kullan; değilse üyeden çöz
            $payload['crm_musteri_id'] = $secilenCrmId ?: $this->crmMusteriBul($secilenUyeId);
        }
        if ($has('maliyet'))          $payload['maliyet']         = $validated['maliyet'] ?? null;
        if ($has('tutar'))            $payload['tutar']           = $validated['tutar'] ?? 0;
        if ($has('baslangic_tarih'))  $payload['baslangic_tarih'] = $baslangic;
        if ($has('bitis_tarih'))      $payload['bitis_tarih']     = $bitis;
        if ($has('tarih'))            $payload['tarih']           = now();
        if ($has('mesaj'))            $payload['mesaj']           = $validated['mesaj'] ?? null;
        if ($has('saglayici'))        $payload['saglayici']       = $validated['saglayici'] ?? null;
        if ($has('hizmetler'))        $payload['hizmetler']       = json_encode(array_values($validated['hizmetler'] ?? []), JSON_UNESCAPED_UNICODE);
        if ($has('vds'))              $payload['vds']             = $validated['vds'] ?? null;
        if ($has('domain_durum'))     $payload['domain_durum']    = 'kayitli';
        if ($has('created_at'))       $payload['created_at']      = now();
        if ($has('updated_at'))       $payload['updated_at']      = now();

        DB::table('satilanlar')->insert($payload);

        return redirect()->route('admin.crm.domains.index')
            ->with('success', 'Alan adı başarıyla eklendi.');
    }

    public function show(Request $request, string $kaynak, int $id)
    {
        if ($kaynak === 'metunic' && Schema::hasTable('metunic_domains')) {
            $row = DB::table('metunic_domains as m')
                ->leftJoin('uyeler as u', 'u.id', '=', 'm.uye_id')
                ->where('m.id', $id)
                ->select('m.*', 'u.email as uye_email', 'u.telefon as uye_telefon', 'u.ad as uye_ad', 'u.soyad as uye_soyad', 'u.firmaadi as uye_firma')
                ->first();
            abort_if(!$row, 404);
            return view('admin.crm.domains.show', compact('row', 'kaynak'));
        }

        if ($kaynak === 'order' && Schema::hasTable('domain_orders')) {
            $row = DB::table('domain_orders as d')
                ->leftJoin('uyeler as u', 'u.id', '=', 'd.user_id')
                ->where('d.id', $id)
                ->select('d.*', 'u.email as uye_email', 'u.telefon as uye_telefon', 'u.ad as uye_ad', 'u.soyad as uye_soyad', 'u.firmaadi as uye_firma')
                ->first();
        } else {
            $row = DB::table('satilanlar as s')
                ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                ->where('s.id', $id)
                ->whereIn('s.tipi', ['1', '2', '3'])
                ->select('s.*', 'u.email as uye_email', 'u.telefon as uye_telefon', 'u.ad as uye_ad', 'u.soyad as uye_soyad', 'u.firmaadi as uye_firma')
                ->first();
        }

        abort_if(!$row, 404);

        return view('admin.crm.domains.show', compact('row', 'kaynak'));
    }

    public function edit(string $kaynak, int $id)
    {
        // tipi 3 = manuel domain, tipi 1/2 = hosting. index() üçünü de listeliyor,
        // bu yüzden edit de üçünü birden kabul etmeli (yoksa hosting satırları 404 verir).
        $row = DB::table('satilanlar as s')
            ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
            ->where('s.id', $id)
            ->whereIn('s.tipi', ['1', '2', '3'])
            ->select('s.*', 'u.email as uye_email', 'u.ad as uye_ad', 'u.soyad as uye_soyad', 'u.firmaadi as uye_firma')
            ->first();
        abort_if(!$row, 404);

        $uyeler = DB::table('uyeler')
            ->select('id', 'ad', 'soyad', 'email', 'firmaadi')
            ->orderBy('ad')->get();

        [$hizmetFiyatlari, $vdsSecenekleri] = $this->fiyatListeleri();

        return view('admin.crm.domains.edit', compact('row', 'uyeler', 'hizmetFiyatlari', 'vdsSecenekleri'));
    }

    public function update(Request $request, string $kaynak, int $id)
    {
        $validated = $request->validate([
            'uyeid'           => 'required|integer|exists:uyeler,id',
            'domain'          => 'required|string|max:190',
            'tutar'           => 'nullable|numeric|min:0',
            'baslangic_tarih' => 'nullable|date',
            'bitis_tarih'     => 'nullable|date|after_or_equal:baslangic_tarih',
            'durum'           => 'required|in:0,1',
            'mesaj'           => 'nullable|string|max:2000',
            'saglayici'       => 'nullable|string|max:100',
            'hizmetler'       => 'nullable|array',
            'hizmetler.*'     => 'string|max:200',
            'vds'             => 'nullable|string|max:100',
        ]);

        $validated['tutar'] = $this->hesaplaTutar($validated['hizmetler'] ?? [], $validated['vds'] ?? null, $validated['tutar'] ?? null);

        $cols = Schema::getColumnListing('satilanlar');
        $has = fn ($c) => in_array($c, $cols, true);

        $payload = [
            'uyeid'  => $validated['uyeid'],
            'domain' => strtolower(trim($validated['domain'])),
            'durum'  => (int) $validated['durum'],
        ];
        // CRM müşteri bağı — güncellemede de yazılsın (bkz. store()'daki açıklama)
        if ($has('crm_musteri_id')) {
            $payload['crm_musteri_id'] = $this->crmMusteriBul((int) $validated['uyeid']);
        }
        if ($has('maliyet'))         $payload['maliyet']         = $validated['maliyet'] ?? null;
        if ($has('tutar'))           $payload['tutar']           = $validated['tutar'] ?? 0;
        if ($has('baslangic_tarih')) $payload['baslangic_tarih'] = $validated['baslangic_tarih'];
        if ($has('bitis_tarih'))     $payload['bitis_tarih']     = $validated['bitis_tarih'];
        if ($has('saglayici'))       $payload['saglayici']       = $validated['saglayici'];
        if ($has('hizmetler'))       $payload['hizmetler']       = json_encode(array_values($validated['hizmetler'] ?? []), JSON_UNESCAPED_UNICODE);
        if ($has('vds'))             $payload['vds']             = $validated['vds'] ?? null;
        if ($has('mesaj'))           $payload['mesaj']           = $validated['mesaj'];
        if ($has('updated_at'))      $payload['updated_at']      = now();

        DB::table('satilanlar')->where('id', $id)->whereIn('tipi', ['1', '2', '3'])->update($payload);

        return redirect()->route('admin.crm.domains.index')
            ->with('success', 'Domain bilgileri güncellendi.');
    }

    public function destroy(string $kaynak, int $id)
    {
        if ($kaynak === 'order') {
            DB::table('domain_orders')->where('id', $id)->delete();

            return redirect()->route('admin.crm.domains.index')
                ->with('success', 'Domain kaydı silindi.');
        }

        // Silmeden ÖNCE kaydı İşlem Geçmişi'ne al — geri alınabilir olsun
        $kayit = DB::table('satilanlar')->where('id', $id)->whereIn('tipi', ['1', '2', '3'])->first();
        if ($kayit) {
            \App\Services\IslemGecmisi::silmeKaydet(
                'satilanlar',
                $id,
                'Domain/hizmet kaydı silindi: ' . mb_substr((string) ($kayit->domain ?: ('#' . $id)), 0, 80)
            );
        }

        DB::table('satilanlar')->where('id', $id)->whereIn('tipi', ['1', '2', '3'])->delete();

        return redirect()->route('admin.crm.domains.index')
            ->with('success', 'Domain kaydı silindi. İşlem Geçmişi\'nden geri alınabilir.');
    }

    /** hizmet_fiyatlari -> [hizmetler, vds seçenekleri] (VDS etiketliler ayrılır) */
    private function fiyatListeleri(): array
    {
        $tum = Schema::hasTable('hizmet_fiyatlari')
            ? DB::table('hizmet_fiyatlari')->orderBy('sira')->get()
            : collect();

        $vds     = $tum->filter(fn ($f) => stripos($f->etiket ?? '', 'vds') !== false)->values();
        $hizmet  = $tum->filter(fn ($f) => stripos($f->etiket ?? '', 'vds') === false)->values();

        return [$hizmet, $vds];
    }

    /** Seçilen hizmetlerin sabit fiyat toplamı; hizmet seçilmemişse formdan gelen tutar korunur */
    private function hesaplaTutar(array $hizmetler, ?string $vds, $fallback)
    {
        if (empty($hizmetler) && empty($vds)) {
            return $fallback ?? 0;
        }
        if (!Schema::hasTable('hizmet_fiyatlari')) {
            return $fallback ?? 0;
        }

        $etiketler = $hizmetler;
        if ($vds) $etiketler[] = $vds;

        return (float) DB::table('hizmet_fiyatlari')
            ->whereIn('etiket', $etiketler)
            ->sum('fiyat');
    }

    /**
     * Metunic'ten domainleri canlı çek, metunic_domains tablosuna upsert et.
     * Buton (POST) buraya gelir; işi bitince listeye döner.
     */
    public function metunicSenkron(Request $request)
    {
        if (!Schema::hasTable('metunic_domains')) {
            return redirect()->route('admin.crm.domains.index')
                ->with('error', 'metunic_domains tablosu yok. Önce SQL\'i çalıştırın.');
        }

        $svc  = new \App\Services\MetunicService();
        $resp = $svc->servisListesi();

        if (!($resp['ok'] ?? false)) {
            return redirect()->route('admin.crm.domains.index')
                ->with('error', 'Metunic bağlantısı başarısız: ' . ($resp['hata'] ?? 'bilinmeyen hata'));
        }

        $veri  = $resp['veri'] ?? [];
        $liste = is_array($veri) ? ($veri['result'] ?? []) : [];
        if (!is_array($liste)) { $liste = []; }

        $eklenen = 0;
        $guncellenen = 0;
        $simdi = now();

        foreach ($liste as $srv) {
            if (!is_array($srv) || empty($srv['id'])) { continue; }

            $serviceId = (int) $srv['id'];
            $payload = [
                'service_id'  => $serviceId,
                'domain'      => mb_strtolower((string) ($srv['name'] ?? ''), 'UTF-8'),
                'status'      => $srv['status'] ?? null,
                'date_added'  => !empty($srv['dateAdded'])  ? $srv['dateAdded']  : null,
                'date_renews' => !empty($srv['dateRenews']) ? $srv['dateRenews'] : null,
                'id_code'     => isset($srv['idCode']) ? (string) $srv['idCode'] : null,
                'client_id'   => isset($srv['clientId']) ? (int) $srv['clientId'] : null,
                'raw_json'    => json_encode($srv, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'senkron_at'  => $simdi,
                'updated_at'  => $simdi,
            ];

            $mevcut = DB::table('metunic_domains')->where('service_id', $serviceId)->first();
            if ($mevcut) {
                DB::table('metunic_domains')->where('service_id', $serviceId)->update($payload);
                $guncellenen++;
            } else {
                $payload['created_at'] = $simdi;
                DB::table('metunic_domains')->insert($payload);
                $eklenen++;
            }
        }

        return redirect()->route('admin.crm.domains.index')
            ->with('success', "Metunic senkron tamamlandı: {$eklenen} yeni, {$guncellenen} güncellendi (toplam " . count($liste) . ').');
    }

    /**
     * Müşteri seçimini çözer.
     *
     * Form tek bir alan (`uyeid`) gönderiyor ama iki tür değer taşıyabiliyor:
     *   "123"     → üye (giriş hesabı) id'si
     *   "crm:726" → üye hesabı OLMAYAN CRM müşterisinin id'si
     *
     * @return array{0: ?int, 1: ?int}  [uyeId, crmMusteriId]
     */
    private function musteriSecimiCoz($deger): array
    {
        $d = trim((string) $deger);
        if ($d === '') return [null, null];

        if (str_starts_with($d, 'crm:')) {
            $crmId = (int) substr($d, 4);
            if ($crmId <= 0) return [null, null];

            // CRM kaydının bağlı üyesi varsa onu da yakala (sonradan bağlanmış olabilir)
            $uyeId = null;
            try {
                if (Schema::hasColumn('crm_customers', 'uye_id')) {
                    $uyeId = DB::table('crm_customers')->where('id', $crmId)->value('uye_id') ?: null;
                }
            } catch (\Throwable $e) {
                // önemli değil, CRM bağı yeterli
            }

            return [$uyeId ? (int) $uyeId : null, $crmId];
        }

        $uyeId = (int) $d;

        return [$uyeId > 0 ? $uyeId : null, null];
    }

    /**
     * Üye id'sinden CRM müşteri kaydını bulur.
     *
     * Domain formunda müşteri ÜYE olarak seçiliyor ama müşteri kartı hem `uyeid`
     * hem `crm_musteri_id` üzerinden eşleşme arıyor. İkinci alan hiç yazılmadığı
     * için, üye kaydı olmayan/eşleşmeyen müşterilerde domainler görünmüyordu.
     *
     * Sıra: crm_customers.uye_id → e-posta eşleşmesi.
     */
    private function crmMusteriBul(?int $uyeId): ?int
    {
        if (!$uyeId || !Schema::hasTable('crm_customers')) return null;

        try {
            if (Schema::hasColumn('crm_customers', 'uye_id')) {
                $id = DB::table('crm_customers')->where('uye_id', $uyeId)->value('id');
                if ($id) return (int) $id;
            }

            $email = Schema::hasTable('uyeler')
                ? trim((string) DB::table('uyeler')->where('id', $uyeId)->value('email'))
                : '';

            if ($email !== '') {
                $id = DB::table('crm_customers')
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->value('id');
                if ($id) return (int) $id;
            }
        } catch (\Throwable $e) {
            // Bağ kurulamazsa domain kaydı yine de kaydedilsin
        }

        return null;
    }

    /**
     * DOMAIN & HOSTING KÂR RAPORU (5. öncelik).
     * Satış − alış = kâr. Hesap App\Services\DomainKarRaporu içinde.
     */
    public function karRaporu(Request $request)
    {
        $yil = (int) $request->query('yil') ?: null;
        $v   = \App\Services\DomainKarRaporu::veri($yil);

        return view('admin.crm.domains.kar-raporu', compact('v'));
    }

    /**
     * METUNIC KAYDINA ELLE TUTAR / MALİYET GİRME.
     *
     * Metunic senkronu yalnızca domain ve tarih getiriyor, fiyat bilgisi
     * yok. Bizde karşılığı olmayan kayıtlar bu yüzden kâr raporunda hiç
     * görünemiyordu. Tutar girilene kadar kayıt ciroya KATILMAZ — tahmini
     * rakamla raporu şişirmek yerine "bilinmiyor" kalması tercih edildi.
     *
     * satis_tarihi ayrı tutuluyor: tablodaki `date_added` domainin ilk
     * tescil tarihi (veride 2020-2026 arasına yayılıyor), satış tarihi
     * değil. Ciroyu ona göre dağıtmak geliri yanlış yıllara yazar.
     */
    public function metunicTutar(Request $request, $id)
    {
        if (! Schema::hasTable('metunic_domains')) {
            return back()->with('error', 'Metunic tablosu bulunamadı.');
        }

        $kayit = DB::table('metunic_domains')->where('id', $id)->first();
        if (! $kayit) {
            return back()->with('error', 'Kayıt bulunamadı.');
        }

        $veri = $request->validate([
            'tutar'        => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'maliyet'      => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'satis_tarihi' => ['nullable', 'date'],
        ], [], [
            'tutar'        => 'tutar',
            'maliyet'      => 'maliyet',
            'satis_tarihi' => 'satış tarihi',
        ]);

        $yaz = [];
        foreach (['tutar', 'maliyet', 'satis_tarihi'] as $alan) {
            if (! Schema::hasColumn('metunic_domains', $alan)) {
                continue;
            }
            // Boş gönderilen alan NULL yazılır — "0 TL" ile "bilinmiyor" ayrı şeyler
            $deger = $veri[$alan] ?? null;
            $yaz[$alan] = ($deger === '' || $deger === null) ? null : $deger;
        }

        /*
         * Tutar girilmiş ama satış tarihi boşsa rapor bu kaydı hiçbir aya
         * yazamaz ve kayıt sessizce ciro dışında kalır. Bugünün tarihi
         * varsayılan olarak konuyor.
         */
        if (! empty($yaz['tutar']) && empty($yaz['satis_tarihi'])
            && Schema::hasColumn('metunic_domains', 'satis_tarihi')) {
            $yaz['satis_tarihi'] = Carbon::today()->toDateString();
        }

        if ($yaz) {
            DB::table('metunic_domains')->where('id', $id)->update($yaz);
        }

        return back()->with('success', $kayit->domain . ' güncellendi.');
    }
}
