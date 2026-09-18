<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SosyalMedyaRaporController;
use App\Models\CRM\SosyalMedyaGunNotu;
use App\Models\CRM\SosyalMedyaPaylasim;
use App\Models\CRM\SosyalMedyaPlanIstisna;
use App\Services\SosyalMedyaTakip;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Aylık rapor — toplu hesaplama.
 *
 * Buradaki testlerin yarısı PERFORMANSI koruyor. aylikVeri() bilerek toplu
 * yazıldı; biri onu "daha okunaklı" diye gün gün gunDurumu() çağıracak
 * şekilde değiştirirse sorgu sayısı testi bunu yakalar.
 */
class SosyalMedyaAylikTest extends TestCase
{
    use DatabaseTransactions;
    use SosyalMedyaTestVerisi;

    private SosyalMedyaTakip $takip;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
        $this->takip = app(SosyalMedyaTakip::class);
        $this->olarakGir($this->patron());
    }

    /** Bu ayın 1'i ile bugün arası — rapor buraya bakıyor */
    private function buAy(): Carbon
    {
        return Carbon::today()->startOfMonth();
    }

    private function satir(array $veri, string $platform): ?array
    {
        return collect($veri['satirlar'])
            ->first(fn ($s) => $s['hesap']->platform === $platform
                            && str_starts_with($s['marka'], 'Aylık'));
    }

    public function test_hedef_ve_yapilan_toplanir(): void
    {
        $marka  = $this->marka('Aylık Toplam');
        $hedef  = array_fill_keys(range(1, 7), 1);          // her gün 1
        $h      = $this->hesap($marka, 'Instagram', $hedef);

        $gunSay = 0;
        for ($t = $this->buAy(); $t->lte(Carbon::today()); $t->addDay()) {
            SosyalMedyaPaylasim::create([
                'hesap_id' => $h->id, 'tarih' => $t->toDateString(),
                'isaretlendi_at' => $t->copy()->setTime(10, 0),
            ]);
            $gunSay++;
        }

        $s = $this->satir($this->takip->aylikVeri($this->buAy()), 'Instagram');

        $this->assertSame($gunSay, $s['hedef']);
        $this->assertSame($gunSay, $s['yapilan']);
        $this->assertSame(100, $s['oran']);
        $this->assertSame(0, $s['eksik_gun']);
    }

    /**
     * Ay ortasında rapor açıldığında kalan günler "eksik" sayılmamalı;
     * yoksa her rapor ayın sonuna kadar kırmızı görünür ve kimse bakmaz.
     */
    public function test_gelecek_gunler_hedefe_eklenmez(): void
    {
        $marka = $this->marka('Aylık Gelecek');
        $h     = $this->hesap($marka, 'LinkedIn', array_fill_keys(range(1, 7), 1));

        $veri = $this->takip->aylikVeri($this->buAy());
        $s    = $this->satir($veri, 'LinkedIn');

        $this->assertSame(Carbon::today()->toDateString(), $veri['bitis']->toDateString());
        $this->assertSame(Carbon::today()->day, $s['hedef'],
            'hedef yalnızca bugüne kadarki günleri kapsamalı');
    }

    public function test_tatil_istisnasi_hedeften_dusulur(): void
    {
        $marka = $this->marka('Aylık Tatil');
        $h     = $this->hesap($marka, 'Facebook', array_fill_keys(range(1, 7), 1));

        $tatil = $this->buAy();   // ayın 1'i
        SosyalMedyaPlanIstisna::create([
            'hesap_id' => $h->id, 'tarih' => $tatil->toDateString(),
            'hedef_adet' => 0, 'sebep' => 'tatil',
        ]);

        $s = $this->satir($this->takip->aylikVeri($this->buAy()), 'Facebook');

        $this->assertSame(Carbon::today()->day - 1, $s['hedef']);
        $this->assertSame('plansiz', $s['gunler'][$tatil->toDateString()]);
    }

    public function test_ertelenen_gun_eksik_sayilmaz(): void
    {
        $marka = $this->marka('Aylık Erteleme');
        $h     = $this->hesap($marka, 'TikTok', array_fill_keys(range(1, 7), 1));

        $gun = $this->buAy();
        SosyalMedyaGunNotu::create([
            'hesap_id' => $h->id, 'tarih' => $gun->toDateString(),
            'tip' => SosyalMedyaGunNotu::TIP_ERTELENDI,
            'ertelendi_tarih' => Carbon::today()->toDateString(), 'sebep' => 'onay yok',
        ]);

        $s = $this->satir($this->takip->aylikVeri($this->buAy()), 'TikTok');

        $this->assertSame(1, $s['ertelenen']);
        $this->assertSame('ertelendi', $s['gunler'][$gun->toDateString()]);
    }

    public function test_plansiz_hesap_rapora_girmez(): void
    {
        $marka = $this->marka('Aylık Plansız');
        $this->hesap($marka, 'X (Twitter)');   // hiç plan yok

        $this->assertNull($this->satir($this->takip->aylikVeri($this->buAy()), 'X (Twitter)'));
    }

    /**
     * SORGU SAYISI SABİT OLMALI.
     *
     * Hesap ve gün sayısından bağımsız. Gün gün gunDurumu() çağrılsaydı
     * canlıdaki ~40 hesap × 30 gün = 3600 sorgu ederdi ve sayfa açılmazdı.
     */
    public function test_sorgu_sayisi_hesap_sayisindan_bagimsizdir(): void
    {
        $marka = $this->marka('Aylık Sorgu');
        foreach (['Instagram', 'LinkedIn', 'Facebook', 'TikTok', 'YouTube'] as $p) {
            $this->hesap($marka, $p, array_fill_keys(range(1, 7), 1));
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->takip->aylikVeri($this->buAy());
        $adet = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(10, $adet,
            "aylikVeri() {$adet} sorgu attı; toplu okuma bozulmuş olabilir");
    }

    /* ─────────── EKRAN ─────────── */

    public function test_bozuk_ay_parametresi_sayfayi_kirmaz(): void
    {
        $html = app(SosyalMedyaRaporController::class)
            ->aylik(Request::create('/x', 'GET', ['ay' => 'saçmalık']), $this->takip)
            ->render();

        $this->assertStringContainsString($this->buAy()->translatedFormat('F Y'), $html);
    }

    public function test_gelecek_ay_istenirse_bu_aya_dusurulur(): void
    {
        $ileri = $this->buAy()->copy()->addMonths(6)->format('Y-m');

        $html = app(SosyalMedyaRaporController::class)
            ->aylik(Request::create('/x', 'GET', ['ay' => $ileri]), $this->takip)
            ->render();

        $this->assertStringContainsString($this->buAy()->translatedFormat('F Y'), $html);
    }
}
