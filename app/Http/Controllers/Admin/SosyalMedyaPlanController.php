<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\SosyalMedyaHesap;
use App\Models\CRM\SosyalMedyaKayit;
use App\Models\CRM\SosyalMedyaPlan;
use App\Models\CRM\SosyalMedyaPlanIstisna;
use App\Models\CRM\SosyalMedyaPlanKalem;
use App\Models\Yonetici;
use App\Support\SosyalMedyaYetki;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * SOSYAL MEDYA TAKİP — plan yönetimi
 *
 * Günlük işaretlemeden AYRI controller: burası "hangi gün kaç paylaşım"
 * ve "kim sorumlu" kurgusudur, işaretleme değil. İkisi farklı kullanıcı,
 * farklı sıklık ve farklı yetki isteyen işler.
 *
 * İKİ KATMAN:
 *   Şablon  (sosyal_medya_planlar)         normal haftanın düzeni
 *   İstisna (sosyal_medya_plan_istisnalar) belirli tarihi ezer
 * Tatiller de istisna olarak girilir (hedef 0) — ayrı mekanizma yok.
 */
class SosyalMedyaPlanController extends Controller
{
    /** Plan ekranı kendi yetkisiyle korunur — takip ekranından ayrı verilebilir */
    private const SAYFA = 'admin.crm.sosyal-medya-takip.plan';

    public function index(Request $request)
    {
        $this->yetkiKontrol();

        // Marka > platform sırasıyla; plan satırları gun => hedef haritası olarak
        $hesaplar = SosyalMedyaHesap::with(['kayit', 'sorumlu'])
            ->where('bolum', SosyalMedyaHesap::BOLUM_SOSYAL)   // web/cPanel kayitlari plana girmez
            ->get()
            ->sortBy(fn ($h) => ($h->kayit->baslik ?? $h->kayit->firma ?? 'zzz') . '|' . $h->platform)
            ->values();

        $planlar = SosyalMedyaPlan::all()
            ->groupBy('hesap_id')
            ->map(fn ($g) => $g->pluck('hedef_adet', 'gun')->all());

        // İstisnalar: bugünden itibaren ileriye + son 7 gün (geçmiş bağlam için)
        $istisnalar = SosyalMedyaPlanIstisna::with('hesap.kayit')
            ->whereDate('tarih', '>=', Carbon::today()->subDays(7))
            ->orderBy('tarih')
            ->get();

        // Sorumlu olarak atanabilecekler: bayi ve müşteri dışındaki aktif
        // yöneticiler. Sabit rol listesi yok — canlıda Sosyal Medya (20),
        // Muhasebe (5), Yazılımcı (21) gibi roller de var.
        $uzmanlar = Yonetici::where('durum', 1)
            ->whereNotIn('rol', [Yonetici::ROL_BAYI, Yonetici::ROL_MUSTERI])
            ->orderBy('adi')
            ->get(['id', 'adi', 'kullaniciadi', 'email']);

        return view('admin.sosyal-medya.plan', [
            'hesaplar'   => $hesaplar,
            'planlar'    => $planlar,
            'istisnalar' => $istisnalar,
            'uzmanlar'   => $uzmanlar,
            'gunler'     => SosyalMedyaPlan::GUNLER,
            'markalar'   => SosyalMedyaKayit::orderBy('baslik')->get(['id', 'baslik']),
            // Kalemler: dunden itibaren ileriye -- gecmis gun listeyi sisirir
            'kalemler'   => SosyalMedyaPlanKalem::with(['hesap.kayit', 'paylasim'])
                ->whereDate('tarih', '>=', Carbon::today()->subDay())
                ->orderBy('tarih')->orderBy('sira')->get()
                ->groupBy(fn ($k) => $k->tarih->toDateString()),
            'platformlar'=> SosyalMedyaHesap::PLATFORMLAR[SosyalMedyaHesap::BOLUM_SOSYAL],
            'raporAlicilari' => \App\Services\SosyalMedyaRaporu::seciliIdler(),
            'raporSaati'     => \App\Services\SosyalMedyaRaporu::raporSaati(),
        ]);
    }

    /**
     * Yeni marka/platform ekler — Şifre Kasası'na gitmeden, doğrudan bu
     * ekrandan. Kasa ile AYNI tabloya yazar (sosyal_medya_kayitlari +
     * sosyal_medya_hesaplari, bolum=sosyal_medya); burası sadece kasadaki
     * karışık forma (şifre/kullanıcı adı alanları) gitmeden kısayol sağlıyor.
     *
     * Marka: var olan bir kayıt seçilebilir YA DA yeni ad girilip anında
     * oluşturulabilir — ikisi de aynı POST'ta, "marka_id" boşsa "marka_adi"
     * kullanılır.
     */
    public function hesapEkle(Request $request)
    {
        $this->yetkiKontrol();

        $veri = $request->validate([
            'marka_id'   => 'nullable|integer|exists:sosyal_medya_kayitlari,id',
            'marka_adi'  => 'nullable|string|max:191|required_without:marka_id',
            'platform'      => 'required|string|max:100',
            'kullanici_adi' => 'nullable|string|max:255',
            'link'          => 'nullable|string|max:255',
            'sorumlu_id'    => 'nullable|integer|exists:yoneticiler,id',
        ], [
            'marka_adi.required_without' => 'Var olan bir marka seçin ya da yeni marka adı girin.',
        ]);

        if (! empty($veri['marka_id'])) {
            $kayit = SosyalMedyaKayit::findOrFail($veri['marka_id']);
        } else {
            $kayit = SosyalMedyaKayit::create([
                'baslik'        => $veri['marka_adi'],
                'olusturan_id'  => session('admin_id'),
            ]);
        }

        $sira = (int) (SosyalMedyaHesap::where('kayit_id', $kayit->id)->max('sira') ?? 0) + 1;

        SosyalMedyaHesap::create([
            'kayit_id'      => $kayit->id,
            'bolum'         => SosyalMedyaHesap::BOLUM_SOSYAL,
            'platform'      => $veri['platform'],
            'kullanici_adi' => $veri['kullanici_adi'] ?? null,
            'link'          => $veri['link'] ?? null,
            'sorumlu_id'    => $veri['sorumlu_id'] ?? null,
            'sira'          => $sira,
        ]);

        return back()->with('success', $kayit->baslik . ' · ' . $veri['platform'] . ' eklendi. Şimdi hedef günlerini gir.');
    }

    /**
     * Izgarayı topluca kaydeder.
     *
     * hedef[hesap_id][gun] = adet   ·   sorumlu[hesap_id] = yonetici_id
     *
     * Hedef 0 veya boşsa plan satırı SİLİNİR — "o gün paylaşım yok" demek
     * satırı 0 ile tutmaktan daha temiz; hedef() zaten satır yoksa 0 döner.
     */
    public function kaydet(Request $request)
    {
        $this->yetkiKontrol();

        $veri = $request->validate([
            'hedef'          => 'nullable|array',
            'hedef.*.*'      => 'nullable|integer|min:0|max:50',
            'sorumlu'        => 'nullable|array',
            'sorumlu.*'      => 'nullable|integer|exists:yoneticiler,id',
        ]);

        $degisen = 0;

        foreach ($veri['hedef'] ?? [] as $hesapId => $gunler) {
            foreach ($gunler as $gun => $adet) {
                $adet = (int) $adet;

                if ($adet > 0) {
                    SosyalMedyaPlan::updateOrCreate(
                        ['hesap_id' => (int) $hesapId, 'gun' => (int) $gun],
                        ['hedef_adet' => $adet, 'aktif' => true]
                    );
                } else {
                    SosyalMedyaPlan::where('hesap_id', (int) $hesapId)
                        ->where('gun', (int) $gun)->delete();
                }
                $degisen++;
            }
        }

        foreach ($veri['sorumlu'] ?? [] as $hesapId => $sorumluId) {
            SosyalMedyaHesap::where('id', (int) $hesapId)
                ->update(['sorumlu_id' => $sorumluId ?: null]);
        }

        return back()->with('success', 'Plan kaydedildi.');
    }

    /**
     * Gün sonu raporunun gideceği kişileri kaydeder.
     *
     * Hiç kimse seçilmezse tablo boşaltılır ve sistem koddaki varsayılan
     * listeye döner (bkz. SosyalMedyaRaporu::alicilar). Böylece rapor
     * yanlışlıkla tamamen susturulmuş olmaz.
     */
    public function raporAlicilari(Request $request)
    {
        $this->yetkiKontrol();

        $veri = $request->validate([
            'alicilar'    => 'nullable|array',
            'alicilar.*'  => 'integer|exists:yoneticiler,id',
            'rapor_saati' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ], [
            'rapor_saati.regex' => 'Saat 00:00 – 23:59 arasında olmalı.',
        ]);

        $secilen = array_values(array_unique($veri['alicilar'] ?? []));

        DB::table('sosyal_medya_rapor_alicilari')->delete();

        foreach ($secilen as $id) {
            DB::table('sosyal_medya_rapor_alicilari')->insert([
                'yonetici_id' => (int) $id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Saat: bos gelirse varsayilana doner (ayar satiri silinir)
        if (! empty($veri['rapor_saati'])) {
            DB::table('sosyal_medya_ayarlar')->updateOrInsert(
                ['anahtar' => 'rapor_saati'],
                ['deger' => $veri['rapor_saati'], 'updated_at' => now(), 'created_at' => now()]
            );
        } else {
            DB::table('sosyal_medya_ayarlar')->where('anahtar', 'rapor_saati')->delete();
        }

        $saat = \App\Services\SosyalMedyaRaporu::raporSaati();

        return back()->with('success', (empty($secilen)
            ? 'Alıcı seçimi temizlendi — rapor varsayılan listeye gidecek.'
            : count($secilen) . ' kişi rapor alıcısı olarak kaydedildi.')
            . ' Gönderim saati: ' . $saat . '.');
    }

    /** Tarihe özel hedef — şablonu ezer. Tatil için 0 girilir. */
    public function istisnaEkle(Request $request)
    {
        $this->yetkiKontrol();

        $veri = $request->validate([
            'hesap_id'   => 'required|integer|exists:sosyal_medya_hesaplari,id',
            'tarih'      => 'required|date',
            'hedef_adet' => 'required|integer|min:0|max:50',
            'sebep'      => 'nullable|string|max:255',
        ], [
            'hedef_adet.required' => 'Hedef paylaşım sayısını girin (tatil için 0).',
        ]);

        SosyalMedyaPlanIstisna::updateOrCreate(
            ['hesap_id' => $veri['hesap_id'], 'tarih' => Carbon::parse($veri['tarih'])->toDateString()],
            [
                'hedef_adet'   => $veri['hedef_adet'],
                'sebep'        => $veri['sebep'] ?? null,
                'olusturan_id' => session('admin_id'),
            ]
        );

        return back()->with('success', 'İstisna kaydedildi.');
    }

    /**
     * Takvim verisi (FullCalendar JSON).
     *
     * Panelin kendi takvim ekranıyla aynı kütüphane kullanılıyor; olay
     * biçimi de aynı: {title, start, ...}. Yapılmış paylaşımlar yeşil,
     * bekleyenler nötr renkte döner — aya bakınca hangi günün aksadığı
     * tek bakışta görünsün diye.
     */
    public function etkinlikler(Request $request)
    {
        $this->yetkiKontrol();

        $bas = $request->filled('start')
            ? Carbon::parse($request->string('start'))->toDateString()
            : Carbon::today()->startOfMonth()->toDateString();

        $son = $request->filled('end')
            ? Carbon::parse($request->string('end'))->toDateString()
            : Carbon::today()->endOfMonth()->toDateString();

        $kalemler = SosyalMedyaPlanKalem::with(['hesap.kayit', 'paylasim'])
            ->whereBetween('tarih', [$bas, $son])
            ->orderBy('sira')->get();

        $olaylar = [];

        foreach ($kalemler as $k) {
            $yapildi = $k->paylasim !== null;
            $marka   = $k->hesap->kayit->baslik ?? $k->hesap->kayit->firma ?? '—';

            $olaylar[] = [
                'id'              => $k->id,
                'title'           => ($yapildi ? '✓ ' : '') . $marka . ' · ' . $k->baslik,
                'start'           => $k->tarih->toDateString(),
                'allDay'          => true,
                'backgroundColor' => $yapildi ? '#E2EFDA' : '#EEF2F7',
                'borderColor'     => $yapildi ? '#1E6B2F' : '#94a3b8',
                'textColor'       => $yapildi ? '#1E6B2F' : '#334155',
                'extendedProps'   => [
                    'tarih'    => $k->tarih->toDateString(),
                    'platform' => $k->hesap->platform,
                    'yapildi'  => $yapildi,
                ],
            ];
        }

        return response()->json($olaylar);
    }

    /**
     * Tarihe özel paylaşım kalemi ekler — "Reels: klinik tanıtım".
     *
     * Birden fazla satır tek seferde girilebilsin diye başlıklar SATIR SATIR
     * kabul ediliyor: uzman genelde o günün 3 postunu bir arada yazıyor,
     * üç kez form doldurtmak gereksiz.
     */
    public function kalemEkle(Request $request)
    {
        $this->yetkiKontrol();

        $veri = $request->validate([
            'hesap_id'  => 'required|integer|exists:sosyal_medya_hesaplari,id',
            'tarih'     => 'required|date',
            'basliklar' => 'required|string|max:4000',
        ], [
            'basliklar.required' => 'En az bir paylaşım adı yazın.',
        ]);

        $tarih = Carbon::parse($veri['tarih'])->toDateString();

        $sira = (int) (SosyalMedyaPlanKalem::where('hesap_id', $veri['hesap_id'])
            ->whereDate('tarih', $tarih)->max('sira') ?? 0);

        $eklenen = 0;
        $atlanan = 0;

        foreach (preg_split('/\R/u', $veri['basliklar']) as $baslik) {
            $baslik = trim($baslik);

            if ($baslik === '') {
                continue;
            }

            // Aynı gün aynı başlık zaten varsa sessizce atla — tabloda unique
            // var, yakalanmazsa 500 dönerdi.
            $varMi = SosyalMedyaPlanKalem::where('hesap_id', $veri['hesap_id'])
                ->whereDate('tarih', $tarih)->where('baslik', $baslik)->exists();

            if ($varMi) {
                $atlanan++;
                continue;
            }

            SosyalMedyaPlanKalem::create([
                'hesap_id'     => $veri['hesap_id'],
                'tarih'        => $tarih,
                'baslik'       => mb_substr($baslik, 0, 255),
                'sira'         => ++$sira,
                'olusturan_id' => session('admin_id'),
            ]);
            $eklenen++;
        }

        if ($eklenen === 0 && $atlanan > 0) {
            return back()->with('error', 'Bu başlıklar o gün için zaten girilmiş.');
        }

        return back()->with('success', $eklenen . ' paylaşım eklendi.'
            . ($atlanan > 0 ? ' ' . $atlanan . ' tanesi zaten vardı, atlandı.' : ''));
    }

    /**
     * Kalemi siler. İşaretlenmiş paylaşımı varsa paylaşım DURUR, yalnızca
     * kalem bağı kopar — yapılmış bir işi silmek istemeyiz.
     */
    public function kalemSil(int $id)
    {
        $this->yetkiKontrol();

        $kalem = SosyalMedyaPlanKalem::findOrFail($id);

        \App\Models\CRM\SosyalMedyaPaylasim::where('kalem_id', $kalem->id)
            ->update(['kalem_id' => null]);

        $kalem->delete();

        return back()->with('success', 'Paylaşım kalemi silindi.');
    }

    public function istisnaSil(int $id)
    {
        $this->yetkiKontrol();

        SosyalMedyaPlanIstisna::findOrFail($id)->delete();

        return back()->with('success', 'İstisna kaldırıldı, o gün şablona döndü.');
    }

    // ─────────────────────────────────────────────────────────────

    private function yetkiKontrol(): void
    {
        SosyalMedyaYetki::zorunlu(self::SAYFA);
    }
}
