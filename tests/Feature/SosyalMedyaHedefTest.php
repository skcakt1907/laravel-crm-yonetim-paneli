<?php

namespace Tests\Feature;

use App\Models\CRM\SosyalMedyaGunNotu;
use App\Models\CRM\SosyalMedyaPaylasim;
use App\Models\CRM\SosyalMedyaPlanIstisna;
use App\Services\SosyalMedyaTakip;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Çekirdek hedef/durum mantığı.
 *
 * Modüldeki en kritik kod burası: hem panel, hem gün sonu maili, hem aylık
 * rapor bu hesaba dayanıyor. Bir hata üçünü birden yanlış gösterir.
 */
class SosyalMedyaHedefTest extends TestCase
{
    use DatabaseTransactions;
    use SosyalMedyaTestVerisi;

    private SosyalMedyaTakip $takip;
    private Carbon $persembe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->takip = app(SosyalMedyaTakip::class);

        // Sabit bir perşembe: dayOfWeekIso = 4. Testin bugünün gününe
        // göre değişmemesi için tarih sabitlendi.
        $this->persembe = Carbon::parse('2026-10-01');
        $this->assertSame(4, $this->persembe->dayOfWeekIso);
    }

    public function test_plani_olmayan_gunun_hedefi_sifirdir(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [1 => 3]);   // yalnız pazartesi

        $this->assertSame(0, $this->takip->hedef($h->id, $this->persembe));
    }

    public function test_haftalik_sablon_okunur(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [4 => 3]);

        $this->assertSame(3, $this->takip->hedef($h->id, $this->persembe));
    }

    public function test_istisna_sablonu_ezer(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [4 => 3]);

        SosyalMedyaPlanIstisna::create([
            'hesap_id' => $h->id, 'tarih' => $this->persembe->toDateString(),
            'hedef_adet' => 7, 'sebep' => 'kampanya',
        ]);

        $this->assertSame(7, $this->takip->hedef($h->id, $this->persembe));
    }

    /**
     * MODÜLDEKİ EN İNCE NOKTA.
     *
     * hedef_adet = 0 olan istisna "o gün tatil, paylaşım yok" demek ve
     * haftalık şablonu EZMELİ. Kod "istisna var mı" kontrolünü truthy ile
     * yapsaydı 0 "yok" sayılır, şablondaki 3 geçerli olur ve tatil günü
     * herkes eksik görünürdü.
     */
    public function test_sifir_hedefli_istisna_tatil_demektir(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [4 => 3]);

        SosyalMedyaPlanIstisna::create([
            'hesap_id' => $h->id, 'tarih' => $this->persembe->toDateString(),
            'hedef_adet' => 0, 'sebep' => '29 Ekim',
        ]);

        $this->assertSame(0, $this->takip->hedef($h->id, $this->persembe),
            'hedef_adet=0 istisna şablonu ezmeli — yoksa tatil günü eksik görünür');
    }

    public function test_eksik_hesaplanir(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [4 => 3]);
        $this->paylasimEkle($h->id, $this->persembe);

        $d = $this->takip->gunDurumu($h->id, $this->persembe);

        $this->assertSame(3, $d['hedef']);
        $this->assertSame(1, $d['yapilan']);
        $this->assertSame(2, $d['eksik']);
        $this->assertFalse($d['tamam']);
    }

    public function test_hedeften_fazla_paylasim_eksik_uretmez(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [4 => 1]);
        $this->paylasimEkle($h->id, $this->persembe);
        $this->paylasimEkle($h->id, $this->persembe);

        $d = $this->takip->gunDurumu($h->id, $this->persembe);

        $this->assertSame(2, $d['yapilan']);
        $this->assertSame(0, $d['eksik'], 'fazla paylaşım negatif eksik üretmemeli');
        $this->assertTrue($d['tamam']);
    }

    /** Ertelenen/iptal edilen gün EKSİK SAYILMAZ — meşru bir karar, hata değil */
    public function test_ertelenen_gun_eksik_sayilmaz(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [4 => 3]);

        SosyalMedyaGunNotu::create([
            'hesap_id' => $h->id, 'tarih' => $this->persembe->toDateString(),
            'tip' => SosyalMedyaGunNotu::TIP_ERTELENDI,
            'ertelendi_tarih' => $this->persembe->copy()->addDays(2)->toDateString(),
            'sebep' => 'görsel onayı gelmedi',
        ]);

        $d = $this->takip->gunDurumu($h->id, $this->persembe);

        $this->assertSame(0, $d['eksik']);
        $this->assertNotNull($d['not']);
    }

    public function test_gunun_hesaplari_plansizlari_atlar(): void
    {
        $marka   = $this->marka();
        $planli  = $this->hesap($marka, 'Instagram', [4 => 2]);
        $plansiz = $this->hesap($marka, 'TikTok');

        $idler = $this->takip->gununHesaplari($this->persembe)->pluck('id');

        $this->assertTrue($idler->contains($planli->id));
        $this->assertFalse($idler->contains($plansiz->id),
            'planı olmayan hesap günlük listede görünmemeli');
    }

    public function test_geriye_isaretleme_siniri(): void
    {
        $bugun = Carbon::today();

        $this->assertTrue($this->takip->isaretlenebilirMi($bugun));
        $this->assertTrue($this->takip->isaretlenebilirMi(
            $bugun->copy()->subDays(SosyalMedyaTakip::GERIYE_GUN)));
        $this->assertFalse($this->takip->isaretlenebilirMi(
            $bugun->copy()->subDays(SosyalMedyaTakip::GERIYE_GUN + 1)));
        $this->assertFalse($this->takip->isaretlenebilirMi($bugun->copy()->addDay()),
            'geleceğe işaretleme yapılamamalı');
    }

    /** Günde birden fazla paylaşım olabilir — bu yüzden tabloda unique YOK */
    public function test_ayni_gune_birden_fazla_paylasim_eklenebilir(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [4 => 3]);

        $this->paylasimEkle($h->id, $this->persembe);
        $this->paylasimEkle($h->id, $this->persembe);
        $this->paylasimEkle($h->id, $this->persembe);

        $this->assertSame(3, $this->takip->gunDurumu($h->id, $this->persembe)['yapilan']);
    }

    private function paylasimEkle(int $hesapId, Carbon $tarih): void
    {
        SosyalMedyaPaylasim::create([
            'hesap_id' => $hesapId,
            'tarih' => $tarih->toDateString(),
            'isaretlendi_at' => $tarih->copy()->setTime(10, 0),
        ]);
    }
}
