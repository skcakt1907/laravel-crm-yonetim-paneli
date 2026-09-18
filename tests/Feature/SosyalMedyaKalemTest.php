<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SosyalMedyaPlanController;
use App\Http\Controllers\Admin\SosyalMedyaTakipController;
use App\Models\CRM\SosyalMedyaPaylasim;
use App\Models\CRM\SosyalMedyaPlan;
use App\Models\CRM\SosyalMedyaGunNotu;
use App\Models\CRM\SosyalMedyaPlanIstisna;
use App\Models\CRM\SosyalMedyaPlanKalem;
use App\Services\SosyalMedyaTakip;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Tarihe özel isimli paylaşımlar (checklist).
 *
 * Haftalık şablon yalnızca SAYI tutuyordu; bu özellik o paylaşımların
 * ADINI da tutuyor ve uzman ekranını sayaçtan checklist'e çeviriyor.
 */
class SosyalMedyaKalemTest extends TestCase
{
    use DatabaseTransactions;
    use SosyalMedyaTestVerisi;

    private SosyalMedyaTakip $takip;
    private Carbon $gun;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
        $this->takip = app(SosyalMedyaTakip::class);
        $this->gun   = Carbon::today();
        $this->olarakGir($this->patron());
    }

    private function planC(): SosyalMedyaPlanController
    {
        return app(SosyalMedyaPlanController::class);
    }

    private function takipC(): SosyalMedyaTakipController
    {
        return app(SosyalMedyaTakipController::class);
    }

    private function kalemEkle(int $hesapId, string $basliklar)
    {
        return $this->planC()->kalemEkle(Request::create('/x', 'POST', [
            'hesap_id'  => $hesapId,
            'tarih'     => $this->gun->toDateString(),
            'basliklar' => $basliklar,
        ]));
    }

    /** Çok satırlı giriş tek tek kalemlere bölünür, boş satırlar atlanır */
    public function test_cok_satirli_giris_ayri_kalemlere_bolunur(): void
    {
        $h = $this->hesap($this->marka('Kalem Testi'), 'Instagram');

        $this->kalemEkle($h->id, "Reels: klinik tanıtım\nStory: hasta yorumu\n\nPost: kampanya");

        $this->assertSame(3, SosyalMedyaPlanKalem::where('hesap_id', $h->id)->count(),
            'boş satır kalem olarak sayılmamalı');
    }

    /**
     * KALEM ŞABLONU EZER.
     *
     * Şablonda 5 yazsa bile o güne 2 kalem girildiyse hedef 2 olur —
     * isim girilen gün, o isimler neyse hedef odur.
     */
    public function test_kalem_haftalik_sablonu_ezer(): void
    {
        $h = $this->hesap($this->marka('Ezme Testi'), 'Instagram',
            [$this->gun->dayOfWeekIso => 5]);

        $this->assertSame(5, $this->takip->hedef($h->id, $this->gun));

        $this->kalemEkle($h->id, "Birinci post\nİkinci post");

        $this->assertSame(2, $this->takip->hedef($h->id, $this->gun),
            'kalem girilince hedef kalem sayısına eşitlenmeli');
    }

    /** Kalem, tarihe özel istisnayı da ezer (öncelik en üstte) */
    public function test_kalem_istisnayi_da_ezer(): void
    {
        $h = $this->hesap($this->marka('Oncelik Testi'), 'Instagram',
            [$this->gun->dayOfWeekIso => 5]);

        SosyalMedyaPlanIstisna::create([
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
            'hedef_adet' => 9, 'sebep' => 'kampanya',
        ]);
        $this->assertSame(9, $this->takip->hedef($h->id, $this->gun));

        $this->kalemEkle($h->id, "Tek post");

        $this->assertSame(1, $this->takip->hedef($h->id, $this->gun));
    }

    /** Aynı gün aynı başlık iki kez girilemez (tabloda unique var, 500 dönmemeli) */
    public function test_ayni_baslik_ikinci_kez_eklenmez(): void
    {
        $h = $this->hesap($this->marka('Tekrar Testi'), 'Instagram');

        $this->kalemEkle($h->id, 'Aynı post');
        $cevap = $this->kalemEkle($h->id, 'Aynı post');

        $this->assertSame(1, SosyalMedyaPlanKalem::where('hesap_id', $h->id)->count());
        $this->assertNotNull($cevap->getSession()->get('error'));
    }

    public function test_kalem_isaretlenince_yapilan_artar(): void
    {
        $h = $this->hesap($this->marka('İşaret Testi'), 'Instagram');
        $this->kalemEkle($h->id, "Birinci\nİkinci");

        $kalem = SosyalMedyaPlanKalem::where('hesap_id', $h->id)->orderBy('sira')->first();

        $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
            'kalem_id' => $kalem->id,
        ]));

        $d = $this->takip->gunDurumu($h->id, $this->gun);

        $this->assertSame(1, $d['yapilan']);
        $this->assertSame(2, $d['hedef']);
        $this->assertSame(1, $d['eksik']);
    }

    /**
     * Aynı kalem iki kez işaretlenemez.
     *
     * Yoksa hedef 2 iken 3 "yapıldı" görünür, oran %150 olurdu.
     */
    public function test_ayni_kalem_iki_kez_isaretlenemez(): void
    {
        $h = $this->hesap($this->marka('Cift Isaret'), 'Instagram');
        $this->kalemEkle($h->id, 'Tek post');
        $kalem = SosyalMedyaPlanKalem::where('hesap_id', $h->id)->first();

        $istek = fn () => Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
            'kalem_id' => $kalem->id,
        ]);

        $this->takipC()->paylasimEkle($istek());
        $cevap = $this->takipC()->paylasimEkle($istek());

        $this->assertNotNull($cevap->getSession()->get('error'));
        $this->assertSame(1, SosyalMedyaPaylasim::where('kalem_id', $kalem->id)->count());
    }

    /**
     * Kalem silinince YAPILMIŞ İŞ SİLİNMEZ.
     *
     * Yalnızca kalem bağı kopar; paylaşım kaydı (link, saat, kim işaretledi)
     * durur. Aksi hâlde plan düzeltmesi geçmiş kaydı yok ederdi.
     */
    public function test_kalem_silinince_paylasim_kaydi_durur(): void
    {
        $h = $this->hesap($this->marka('Silme Testi'), 'Instagram');
        $this->kalemEkle($h->id, 'Silinecek post');
        $kalem = SosyalMedyaPlanKalem::where('hesap_id', $h->id)->first();

        $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
            'kalem_id' => $kalem->id, 'link' => 'https://instagram.com/p/abc',
        ]));

        $this->planC()->kalemSil($kalem->id);

        $paylasim = SosyalMedyaPaylasim::where('hesap_id', $h->id)->first();

        $this->assertNotNull($paylasim, 'paylaşım kaydı silinmemeli');
        $this->assertNull($paylasim->kalem_id, 'kalem bağı kopmalı');
        $this->assertSame('https://instagram.com/p/abc', $paylasim->link);
    }

    /** Gün sonu raporu eksik kalemlerin ADINI da vermeli */
    public function test_rapor_eksik_kalem_adlarini_verir(): void
    {
        $marka = $this->marka('Rapor Kalem');
        $h     = $this->hesap($marka, 'Instagram');
        $this->kalemEkle($h->id, "Yapılan post\nUnutulan post");

        $yapilan = SosyalMedyaPlanKalem::where('hesap_id', $h->id)
            ->where('baslik', 'Yapılan post')->first();

        $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
            'kalem_id' => $yapilan->id,
        ]));

        $v     = $this->takip->gunSonuVerisi($this->gun);
        $satir = collect($v['eksik'])->firstWhere('marka', 'Rapor Kalem');

        $this->assertNotNull($satir);
        $this->assertSame(['Unutulan post'], $satir['eksik_kalemler']);
        $this->assertSame(['Yapılan post'], $satir['yapilan_kalemler']);
    }

    /** Kalem girilen gün takip ekranı checklist gösterir, serbest buton gizlenir */
    public function test_takip_ekrani_checklist_gosterir(): void
    {
        $h = $this->hesap($this->marka('ZZ Ekran Kalem'), 'Instagram');
        $this->kalemEkle($h->id, 'Ekranda görünecek post');

        $html = $this->takipC()->index(Request::create('/x'))->render();

        // Yalnızca bu markanın kartına bak — sayfada başka hesaplar da var
        $bas  = strpos($html, 'ZZ Ekran Kalem');
        $kart = substr($html, $bas, 4000);

        $this->assertStringContainsString('sm-checklist', $kart);
        $this->assertStringContainsString('Ekranda görünecek post', $kart);
        $this->assertStringNotContainsString('class="sm-form"', $kart,
            'kalem varken serbest "Yapıldı" formu gösterilmemeli — yoksa hedeften fazla işaretlenebilir');
    }

    /** Kalem yoksa eski davranış bozulmamalı: sayaç + serbest işaretleme */
    public function test_kalem_yoksa_eski_davranis_korunur(): void
    {
        $h = $this->hesap($this->marka('Eski Davranis'), 'Instagram',
            [$this->gun->dayOfWeekIso => 2]);

        $this->assertSame(2, $this->takip->hedef($h->id, $this->gun));

        $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
        ]));

        $d = $this->takip->gunDurumu($h->id, $this->gun);

        $this->assertSame(1, $d['yapilan']);
        $this->assertCount(0, $d['kalemler']);
    }

    /* ─────────── TAKVİM ─────────── */

    /** Takvim ucu kalemleri FullCalendar biçiminde döndürür */
    public function test_takvim_kalemleri_olay_olarak_dondurur(): void
    {
        $h = $this->hesap($this->marka('ZZ Takvim Testi'), 'Instagram');
        $this->kalemEkle($h->id, "Birinci post
İkinci post");

        $kalem = SosyalMedyaPlanKalem::where('hesap_id', $h->id)->orderBy('sira')->first();
        $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
            'kalem_id' => $kalem->id,
        ]));

        $json = $this->planC()->etkinlikler(Request::create('/x', 'GET', [
            'start' => $this->gun->copy()->startOfMonth()->toDateString(),
            'end'   => $this->gun->copy()->endOfMonth()->toDateString(),
        ]))->getData(true);

        $bizim = collect($json)->filter(fn ($e) => str_contains($e['title'], 'ZZ Takvim Testi'))->values();

        $this->assertCount(2, $bizim);

        $yapilan = $bizim->firstWhere('extendedProps.yapildi', true);
        $bekleyen = $bizim->firstWhere('extendedProps.yapildi', false);

        $this->assertNotNull($yapilan, 'işaretlenen kalem yapıldı olarak dönmeli');
        $this->assertStringStartsWith('✓', $yapilan['title'], 'yapılanın başında onay işareti olmalı');
        $this->assertNotNull($bekleyen);
        $this->assertStringNotContainsString('✓', $bekleyen['title']);

        // Tıklama hedefi olarak gün bilgisi taşınmalı
        $this->assertSame($this->gun->toDateString(), $yapilan['extendedProps']['tarih']);
    }

    /** Takvim yalnızca istenen ay aralığını döndürür */
    public function test_takvim_yalnizca_istenen_araligi_dondurur(): void
    {
        $h = $this->hesap($this->marka('ZZ Aralik Testi'), 'Instagram');

        $this->planC()->kalemEkle(Request::create('/x', 'POST', [
            'hesap_id'  => $h->id,
            'tarih'     => $this->gun->copy()->addMonths(2)->toDateString(),
            'basliklar' => 'Uzak aydaki post',
        ]));

        $json = $this->planC()->etkinlikler(Request::create('/x', 'GET', [
            'start' => $this->gun->copy()->startOfMonth()->toDateString(),
            'end'   => $this->gun->copy()->endOfMonth()->toDateString(),
        ]))->getData(true);

        $this->assertCount(0,
            collect($json)->filter(fn ($e) => str_contains($e['title'], 'ZZ Aralik Testi'))->all(),
            'aralık dışındaki kalem takvime girmemeli');
    }

    /**
     * TAKIP EKRANINDA SILME BUTONU BULUNMALI.
     *
     * Plan ekrani takvime cevrilirken kalem listesi (ve icindeki silme
     * butonlari) kaldirilmisti; rota ve controller duruyordu ama hicbir
     * ekran onu cagirmiyordu -- kullanici planlanmis paylasimi silemedi.
     * Bu test o gerilemenin tekrarini yakalar.
     */
    public function test_takip_ekraninda_kalem_silme_butonu_var(): void
    {
        $h = $this->hesap($this->marka('ZZ Silme Butonu'), 'Instagram');
        $this->kalemEkle($h->id, 'Silinebilir post');

        $html = $this->takipC()->index(Request::create('/x'))->render();
        $bas  = strpos($html, 'ZZ Silme Butonu');
        $kart = substr($html, $bas, 6000);

        $kalem = SosyalMedyaPlanKalem::where('hesap_id', $h->id)->first();

        $this->assertStringContainsString(
            route('admin.crm.sosyal-medya-takip.kalem.sil', $kalem->id),
            $kart,
            'checklist kaleminde "plandan kaldir" butonu olmali');
    }

    /** Silinen kalem ekrana geri gelmemeli */
    public function test_silinen_kalem_ekranda_gorunmez(): void
    {
        $h = $this->hesap($this->marka('ZZ Geri Gelme'), 'Instagram');
        $this->kalemEkle($h->id, "Gidecek post
Kalacak post");

        $sil = SosyalMedyaPlanKalem::where('hesap_id', $h->id)
            ->where('baslik', 'Gidecek post')->first();
        $this->planC()->kalemSil($sil->id);

        $html = $this->takipC()->index(Request::create('/x'))->render();
        $bas  = strpos($html, 'ZZ Geri Gelme');
        $kart = substr($html, $bas, 6000);

        $this->assertStringNotContainsString('Gidecek post', $kart);
        $this->assertStringContainsString('Kalacak post', $kart);
        $this->assertSame(1, $this->takip->hedef($h->id, $this->gun));
    }

    /* ─────────── GÜNDEN KALDIR ─────────── */

    /**
     * Platform o günün listesinden çıkar, HAFTALIK PLAN BOZULMAZ.
     *
     * Bu ayrım önemli: kullanıcı "bugün bunu istemiyorum" derken gelecek
     * haftaların planını da silmek istemiyor.
     */
    public function test_gunden_kaldirinca_haftalik_plan_bozulmaz(): void
    {
        $h = $this->hesap($this->marka('ZZ Günden Kaldır'), 'Facebook',
            [$this->gun->dayOfWeekIso => 1]);

        $this->assertSame(1, $this->takip->hedef($h->id, $this->gun));

        $this->takipC()->gundenKaldir(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
        ]));

        $this->assertSame(0, $this->takip->hedef($h->id, $this->gun),
            'o gün listeden çıkmalı');
        $this->assertSame(1, $this->takip->hedef($h->id, $this->gun->copy()->addWeek()),
            'haftaya aynı gün etkilenmemeli');
    }

    /** Kaldırılan platform o günün ekranında görünmez */
    public function test_gunden_kaldirilan_platform_ekranda_gorunmez(): void
    {
        $h = $this->hesap($this->marka('ZZ Ekrandan Çık'), 'Facebook',
            [$this->gun->dayOfWeekIso => 1]);

        $this->takipC()->gundenKaldir(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
        ]));

        $html = $this->takipC()->index(Request::create('/x'))->render();

        $this->assertStringNotContainsString('ZZ Ekrandan Çık', $html);
    }

    /** Erteleme/iptal notu da temizlenmeli — platform gidince not anlamsız */
    public function test_gunden_kaldirinca_gun_notu_da_silinir(): void
    {
        $h = $this->hesap($this->marka('ZZ Not Temizle'), 'Facebook',
            [$this->gun->dayOfWeekIso => 1]);

        $this->takipC()->gunNotu(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
            'tip' => 'iptal', 'sebep' => 'deneme',
        ]));
        $this->assertSame(1, SosyalMedyaGunNotu::where('hesap_id', $h->id)->count());

        $this->takipC()->gundenKaldir(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
        ]));

        $this->assertSame(0, SosyalMedyaGunNotu::where('hesap_id', $h->id)->count());
    }

    /**
     * YAPILMIŞ İŞ VARSA ENGELLENİR.
     *
     * Tamamlanmış paylaşımı olan bir günü listeden çıkarmak, yapılan işi
     * raporlarda görünmez kılardı.
     */
    public function test_isaretli_paylasim_varken_gunden_kaldirilamaz(): void
    {
        $h = $this->hesap($this->marka('ZZ Isaretli'), 'Facebook',
            [$this->gun->dayOfWeekIso => 1]);

        $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
        ]));

        $cevap = $this->takipC()->gundenKaldir(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $this->gun->toDateString(),
        ]));

        $this->assertNotNull($cevap->getSession()->get('error'));
        $this->assertSame(1, $this->takip->hedef($h->id, $this->gun),
            'engellenince hedef değişmemeli');
    }
}
