<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmailNotificationService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TopluMesajController extends Controller
{
    /**
     * Ana sayfa — Tab'lı UI (Mail, SMS, Reklam, Şablonlar, Geçmiş)
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'mail');

        // Şablonlar
        $sablonlar = collect();
        if (Schema::hasTable('mesaj_sablonlari')) {
            $sablonlar = DB::table('mesaj_sablonlari')
                ->orderBy('tip')
                ->orderByDesc('kullanim_sayisi')
                ->orderByDesc('id')
                ->get();
        }

        // Geçmiş gönderimler (son 30)
        $gecmis = collect();
        if (Schema::hasTable('toplu_mesaj_log')) {
            $gecmis = DB::table('toplu_mesaj_log')
                ->leftJoin('yoneticiler', 'toplu_mesaj_log.gonderen_admin_id', '=', 'yoneticiler.id')
                ->select('toplu_mesaj_log.*', 'yoneticiler.adi as gonderen_adi')
                ->orderByDesc('toplu_mesaj_log.id')
                ->limit(30)
                ->get();
        }

        // Alıcı istatistikleri (üyeler)
        $istatistik = [
            'tum_aktif'   => DB::table('uyeler')->where('durum', 1)->whereNotNull('email')->count(),
            'tum_telefon' => DB::table('uyeler')->where('durum', 1)->whereNotNull('telefon')->count(),
            'son_30_gun'  => DB::table('uyeler')->where('durum', 1)
                ->where('ktarih', '>=', now()->subDays(30))->count(),
            'toplam_kayit'=> DB::table('uyeler')->count(),
            'reklam_izin' => DB::table('uyeler')->where('durum', 1)
                ->where('kampanya_mail_izin', 1)->whereNotNull('email')->count(),
            'crm_toplam'  => Schema::hasTable('crm_customers') ? DB::table('crm_customers')->count() : 0,
        ];

        // CRM sektör seçenekleri — crm_customers'taki dolu distinct değerler
        $crmSektorler = collect();
        if (Schema::hasTable('crm_customers')) {
            $crmSektorler = DB::table('crm_customers')->whereNotNull('sektor')->where('sektor', '!=', '')
                ->distinct()->orderBy('sektor')->pluck('sektor');
        }

        // Türkiye il listesi (kademeli dropdown — ilçe AJAX ile gelir)
        $trIller = Schema::hasTable('tr_iller')
            ? DB::table('tr_iller')->orderBy('ad')->get(['id', 'ad'])
            : collect();

        // Manuel seçim için tüm kişi listeleri (filtreli çoklu seçim kutusu)
        $tmKisiler = [
            'uye' => DB::table('uyeler')->where('durum', 1)
                ->orderBy('ad')
                ->get(['id', 'ad', 'soyad', 'email', 'telefon'])
                ->map(fn ($u) => [
                    'id'  => $u->id,
                    'ad'  => trim(($u->ad ?? '') . ' ' . ($u->soyad ?? '')) ?: ($u->email ?? ('#' . $u->id)),
                    'alt' => trim(($u->email ?? '') . ' ' . ($u->telefon ?? '')),
                ])->values(),
            'crm' => Schema::hasTable('crm_customers')
                ? DB::table('crm_customers')->orderBy('adi')
                    ->get(['id', 'adi', 'email', 'telefon', 'gsm'])
                    ->map(fn ($c) => [
                        'id'  => $c->id,
                        'ad'  => $c->adi ?: ('#' . $c->id),
                        'alt' => trim(($c->email ?? '') . ' ' . ($c->gsm ?: ($c->telefon ?? ''))),
                    ])->values()
                : collect(),
        ];

        return view('admin.toplu-mesaj.index', compact(
            'tab', 'sablonlar', 'gecmis', 'istatistik',
            'crmSektorler', 'trIller', 'tmKisiler'
        ));
    }


    /**
     * Mail gönder (toplu) — üye veya CRM müşteri kitlesine
     */
    public function sendMail(Request $request)
    {
        return $this->gonder($request, 'mail');
    }

    /**
     * SMS gönder (toplu)
     */
    public function sendSms(Request $request)
    {
        return $this->gonder($request, 'sms');
    }

    /**
     * Reklam / Kampanya gönder — sadece kampanya izni olan üyelere (mail)
     */
    public function sendReklam(Request $request)
    {
        return $this->gonder($request, 'reklam');
    }


    /**
     * Ortak gönderim motoru. $kanal: mail | sms | reklam
     */
    protected function gonder(Request $request, string $kanal)
    {
        $smsMi = ($kanal === 'sms');

        $rules = [
            'kitle'        => 'required|in:uye,crm',
            'hedef'        => 'required|string',
            'manuel_ids'   => 'nullable|array',
            'manuel_ids.*' => 'integer',
            'filtre_durum' => 'nullable|integer',
            'crm_il'       => 'nullable|string|max:100',
            'crm_ilce'     => 'nullable|string|max:100',
            'crm_sektor'   => 'nullable|string|max:100',
            'mesaj'        => 'required|string' . ($smsMi ? '|max:1000' : ''),
            'sablon_kaydet'=> 'nullable|boolean',
            'sablon_adi'   => 'nullable|string|max:255',
            'sablon_kategori' => 'nullable|string|max:100',
        ];
        if (!$smsMi) {
            $rules['konu'] = 'required|string|max:255';
        }
        $validated = $request->validate($rules);

        @set_time_limit(0); // büyük listelerde zaman aşımını önle

        $kitle = $validated['kitle'];

        // Alıcıları getir (kanal mail/reklam -> email zorunlu, sms -> telefon)
        $alicilar = $this->aliciGetir($kitle, $validated, $smsMi ? 'telefon' : 'email', $kanal);

        if ($alicilar->isEmpty()) {
            return back()->with('error', 'Seçilen kriterlere uygun alıcı bulunamadı.')->withInput();
        }

        $toplam = $alicilar->count();
        $basarili = 0; $basarisiz = 0; $atlanan = 0; $sonHata = null;

        if ($smsMi) {
            $smsService = new SmsService();
            foreach ($alicilar as $u) {
                $tel = preg_replace('/[^0-9]/', '', $u->telefon ?? '');
                if (strlen($tel) < 10) { $atlanan++; continue; }
                try {
                    $mesaj = $this->placeholders($validated['mesaj'], $u);
                    $result = $smsService->send($tel, $mesaj);
                    if (is_array($result) && !empty($result['success'])) {
                        $basarili++;
                    } else {
                        $basarisiz++;
                        if (is_array($result)) $sonHata = $result['message'] ?? $result['error'] ?? 'SMS hatası';
                    }
                } catch (\Throwable $e) {
                    $basarisiz++; $sonHata = $e->getMessage();
                    \Log::warning('TopluMesaj SMS hatası', ['err' => $e->getMessage()]);
                }
            }
        } else {
            /*
             * TESLİM EDİLEBİLİRLİK (12.08.2026)
             *
             * $tanitim=true: maile List-Unsubscribe başlığı eklenir. Gmail ve
             * Outlook toplu gönderimde bunu zorunlu tutuyor; olmadan mailler
             * spam'e düşüyor ve gönderen adresin itibarı bozuluyor (aynı
             * adresten çıkan doğrulama kodları da spam'e düşmeye başlıyor).
             *
             * BEKLEME: Gmail SMTP kişiden-kişiye gönderim için tasarlandı.
             * Yüzlerce maili art arda basmak hız sınırını tetikliyor ve
             * gönderim itibarına zarar veriyor. Mailler arasına kısa bir
             * duraklama koyarak "insan hızında" gönderiyoruz.
             */
            $tanitimMi = in_array($kanal, ['reklam', 'mail'], true);
            $ilk = true;

            foreach ($alicilar as $u) {
                if (empty($u->email)) { $atlanan++; continue; }
                if (!$ilk) { usleep(400000); }   // 0.4 sn — ~150 mail/dk
                $ilk = false;

                try {
                    $konu = $this->placeholders($validated['konu'], $u);
                    $body = $this->placeholders($validated['mesaj'], $u);
                    $html = $this->htmlWrap($body, $u, $kanal === 'reklam');
                    EmailNotificationService::send($u->email, $konu, $html, true, null, $tanitimMi);
                    $basarili++;
                } catch (\Throwable $e) {
                    $basarisiz++; $sonHata = $e->getMessage();
                    \Log::warning('TopluMesaj mail hatası', ['email' => $u->email, 'err' => $e->getMessage()]);
                }
            }
        }

        // Log
        if (Schema::hasTable('toplu_mesaj_log')) {
            DB::table('toplu_mesaj_log')->insert([
                'tip'         => $kanal,
                'baslik'      => $validated['sablon_adi'] ?? mb_substr($validated['konu'] ?? $validated['mesaj'], 0, 100),
                'konu'        => $validated['konu'] ?? null,
                'icerik'      => $validated['mesaj'],
                'hedef_grup'  => $kitle . ':' . $validated['hedef'],
                'hedef_detay' => $this->hedefDetayMetni($validated),
                'toplam_alici'=> $toplam,
                'basarili'    => $basarili,
                'basarisiz'   => $basarisiz,
                'atlanan'     => $atlanan,
                'son_hata'    => $sonHata,
                'gonderen_admin_id' => session('admin_id'),
                'created_at'  => now(),
            ]);
        }

        // Şablon kaydet
        if (!empty($validated['sablon_kaydet']) && !empty($validated['sablon_adi'])) {
            $this->sablonOlustur(
                $kanal,
                $validated['sablon_adi'],
                $validated['konu'] ?? null,
                $validated['mesaj'],
                $validated['sablon_kategori'] ?? null
            );
        }

        $etiket = $smsMi ? 'SMS' : ($kanal === 'reklam' ? 'Reklam' : 'Mail');
        $msg = "{$etiket} gönderimi tamamlandı. Başarılı: {$basarili}, başarısız: {$basarisiz}, atlandı: {$atlanan}";
        if ($basarisiz > 0 && $sonHata) $msg .= " — Son hata: {$sonHata}";

        return redirect()->route('admin.toplu-mesaj.index', ['tab' => $kanal])
            ->with($basarili === 0 ? 'error' : 'success', $msg);
    }


    /**
     * Mail şablonu canlı önizleme (tarayıcıda HTML olarak gösterir)
     */
    public function onizleme(Request $request)
    {
        $reklam = $request->boolean('reklam');
        $mesaj = $request->input('mesaj') ?: "Sayın {{musteri_adi}},\n\nBu, toplu mesaj e-posta şablonunun önizlemesidir. Buraya yazdığınız mesaj bu tasarımın içinde gösterilir.\n\nDeğişkenler ({{ad}}, {{firma_adi}} vb.) gönderimde otomatik doldurulur.\n\nSaygılarımızla.";

        // Örnek alıcı (placeholder'lar için)
        $ornek = (object) ['ad' => 'Ahmet', 'soyad' => 'Yılmaz', 'email' => 'ornek@firma.com', 'telefon' => '05551112233'];
        $mesaj = $this->placeholders($mesaj, $ornek);

        return response($this->htmlWrap($mesaj, $ornek, $reklam))
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Mail/Reklam mesajına eklenecek görseli yükler (AJAX).
     * public/uploads altına DOĞRUDAN kaydeder — storage sembolik linkine
     * bağımlı değil (11.08.2026'da o link canlıda bozuk çıktığı için bilerek
     * bu yol seçildi). Döner: {url: '...'} — bunu {{gorsel:URL}} olarak
     * mesaj kutusuna ekleyen taraf frontend.
     */
    public function gorselYukle(Request $request)
    {
        $request->validate([
            'gorsel' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        $dosya = $request->file('gorsel');
        $klasor = public_path('uploads/toplu-mesaj');
        if (!is_dir($klasor)) {
            mkdir($klasor, 0755, true);
        }

        $ad = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $dosya->guessExtension();
        $dosya->move($klasor, $ad);

        return response()->json([
            'success' => true,
            'url' => asset('uploads/toplu-mesaj/' . $ad),
        ]);
    }

    /**
     * Şablon kaydet (AJAX)
     */
    public function sablonKaydet(Request $request)
    {
        $validated = $request->validate([
            'tip'      => 'required|in:mail,sms,reklam',
            'baslik'   => 'required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'konu'     => 'nullable|string|max:255',
            'icerik'   => 'required|string',
        ]);

        if (!Schema::hasTable('mesaj_sablonlari')) {
            return response()->json(['success' => false, 'message' => 'mesaj_sablonlari tablosu yok.'], 500);
        }

        $id = $this->sablonOlustur(
            $validated['tip'],
            $validated['baslik'],
            $validated['konu'] ?? null,
            $validated['icerik'],
            $validated['kategori'] ?? null
        );

        return response()->json(['success' => true, 'id' => $id, 'message' => 'Şablon kaydedildi.']);
    }


    /**
     * Şablon getir (AJAX)
     */
    public function sablonGetir($id)
    {
        if (!Schema::hasTable('mesaj_sablonlari')) {
            return response()->json(['success' => false], 404);
        }

        $sablon = DB::table('mesaj_sablonlari')->where('id', $id)->first();
        if (!$sablon) {
            return response()->json(['success' => false, 'message' => 'Şablon bulunamadı'], 404);
        }

        DB::table('mesaj_sablonlari')->where('id', $id)->update([
            'kullanim_sayisi' => DB::raw('kullanim_sayisi + 1'),
            'son_kullanim' => now(),
        ]);

        return response()->json(['success' => true, 'sablon' => $sablon]);
    }


    /**
     * Şablon güncelle (AJAX).
     *
     * NOT (11.08.2026): Bu metod EKSİKTİ — route (web.php) tanımlıydı ve
     * arayüz de onu çağırıyordu (Şablonlar sekmesi > kalem ikonu > Kaydet,
     * blade içinde route() yerine url() ile kurulduğu için gözden kaçmış),
     * ama controller'da karşılığı yoktu -> her şablon düzenleme 500 veriyordu.
     * sablonKaydet() ile aynı doğrulama ve tablo-yok kontrolünü kullanır.
     */
    public function sablonGuncelle(Request $request, $id)
    {
        $validated = $request->validate([
            'tip'      => 'required|in:mail,sms,reklam',
            'baslik'   => 'required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'konu'     => 'nullable|string|max:255',
            'icerik'   => 'required|string',
        ]);

        if (!Schema::hasTable('mesaj_sablonlari')) {
            return response()->json(['success' => false, 'message' => 'mesaj_sablonlari tablosu yok.'], 500);
        }

        $sablon = DB::table('mesaj_sablonlari')->where('id', $id)->first();
        if (!$sablon) {
            return response()->json(['success' => false, 'message' => 'Şablon bulunamadı.'], 404);
        }

        $guncel = [
            'tip'      => $validated['tip'],
            'baslik'   => $validated['baslik'],
            'kategori' => $validated['kategori'] ?? null,
            'konu'     => $validated['konu'] ?? null,
            'icerik'   => $validated['icerik'],
        ];
        if (Schema::hasColumn('mesaj_sablonlari', 'updated_at')) {
            $guncel['updated_at'] = now();
        }

        DB::table('mesaj_sablonlari')->where('id', $id)->update($guncel);

        return response()->json(['success' => true, 'id' => (int) $id, 'message' => 'Şablon güncellendi.']);
    }


    /**
     * Şablon sil
     */
    public function sablonSil($id)
    {
        if (!Schema::hasTable('mesaj_sablonlari')) {
            return redirect()->back()->with('error', 'Tablo yok');
        }
        DB::table('mesaj_sablonlari')->where('id', $id)->delete();
        return redirect()->route('admin.toplu-mesaj.index', ['tab' => 'sablonlar'])
            ->with('success', 'Şablon silindi.');
    }


    /**
     * Alıcı listesi (AJAX, manuel kişi seçimi için) — üye veya CRM
     */
    public function alicilar(Request $request)
    {
        $q = trim($request->query('q', ''));
        $kitle = $request->query('kitle', 'uye');

        if ($kitle === 'crm' && Schema::hasTable('crm_customers')) {
            $query = DB::table('crm_customers')
                ->select('id', 'adi', 'email', 'telefon', 'il', 'ilce', 'sektor');
            if ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('adi', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('telefon', 'like', "%{$q}%");
                });
            }
            $sonuclar = $query->orderBy('adi')->limit(50)->get()->map(function ($c) {
                return [
                    'id' => $c->id,
                    'ad' => $c->adi,
                    'soyad' => '',
                    'email' => $c->email,
                    'telefon' => $c->telefon,
                    'extra' => trim(($c->il ?? '') . ($c->ilce ? ' / ' . $c->ilce : '') . ($c->sektor ? ' · ' . $c->sektor : ''), ' '),
                ];
            });
            return response()->json(['success' => true, 'alicilar' => $sonuclar]);
        }

        // Üyeler
        $query = DB::table('uyeler')->select('id', 'ad', 'soyad', 'email', 'telefon', 'firmaadi', 'durum')
            ->where('durum', 1);
        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('ad', 'like', "%{$q}%")
                  ->orWhere('soyad', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('telefon', 'like', "%{$q}%")
                  ->orWhere('firmaadi', 'like', "%{$q}%");
            });
        }
        $sonuclar = $query->orderBy('ad')->limit(50)->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'ad' => $u->ad,
                'soyad' => $u->soyad,
                'email' => $u->email,
                'telefon' => $u->telefon,
                'extra' => $u->firmaadi ?? '',
            ];
        });
        return response()->json(['success' => true, 'alicilar' => $sonuclar]);
    }


    // ════════════════════════════════════════════════════════════
    // YARDIMCI METOTLAR
    // ════════════════════════════════════════════════════════════

    /**
     * Kitle + hedef + filtrelere göre normalize edilmiş alıcı koleksiyonu döndür.
     * Her alıcı: ad, soyad, email, telefon (+ firma) içerir.
     * $zorunluKanal: 'email' | 'telefon' — o alanı dolu olanları getirir.
     */
    protected function aliciGetir(string $kitle, array $data, string $zorunluKanal, string $kanal)
    {
        if ($kitle === 'crm' && Schema::hasTable('crm_customers')) {
            $q = DB::table('crm_customers');

            $hedef = $data['hedef'] ?? 'tum';
            if ($hedef === 'manuel') {
                $q->whereIn('id', $data['manuel_ids'] ?? [-1]);
            } else {
                // filtre: il / ilçe / sektör
                if (!empty($data['crm_il']))     $q->where('il', $data['crm_il']);
                if (!empty($data['crm_ilce']))   $q->where('ilce', $data['crm_ilce']);
                if (!empty($data['crm_sektor'])) $q->where('sektor', $data['crm_sektor']);
            }

            $q->whereNotNull($zorunluKanal)->where($zorunluKanal, '!=', '');

            return $q->get()->map(function ($c) {
                return (object) [
                    'ad' => $c->adi ?? '',
                    'soyad' => '',
                    'email' => $c->email ?? null,
                    'telefon' => $c->telefon ?? null,
                    'firmaadi' => $c->adi ?? '',
                ];
            });
        }

        // ── Üyeler ──
        $q = DB::table('uyeler');
        $hedef = $data['hedef'] ?? 'tum_aktif';

        switch ($hedef) {
            case 'son_30_gun':
                $q->where('durum', 1)->where('ktarih', '>=', now()->subDays(30));
                break;
            case 'manuel':
                $q->whereIn('id', $data['manuel_ids'] ?? [-1]);
                break;
            case 'filtre':
                if (isset($data['filtre_durum'])) $q->where('durum', $data['filtre_durum']);
                // İl/İlçe filtresi — uyeler.sehir (il) ve uyeler.ilce, kolon varsa
                if (!empty($data['crm_il']) && Schema::hasColumn('uyeler', 'sehir')) {
                    $q->where('sehir', $data['crm_il']);
                }
                if (!empty($data['crm_ilce']) && Schema::hasColumn('uyeler', 'ilce')) {
                    $q->where('ilce', $data['crm_ilce']);
                }
                break;
            case 'tum_aktif':
            default:
                $q->where('durum', 1);
                break;
        }

        // Reklam kanalı: sadece kampanya izni olanlar
        if ($kanal === 'reklam') {
            $q->where('kampanya_mail_izin', 1);
        }

        $q->whereNotNull($zorunluKanal)->where($zorunluKanal, '!=', '');

        return $q->get();
    }


    /**
     * Log için okunaklı hedef detay metni
     */
    protected function hedefDetayMetni(array $data): string
    {
        if (($data['kitle'] ?? '') === 'crm') {
            $parts = [];
            if (!empty($data['crm_il']))     $parts[] = 'İl: ' . $data['crm_il'];
            if (!empty($data['crm_ilce']))   $parts[] = 'İlçe: ' . $data['crm_ilce'];
            if (!empty($data['crm_sektor'])) $parts[] = 'Sektör: ' . $data['crm_sektor'];
            if (($data['hedef'] ?? '') === 'manuel') $parts[] = 'Manuel seçim';
            return 'CRM Müşteri' . ($parts ? ' (' . implode(', ', $parts) . ')' : '');
        }
        $uyeParts = [];
        if (!empty($data['crm_il']))   $uyeParts[] = 'İl: ' . $data['crm_il'];
        if (!empty($data['crm_ilce'])) $uyeParts[] = 'İlçe: ' . $data['crm_ilce'];
        return 'Üye (' . ($data['hedef'] ?? 'tum_aktif') . ')' . ($uyeParts ? ' [' . implode(', ', $uyeParts) . ']' : '');
    }


    /**
     * Şablon oluştur (DB)
     */
    protected function sablonOlustur(string $tip, string $baslik, ?string $konu, string $icerik, ?string $kategori = null): int
    {
        if (!Schema::hasTable('mesaj_sablonlari')) return 0;

        return (int) DB::table('mesaj_sablonlari')->insertGetId([
            'tip'            => $tip,
            'baslik'         => $baslik,
            'kategori'       => $kategori,
            'konu'           => $konu,
            'icerik'         => $icerik,
            'olusturan_id'   => session('admin_id'),
            'kullanim_sayisi'=> 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }


    /**
     * Placeholder değiştir
     */
    protected function placeholders(string $text, $kullanici, array $extra = []): string
    {
        $ayarlar = DB::table('ayarlar')->first();
        $tamAd = trim(($kullanici->ad ?? '') . ' ' . ($kullanici->soyad ?? ''));

        $map = [
            '{{ad}}'                    => $kullanici->ad ?? '',
            '{{soyad}}'                 => $kullanici->soyad ?? '',
            '{{tam_ad}}'                => $tamAd,
            '{{musteri_adi}}'           => $tamAd ?: ($kullanici->ad ?? ''),
            '{{email}}'                 => $kullanici->email ?? '',
            '{{telefon}}'               => $kullanici->telefon ?? '',
            '{{kullanici_adi}}'         => $kullanici->kullanici_adi ?? ($kullanici->email ?? ''),
            '{{sifre_sifirlama_link}}'  => \Illuminate\Support\Facades\Route::has('sifre.sifirlama') ? route('sifre.sifirlama') : url('/'),
            '{{firma_adi}}'             => $ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım',
            '{{firma_email}}'           => $ayarlar->firma_email ?? '',
            '{{firma_telefon}}'         => $ayarlar->firma_telefon ?? '',
            '{{site_url}}'              => $ayarlar->site_url ?? url('/'),
            '{{tarih}}'                 => date('d.m.Y'),
            '{{saat}}'                  => date('H:i'),
            '{{yil}}'                   => date('Y'),
        ];

        $map = array_merge($map, $extra);

        return str_replace(array_keys($map), array_values($map), $text);
    }


    /**
     * Mail için modern, markalı HTML wrapper (responsive, e-posta istemcisi uyumlu).
     * $reklam true ise altta tanıtım/izin notu eklenir.
     */
    protected function htmlWrap(string $body, $kullanici, bool $reklam = false): string
    {
        $ayarlar = DB::table('ayarlar')->first();

        $firmaAdi = $ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım';
        $siteUrl  = rtrim($ayarlar->site_url ?? url('/'), '/');
        // Logo: PUBLIC site adresinden (site_url) URL olarak — canlıda görünür.
        // firma_logo şu üç biçimde gelebilir, hepsini doğru çöz:
        //   1) tam URL:            https://.../logo.png        → olduğu gibi kullan
        //   2) site köküne yol:    tema/uploads/logo/logo.png  → site_url + '/' + yol
        //   3) sadece dosya adı:   logo.png                    → site_url + '/tema/uploads/logo/' + ad
        $logoFile = trim((string) ($ayarlar->firma_logo ?? ''));
        if ($logoFile === '') {
            $logoUrl = $siteUrl . '/tema/uploads/favicon/favicon_dn.png';
        } elseif (preg_match('#^https?://#i', $logoFile)) {
            $logoUrl = $logoFile;
        } elseif (strpos($logoFile, '/') !== false) {
            // İçinde yol var (örn. "tema/uploads/logo/x.png") → baştaki / temizle, site_url'e ekle
            $logoUrl = $siteUrl . '/' . ltrim($logoFile, '/');
        } else {
            // Sadece dosya adı → standart logo klasörü
            $logoUrl = $siteUrl . '/tema/uploads/logo/' . $logoFile;
        }

        $telefon = $ayarlar->firma_telefon ?? '';
        $email   = $ayarlar->firma_email ?? '';
        $adres   = $ayarlar->firma_adres ?? '';

        // {{gorsel:URL}} token'larını gerçek <img> etiketine çevir — escape edilmeden ÖNCE
        // geçici bir işaretçiye alınır, metin escape'lendikten SONRA geri konur. Böylece
        // görsel gerçekten render olur ama serbest metin hâlâ HTML injection'a kapalı kalır.
        $gorselHarita = [];
        $gorselIndex = 0;
        $body = preg_replace_callback('/\{\{gorsel:(https?:\/\/[^\s}]+)\}\}/', function ($m) use (&$gorselHarita, &$gorselIndex) {
            $anahtar = "\x01GORSEL{$gorselIndex}\x01";
            $gorselHarita[$anahtar] = '<img src="' . e($m[1]) . '" alt="" style="max-width:100%;height:auto;display:block;margin:14px auto;border-radius:8px">';
            $gorselIndex++;
            return $anahtar;
        }, $body);

        $icerik = nl2br(e($body));
        if ($gorselHarita) {
            $icerik = strtr($icerik, $gorselHarita);
        }

        // Sosyal medya linkleri (dolu olanlar)
        $sosyaller = [];
        foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'twitter' => 'X'] as $alan => $etiket) {
            if (!empty($ayarlar->$alan)) {
                $sosyaller[] = '<a href="' . e($ayarlar->$alan) . '" target="_blank" style="display:inline-block;margin:0 5px;padding:7px 14px;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.25);border-radius:20px;color:#ffffff;text-decoration:none;font-size:12px;font-weight:600">' . $etiket . '</a>';
            }
        }
        if (!empty($ayarlar->whatsapp)) {
            $wa = preg_replace('/[^0-9]/', '', $ayarlar->whatsapp);
            $sosyaller[] = '<a href="https://wa.me/' . $wa . '" target="_blank" style="display:inline-block;margin:0 5px;padding:7px 14px;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.25);border-radius:20px;color:#ffffff;text-decoration:none;font-size:12px;font-weight:600">WhatsApp</a>';
        }
        $sosyalRow = $sosyaller
            ? '<tr><td style="padding:4px 28px 22px;text-align:center;background:linear-gradient(135deg,#1f2937,#111827)">' . implode('', $sosyaller) . '</td></tr>'
            : '';

        // İletişim satırı (telefon / mail / adres)
        $iletisimler = [];
        if ($telefon) $iletisimler[] = '📞 <a href="tel:' . e(preg_replace('/[^0-9+]/', '', $telefon)) . '" style="color:#9ca3af;text-decoration:none">' . e($telefon) . '</a>';
        if ($email)   $iletisimler[] = '✉️ <a href="mailto:' . e($email) . '" style="color:#9ca3af;text-decoration:none">' . e($email) . '</a>';
        $iletisimSatir = $iletisimler ? implode('&nbsp;&nbsp;•&nbsp;&nbsp;', $iletisimler) : '';

        $reklamNot = $reklam
            ? '<tr><td style="padding:12px 28px;background:#fffbeb;border-top:1px solid #fde68a;text-align:center;font-size:11px;line-height:1.6;color:#92400e">Bu bir tanıtım/kampanya iletisidir; izniniz doğrultusunda gönderilmiştir.<br>Bu tür e-postaları almak istemiyorsanız hesap ayarlarınızdan izinleri güncelleyebilirsiniz.</td></tr>'
            : '';

        return '<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light">
<style>
:root { color-scheme: light only; supported-color-schemes: light; }
/* Karanlık modda renkleri sabit tut (otomatik ters çevirmeyi engelle) */
@media (prefers-color-scheme: dark) {
    body, table, td { background-color: inherit !important; }
}
</style>
<title>' . e($firmaAdi) . '</title>
</head>
<body style="margin:0;padding:0;background:#eef0e8;-webkit-text-size-adjust:100%;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef0e8">
<tr><td align="center" style="padding:28px 12px">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(17,24,39,0.08)">

        <!-- ÜST ŞERİT -->
        <tr><td style="height:6px;background:linear-gradient(90deg,#d4d066,#b8b62e,#8a8a1f);font-size:0;line-height:0">&nbsp;</td></tr>

        <!-- LOGO / BAŞLIK -->
        <tr>
            <td align="center" style="padding:32px 28px 22px">
                <img src="' . e($logoUrl) . '" alt="' . e($firmaAdi) . '" height="100" style="height:100px;max-width:420px;display:block;margin:0 auto">
            </td>
        </tr>

        <!-- İÇERİK -->
        <tr>
            <td style="padding:8px 36px 32px;font-size:15px;line-height:1.75;color:#374151">
                ' . $icerik . '
            </td>
        </tr>

        <!-- AYRAÇ -->
        <tr><td style="padding:0 36px"><div style="height:1px;background:#eceedf"></div></td></tr>

        <!-- İLETİŞİM -->
        ' . ($iletisimSatir ? '<tr><td align="center" style="padding:18px 28px 6px;font-size:12px;color:#9ca3af">' . $iletisimSatir . '</td></tr>' : '') . '
        ' . ($adres ? '<tr><td align="center" style="padding:0 28px 18px;font-size:11px;color:#b6bbc4;line-height:1.5">📍 ' . e($adres) . '</td></tr>' : '<tr><td style="height:10px"></td></tr>') . '

        ' . $reklamNot . '

        <!-- SOSYAL + ALT -->
        ' . $sosyalRow . '
        <tr>
            <td align="center" style="padding:16px 28px;background:#111827;color:#6b7280;font-size:11px;line-height:1.6">
                © ' . date('Y') . ' <span style="color:#b8b62e;font-weight:600">' . e($firmaAdi) . '</span><br>
                <a href="' . e($siteUrl) . '" target="_blank" style="color:#9ca3af;text-decoration:none">' . e(preg_replace('#^https?://#', '', $siteUrl)) . '</a>
            </td>
        </tr>

    </table>

    <div style="max-width:600px;margin:14px auto 0;font-size:10px;color:#b6bbc4;text-align:center;line-height:1.5">
        Bu e-posta ' . e($firmaAdi) . ' tarafından gönderilmiştir.
    </div>

</td></tr>
</table>
</body>
</html>';
    }
}