<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data Center — aktif olmayan kayıtların deposu/arşivi.
 * Aktif müşteriler CRM'de yönetilir; burada artık çalışılmayan (pasif/arşiv)
 * müşteriler + tüm üyeler + tüm bayiler bilgileriyle saklanır ve görüntülenir.
 * Ayrıca: kişi (müşteri) ekleme/düzenleme, ne kadar süredir kayıtlı ve
 * ne kadar süredir iletişime geçilmediği bilgisi.
 */
class DataCenterController extends Controller
{
    public function index()
    {
        $kayitlar = $this->topla();

        $istatistik = [
            'toplam'  => $kayitlar->count(),
            'musteri' => $kayitlar->where('kaynak', 'Müşteri')->count(),
            'uye'     => $kayitlar->where('kaynak', 'Üye')->count(),
            'bayi'    => $kayitlar->where('kaynak', 'Bayi')->count(),
            'sessiz'  => $kayitlar->where('temas_gun', '>', 180)->count(), // 6+ aydır iletişim yok
        ];

          $durumlar = collect(['pasif', 'potansiyel', 'aktif'])->sort()->values();

        $mailSablonlari = Schema::hasTable('mail_templates')
            ? DB::table('mail_templates')->where('aktif', 1)->orderBy('name')->get()
            : collect();

        return view('admin.data-center.index', [
            'baslik'         => 'Data Center',
            'kayitlar'       => $kayitlar->sortBy('ad')->values(),
            'istatistik'     => $istatistik,
            'durumlar'       => $durumlar,
            'mailSablonlari' => $mailSablonlari,
            'listeler'       => \App\Models\CRM\CrmListe::where('durum', 1)->orderBy('ad')->get(),
        ]);
    }

    /** durum değerini insan-okur etikete çevirir (üye/bayi sayısal 0/1 dahil). */
    private function durumLabel($durum): string
    {
        $x = mb_strtolower(trim((string) $durum), 'UTF-8');
        if ($x === '') return '';
        $map = [
            '1' => 'Aktif', '0' => 'Pasif', 'aktif' => 'Aktif', 'pasif' => 'Pasif',
            'potansiyel' => 'Potansiyel', 'sicak' => 'Sıcak', 'ilimli' => 'Ilımlı',
            'ılımlı' => 'Ilımlı', 'soguk' => 'Soğuk', 'soğuk' => 'Soğuk',
        ];
        return $map[$x] ?? (mb_strtoupper(mb_substr($x, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($x, 1, null, 'UTF-8'));
    }

    /** Gün farkını insan diline çevir (TR) */
    private function insanSure(?string $tarih): array
    {
        if (!$tarih || !strtotime($tarih)) return ['gun' => null, 'metin' => '—'];
        $gun = (int) floor((time() - strtotime($tarih)) / 86400);
        if ($gun < 0) $gun = 0;

        if ($gun === 0)      $metin = 'bugün';
        elseif ($gun < 30)   $metin = $gun . ' gün';
        elseif ($gun < 365)  $metin = floor($gun / 30) . ' ay';
        else {
            $yil = floor($gun / 365);
            $ay  = floor(($gun % 365) / 30);
            $metin = $yil . ' yıl' . ($ay ? ' ' . $ay . ' ay' : '');
        }
        return ['gun' => $gun, 'metin' => $metin];
    }

    /** En geç (büyük) tarihi seç */
    private function enSonTarih(array $tarihler): ?string
    {
        $gecerli = array_filter($tarihler, fn ($t) => $t && strtotime((string) $t));
        if (!$gecerli) return null;
        usort($gecerli, fn ($a, $b) => strtotime((string) $b) <=> strtotime((string) $a));
        return (string) $gecerli[0];
    }

    private function topla()
    {
        $hepsi = collect();

        // ── Pasif / aktif olmayan müşteriler ──
        if (Schema::hasTable('crm_customers')) {
            // Data Center = tüm verinin deposu → TÜM müşteriler (aktif dahil)
            $musteriler = DB::table('crm_customers')
                ->orderBy('adi')
                ->get();

            $mIds = $musteriler->pluck('id')->filter()->values()->all();

            // Her müşterinin Data Center liste id'leri (tek toplu sorgu, N+1 yok)
            $listeMap = Schema::hasTable('crm_customer_liste')
                ? DB::table('crm_customer_liste')->whereIn('customer_id', $mIds)->get()
                    ->groupBy('customer_id')->map(fn ($r) => $r->pluck('liste_id')->values()->all())
                : collect();

            // Son temas tarihleri: HER müşteri için ayrı alt-sorgu (N+1) yerine üçer TEK toplu sorgu
            $notMax  = $this->grupMax('crm_notes',  'musteri_id',     'created_at', $mIds);
            $randMax = $this->grupMax('randevular', 'crm_musteri_id', 'baslangic',  $mIds);
            $satMax  = $this->grupMax('satilanlar', 'crm_musteri_id', 'tarih',      $mIds);

            foreach ($musteriler as $c) {
                $sonTemas = $this->enSonTarih([
                    $notMax[$c->id]  ?? null,
                    $randMax[$c->id] ?? null,
                    $satMax[$c->id]  ?? null,
                    $c->updated_at ?? null,
                    $c->created_at ?? null,
                ]);
                $kayit = $this->insanSure($c->created_at ?? null);
                $temas = $this->insanSure($sonTemas);

                $hepsi->push([
                    'kaynak'    => 'Müşteri',
                    'kaynak_id' => $c->id,
                    'liste_ids' => $listeMap[$c->id] ?? [],
                    'ad'        => $c->adi ?: '—',
                    'email'     => trim((string) ($c->email ?? '')),
                    'telefon'   => trim((string) ($c->gsm ?: ($c->telefon ?? ''))),
                    'sektor'    => trim((string) ($c->sektor ?? '')),
                    'konum'     => trim(implode(' / ', array_filter([$c->il ?? null, $c->ilce ?? null]))),
                    'durum'     => $this->durumLabel($c->durum),
                    'kayit_sure'=> $kayit['metin'],
                    'temas_sure'=> $temas['metin'],
                    'temas_gun' => $temas['gun'],
                    'duzenlenebilir' => true,
                    'aktife_alinabilir' => true,
                    // Giriş hesabı (üye) var mı? uye_id doluysa müşterinin giriş yapabildiği hesabı vardır.
                    'hesap_var' => !empty($c->uye_id),
                    'd' => [
                        'adi' => $c->adi, 'email' => $c->email, 'telefon' => $c->telefon, 'gsm' => $c->gsm,
                        'sektor' => $c->sektor, 'il' => $c->il, 'ilce' => $c->ilce,
                        'unvan' => $c->unvan ?? '', 'adres' => $c->adres ?? '', 'durum' => $c->durum ?: 'pasif',
                    ],
                ]);
            }
        }

        // ── Üyeler ── (alt-sorgu yok, tek çekim)
        if (Schema::hasTable('uyeler')) {
            DB::table('uyeler')->orderBy('ad')->get()
                ->each(function ($u) use ($hepsi) {
                    $ad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
                    if ($ad === '' && !empty($u->firmaadi)) $ad = $u->firmaadi;
                    $sonTemas = $this->enSonTarih([$u->son_giris ?? null, $u->ktarih ?? null]);
                    $kayit = $this->insanSure($u->ktarih ?? null);
                    $temas = $this->insanSure($sonTemas);

                    $hepsi->push([
                        'kaynak'    => 'Üye',
                        'kaynak_id' => $u->id,
                        'ad'        => $ad ?: '—',
                        'email'     => trim((string) ($u->email ?? '')),
                        'telefon'   => trim((string) ($u->telefon ?? '')),
                        'sektor'    => '',
                        'konum'     => trim(implode(' / ', array_filter([$u->sehir ?? null, $u->ilce ?? null]))),
                        'durum'     => $this->durumLabel($u->durum ?? ''),
                        'kayit_sure'=> $kayit['metin'],
                        'temas_sure'=> $temas['metin'],
                        'temas_gun' => $temas['gun'],
                        'duzenlenebilir' => false,
                        'aktife_alinabilir' => false,
                    ]);
                });
        }

        // ── Bayiler ──
        if (Schema::hasTable('bayiler')) {
            $bayiler = DB::table('bayiler')->get();

            // Bayi e-postaları: HER bayi için ayrı sorgu (N+1) yerine tek toplu sorgu
            $uyeIds = $bayiler->pluck('uye_id')->filter()->unique()->values()->all();
            $bayiEmail = [];
            if (!empty($uyeIds) && Schema::hasTable('uyeler')) {
                $bayiEmail = DB::table('uyeler')->whereIn('id', $uyeIds)->pluck('email', 'id')->all();
            }

            foreach ($bayiler as $b) {
                $email = trim((string) ($bayiEmail[$b->uye_id] ?? ''));
                $sonTemas = $this->enSonTarih([$b->updated_at ?? null, $b->created_at ?? null]);
                $kayit = $this->insanSure($b->created_at ?? null);
                $temas = $this->insanSure($sonTemas);

                $hepsi->push([
                    'kaynak'    => 'Bayi',
                    'kaynak_id' => $b->id,
                    'ad'        => $b->firma_adi ?: ('Bayi #' . $b->id),
                    'email'     => $email,
                    'telefon'   => trim((string) ($b->telefon ?? '')),
                    'sektor'    => '',
                    'konum'     => trim(implode(' / ', array_filter([$b->il ?? null, $b->ilce ?? null]))),
                    'durum'     => $this->durumLabel($b->durum ?? ''),
                    'kayit_sure'=> $kayit['metin'],
                    'temas_sure'=> $temas['metin'],
                    'temas_gun' => $temas['gun'],
                    'duzenlenebilir' => false,
                    'aktife_alinabilir' => false,
                ]);
            }
        }

        // Aynı kişi birden fazla kaynakta olabilir (örn. hem Üye hem Müşteri) →
        // e-posta (yoksa telefon) bazında TEKİLLEŞTİR. Öncelik: Müşteri > Üye > Bayi
        // (daha zengin / işlem yapılabilir kayıt kalsın).
        $oncelik = ['Müşteri' => 3, 'Üye' => 2, 'Bayi' => 1];
        $hepsi = $hepsi
            ->sortByDesc(fn ($k) => $oncelik[$k['kaynak']] ?? 0)
            ->unique(function ($k) {
                $mail = mb_strtolower(trim((string) ($k['email'] ?? '')), 'UTF-8');
                if ($mail !== '') return 'e:' . $mail;
                $tel = preg_replace('/\D/', '', (string) ($k['telefon'] ?? ''));
                if ($tel !== '') return 't:' . $tel;
                // e-posta ve telefon yoksa tekilleştirme (benzersiz anahtar)
                return 'x:' . $k['kaynak'] . ':' . ($k['kaynak_id'] ?? uniqid());
            })
            ->values();

        return $hepsi;
    }

    /**
     * Bir tabloda, verilen id listesi için kolon bazinda MAX degerini TEK sorguda getirir.
     * Boylece her kayit icin ayri alt-sorgu (N+1) calismaz. Tablo yoksa/hata olursa bos doner.
     */
    private function grupMax(string $tablo, string $idKolon, string $tarihKolon, array $ids): array
    {
        if (empty($ids) || !Schema::hasTable($tablo)) return [];
        try {
            return DB::table($tablo)
                ->whereIn($idKolon, $ids)
                ->groupBy($idKolon)
                ->select(DB::raw($idKolon . ' as k'), DB::raw('MAX(' . $tarihKolon . ') as m'))
                ->pluck('m', 'k')
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function guvenli(callable $fn)
    {
        try { return $fn(); } catch (\Throwable $e) { return null; }
    }

    /** Kişi (müşteri) ekle/düzenle */
    public function kisiKaydet(Request $request)
    {
        $data = $request->validate([
            'id'           => 'nullable|integer',
            'adi'          => 'required|string|max:190',
            'email'        => 'nullable|email|max:190',
            'telefon'      => 'nullable|string|max:40',
            'sektor'       => 'nullable|string|max:120',
            'il'           => 'nullable|string|max:80',
            'ilce'         => 'nullable|string|max:80',
            'unvan'        => 'nullable|string|max:190',
            'adres'        => 'nullable|string|max:500',
            'durum'        => 'nullable|string|max:20',
            'liste_ids'    => 'nullable|array',
            'liste_ids.*'  => 'integer|exists:crm_listeler,id',
            'uye_yap'      => 'nullable|boolean',   // kayıtlı üye olarak da ekle
        ]);

        $kayit = [
            'adi'     => $data['adi'],
            'email'   => $data['email'] ?? null,
            'telefon' => $data['telefon'] ?? null,
            'sektor'  => $data['sektor'] ?? null,
            'il'      => $data['il'] ?? null,
            'ilce'    => $data['ilce'] ?? null,
            'unvan'   => $data['unvan'] ?? null,
            'adres'   => $data['adres'] ?? null,
            'durum'   => $data['durum'] ?: 'pasif',
            'updated_at' => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('crm_customers')->where('id', $data['id'])->update($kayit);
            $customerId = (int) $data['id'];
            $msg = 'Kişi güncellendi.';
        } else {
            $kayit['created_at'] = now();
            $customerId = (int) DB::table('crm_customers')->insertGetId($kayit);
            $msg = 'Kişi eklendi.';
        }

        // Data Center listeleri senkronu (Çoka Çok) — seçilmeyenler çıkarılır
        if ($customerId && Schema::hasTable('crm_customer_liste')) {
            $sync = [];
            foreach ($data['liste_ids'] ?? [] as $lid) {
                $sync[(int) $lid] = ['kaynak' => 'manuel'];
            }
            $customer = \App\Models\CRM\Customer::find($customerId);
            if ($customer) {
                $customer->listeler()->sync($sync);
            }
        }

        // Kayıtlı üye olarak da ekle (giriş yapabilen gerçek müşteri)
        if (!empty($data['uye_yap'])) {
            if (empty($data['email'])) {
                return back()->with('info', $msg . ' Ancak kayıtlı üye oluşturmak için e-posta gerekli — üye eklenmedi.');
            }

            $kisi = (object) [
                'adi'     => $data['adi'],
                'soyad'   => '',
                'email'   => $data['email'],
                'telefon' => $data['telefon'] ?? null,
                'unvan'   => $data['unvan'] ?? null,
                'il'      => $data['il'] ?? null,
                'ilce'    => $data['ilce'] ?? null,
                'adres'   => $data['adres'] ?? null,
            ];
            $sonuc = \App\Services\CrmUyeOlusturucu::olustur($kisi);

            // CRM kaydını üyeye bağla
            if ($sonuc['uye_id'] && Schema::hasColumn('crm_customers', 'uye_id')) {
                DB::table('crm_customers')->where('id', $customerId)->update(['uye_id' => $sonuc['uye_id']]);
            }

            if ($sonuc['hata']) {
                return back()->with('info', $msg . ' Üye oluşturulamadı: ' . $sonuc['hata']);
            }
            if ($sonuc['yeni']) {
                return back()->with('success', $msg . ' Ayrıca kayıtlı üye oluşturuldu (geçici şifre: ' . $sonuc['sifre'] . '). Giriş bilgilerini müşteri kartından "Geçici Şifre Gönder" ile iletebilirsiniz.');
            }
            return back()->with('success', $msg . ' Bu e-posta zaten kayıtlı üye — mevcut üyeye bağlandı.');
        }

        return back()->with('success', $msg);
    }

    /** Pasif müşteriyi tekrar aktife al (CRM'e geri taşı) */
    public function musteriAktifeAl($id)
    {
        DB::table('crm_customers')->where('id', $id)->update(['durum' => 'aktif', 'updated_at' => now()]);
        return back()->with('success', 'Müşteri tekrar aktif edildi (CRM listesine taşındı).');
    }

    /** Tek müşterinin durumunu değiştir (aktif/pasif/potansiyel) — AJAX, dropdown'dan */
    public function musteriDurum(Request $request, $id)
    {
        $data = $request->validate([
            'durum' => 'required|string|in:aktif,pasif,potansiyel',
        ]);

        $var = DB::table('crm_customers')->where('id', $id)->exists();
        if (!$var) {
            return response()->json(['ok' => false, 'mesaj' => 'Müşteri bulunamadı.'], 404);
        }

        DB::table('crm_customers')->where('id', $id)->update([
            'durum'      => $data['durum'],
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'durum' => $data['durum']]);
    }

    /**
     * "Müşteri Yap" — müşteriye giriş yapabileceği bir hesap (üye) açar.
     * Sadece giriş hesabı olmayan (uye_id boş) müşteriler içindir.
     * Geçici şifre üretir; JSON döner (pop-up'ta gösterilir).
     */
    public function musteriYap(Request $request, $id)
    {
        $c = DB::table('crm_customers')->where('id', $id)->first();
        if (!$c) {
            return response()->json(['ok' => false, 'mesaj' => 'Müşteri bulunamadı.'], 404);
        }
        if (!empty($c->uye_id)) {
            return response()->json(['ok' => false, 'mesaj' => 'Bu müşterinin zaten giriş hesabı var.']);
        }
        $email = trim((string) ($c->email ?? ''));
        if ($email === '') {
            return response()->json(['ok' => false, 'mesaj' => 'Giriş hesabı açmak için müşterinin e-posta adresi gerekli. Önce "Düzenle" ile e-posta ekleyin.']);
        }

        $kisi = (object) [
            'adi'     => $c->adi,
            'soyad'   => '',
            'email'   => $email,
            'telefon' => $c->gsm ?: ($c->telefon ?? null),
            'unvan'   => $c->unvan ?? null,
            'il'      => $c->il ?? null,
            'ilce'    => $c->ilce ?? null,
            'adres'   => $c->adres ?? null,
        ];

        try {
            $sonuc = \App\Services\CrmUyeOlusturucu::olustur($kisi);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'mesaj' => 'Hesap açılamadı: ' . $e->getMessage()]);
        }

        // Müşteriyi açılan üyeye bağla
        if (!empty($sonuc['uye_id']) && Schema::hasColumn('crm_customers', 'uye_id')) {
            DB::table('crm_customers')->where('id', $id)->update([
                'uye_id'     => $sonuc['uye_id'],
                'updated_at' => now(),
            ]);
        }

        if (!empty($sonuc['hata'])) {
            return response()->json(['ok' => false, 'mesaj' => 'Üye oluşturulamadı: ' . $sonuc['hata']]);
        }

        if (!empty($sonuc['yeni'])) {
            return response()->json([
                'ok'     => true,
                'yeni'   => true,
                'mesaj'  => 'Giriş hesabı oluşturuldu.',
                'email'  => $email,
                'sifre'  => $sonuc['sifre'] ?? null,
            ]);
        }

        return response()->json([
            'ok'    => true,
            'yeni'  => false,
            'mesaj' => 'Bu e-posta zaten kayıtlı bir üyeye ait — mevcut üyeye bağlandı.',
            'email' => $email,
        ]);
    }

    /** Aktif müşteriyi arşivle (Data Center'a taşı) */
    public function musteriArsivle($id)
    {
        DB::table('crm_customers')->where('id', $id)->update(['durum' => 'pasif', 'updated_at' => now()]);
        return back()->with('success', 'Müşteri Data Center\'a (pasif) taşındı.');
    }

    /** Seçili/çoklu kayıtlara toplu e-posta (sektör/konum filtresiyle seçilenler) */
    public function topluMail(Request $request)
    {
        $data = $request->validate([
            'konu'       => 'required|string|max:200',
            'mesaj'      => 'required|string|max:5000',
            'alicilar'   => 'required|array|min:1',
            'alicilar.*' => 'email',
        ]);

        $mesaj = $data['mesaj'];
        // Şablon HTML olabilir → etiket içeriyorsa olduğu gibi gönder, düz metinse nl2br+escape
        $govde = preg_match('/<[a-z!\\/][^>]*>/i', $mesaj) ? $mesaj : nl2br(e($mesaj));
        $ok = 0; $hata = 0;
        foreach (array_values(array_unique($data['alicilar'])) as $email) {
            try {
                \App\Services\EmailNotificationService::send($email, $data['konu'], $govde, true);
                $ok++;
            } catch (\Throwable $e) {
                $hata++;
            }
        }

        return back()->with('success', $ok . ' kişiye e-posta gönderildi' . ($hata ? ', ' . $hata . ' başarısız' : '') . '.');
    }

    /** Seçili/çoklu kayıtlara toplu SMS */
    public function topluSms(Request $request)
    {
        $data = $request->validate([
            'mesaj'       => 'required|string|max:600',
            'numaralar'   => 'required|array|min:1',
            'numaralar.*' => 'string',
        ]);

        $ok = 0; $hata = 0;
        foreach (array_values(array_unique($data['numaralar'])) as $tel) {
            $tel = preg_replace('/\D+/', '', (string) $tel);
            if (strlen($tel) < 10) { $hata++; continue; }
            try {
                \App\Services\RandevuSms::gonder($tel, $data['mesaj']);
                $ok++;
            } catch (\Throwable $e) {
                $hata++;
            }
        }

        return back()->with('success', $ok . ' numaraya SMS gönderildi' . ($hata ? ', ' . $hata . ' atlandı/başarısız' : '') . '.');
    }

    /** Toplu müşteri içe aktarma (Excel/CSV) */
    public function iceAktar(Request $request)
    {
        $request->validate([
            'dosya'       => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'liste_ids'   => 'nullable|array',
            'liste_ids.*' => 'integer|exists:crm_listeler,id',
        ], [], ['dosya' => 'dosya']);

        $import = new \App\Imports\DataCenterMusteriImport($request->input('liste_ids', []));

        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('dosya'));
        } catch (\Throwable $e) {
            return back()->with('error', 'İçe aktarma başarısız: ' . $e->getMessage());
        }

        $msg = $import->eklenen . ' müşteri içe aktarıldı';
        if ($import->atlanan) $msg .= ', ' . $import->atlanan . ' satır atlandı (adı boş)';
        return back()->with('success', $msg . '.');
    }

    // ════════════════════════════════════════════════════════════
    // DATA CENTER — LİSTELER (Çoka Çok segment yönetimi)
    // ════════════════════════════════════════════════════════════

    /** Listeler yönetim sayfası */
    public function listeler()
    {
        $listeler = \App\Models\CRM\CrmListe::withCount('musteriler')
            ->orderBy('sira')->orderBy('ad')->get();

        return view('admin.data-center.listeler', compact('listeler'));
    }

    /** Yeni liste oluştur */
    public function listeKaydet(Request $request)
    {
        $data = $request->validate([
            'ad'       => 'required|string|max:150',
            'aciklama' => 'nullable|string|max:500',
            'renk'     => 'nullable|string|max:20',
        ]);
        $data['durum'] = 1;
        $data['olusturan_id'] = session('admin_id');
        \App\Models\CRM\CrmListe::create($data);

        return back()->with('success', '"' . $data['ad'] . '" listesi oluşturuldu.');
    }

    /** Liste adı/açıklama güncelle */
    public function listeGuncelle(Request $request, $id)
    {
        $liste = \App\Models\CRM\CrmListe::findOrFail($id);
        $liste->update($request->validate([
            'ad'       => 'required|string|max:150',
            'aciklama' => 'nullable|string|max:500',
            'renk'     => 'nullable|string|max:20',
        ]));

        return back()->with('success', 'Liste güncellendi.');
    }

    /** Liste sil — ilişki kopar, müşteriler korunur (model deleting kancası temizler) */
    public function listeSil($id)
    {
        $liste = \App\Models\CRM\CrmListe::findOrFail($id);
        $ad = $liste->ad;
        $liste->delete();

        return back()->with('success', '"' . $ad . '" listesi silindi (müşteriler korundu).');
    }

    /** Seçili müşterileri bir listeye TOPLU ata */
    public function listeyeAta(Request $request)
    {
        $data = $request->validate([
            'liste_id'       => 'required|integer|exists:crm_listeler,id',
            'musteri_ids'    => 'required|array|min:1',
            'musteri_ids.*'  => 'integer',
        ]);
        $liste = \App\Models\CRM\CrmListe::findOrFail($data['liste_id']);
        $pivot = [];
        foreach ($data['musteri_ids'] as $cid) {
            $pivot[$cid] = ['kaynak' => 'manuel'];
        }
        $liste->musteriler()->syncWithoutDetaching($pivot);

        return back()->with('success', count($data['musteri_ids']) . ' müşteri "' . $liste->ad . '" listesine eklendi.');
    }
}