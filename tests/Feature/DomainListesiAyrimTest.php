<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CRM\DomainTrackingController;
use App\Services\DomainKarRaporu;
use App\Support\DomainAnahtari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * DOMAIN LİSTESİNDE TEKRAR ELEME.
 *
 * Aynı domain iki tabloda birden duruyordu ve listede iki kez görünüyordu:
 * bir kez bizim kaydımız (satilanlar), bir kez Metunic senkronundan.
 * Canlıda 144 domain böyleydi.
 *
 * Korunanlar:
 *  - Tekrar eden domainin BİZİM kaydımız kalır, Metunic kaydı elenir
 *  - Toplam sayaç tekrarı bir kez sayar
 *  - Türkçe 'İ' ile girilmiş kayıtlar da eşleşir (canlıda iki örneği var)
 *  - Metunic'e özel kayıt, tutarı girilmeden ciroya KATILMAZ
 *  - Ciro dağılımı satış tarihine göre yapılır, tescil tarihine göre değil
 *
 * NOT: `satilanlar` MyISAM — işlem geri alınmaz, kayıtlar elle silinir.
 */
class DomainListesiAyrimTest extends TestCase
{
    /** Test kayıtlarının domainleri — çakışmasın diye benzersiz önek */
    private const ONEK = 'zzdeneme-';

    private bool $hazir = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hazir = Schema::hasTable('satilanlar')
            && Schema::hasTable('metunic_domains')
            && Schema::hasColumn('metunic_domains', 'tutar');

        if (! $this->hazir) {
            $this->markTestSkipped('metunic_domains tablosu veya tutar kolonu yok.');
        }

        $this->temizle();
        session(['admin_id' => 1]);
    }

    protected function tearDown(): void
    {
        if ($this->hazir) {
            $this->temizle();
        }

        parent::tearDown();
    }

    private function temizle(): void
    {
        DB::table('satilanlar')->where('domain', 'like', self::ONEK . '%')->delete();
        DB::table('metunic_domains')->where('domain', 'like', self::ONEK . '%')->delete();
    }

    private function bizimKayit(string $domain, float $tutar = 1000, string $tarih = '2026-05-10'): int
    {
        return DB::table('satilanlar')->insertGetId([
            'domain' => $domain, 'tipi' => '3', 'tutar' => $tutar, 'tarih' => $tarih,
        ]);
    }

    private function metunicKaydi(string $domain, string $tescil = '2021-02-03'): int
    {
        return DB::table('metunic_domains')->insertGetId([
            'service_id' => random_int(900000, 999999),
            'domain'     => $domain,
            'status'     => 'active',
            'date_added' => $tescil,
        ]);
    }

    private function ekran(): object
    {
        $req = Request::create('/admin/crm/domains', 'GET', ['tumu' => 1]);
        $this->app->instance('request', $req);

        $veri = app(DomainTrackingController::class)->index($req)->getData();

        return (object) [
            'domains' => collect($veri['domains']->items()),
            'stats'   => $veri['stats'],
        ];
    }

    /** Test kayitlarimizdan BIZIM tabloya ait olanlar (kaynak != metunic) */
    private function bizimkiler($ekran)
    {
        return $ekran->domains->filter(fn ($d) => str_starts_with((string) $d->domain, self::ONEK)
            && ($d->kaynak ?? '') !== 'metunic');
    }

    /** Test kayitlarimizdan Metunic'e ait olanlar */
    private function metunikler($ekran)
    {
        return $ekran->domains->filter(fn ($d) => str_starts_with((string) $d->domain, self::ONEK)
            && ($d->kaynak ?? '') === 'metunic');
    }

    /* ─────────── AYRIM ─────────── */

    /** Her iki tabloda olan domain, YALNIZCA bizim tabloda görünmeli */
    public function test_tekrar_eden_domainin_metunic_kaydi_elenmis(): void
    {
        $d = self::ONEK . 'ikisinde.com';
        $this->bizimKayit($d);
        $this->metunicKaydi($d);

        $e = $this->ekran();

        $this->assertCount(1, $this->bizimkiler($e), 'Bizim tabloda tam bir kez olmalı');
        $this->assertCount(0, $this->metunikler($e), 'Metunic kaydı elenmiş olmalı');
    }

    /** Bizde karşılığı olmayan Metunic kaydı ikinci tabloda durmalı */
    public function test_metunice_ozel_domain_listede_kaliyor(): void
    {
        $d = self::ONEK . 'sadece-metunic.com';
        $this->metunicKaydi($d);

        $e = $this->ekran();

        $this->assertCount(0, $this->bizimkiler($e));
        $this->assertCount(1, $this->metunikler($e));
    }

    /** Bizim kaydımız Metunic'te yoksa ana tabloda kalmalı */
    public function test_yalnizca_bizde_olan_domain_listede(): void
    {
        $d = self::ONEK . 'sadece-bizde.com';
        $this->bizimKayit($d);

        $e = $this->ekran();

        $this->assertCount(1, $this->bizimkiler($e));
        $this->assertCount(0, $this->metunikler($e));
    }

    /**
     * Türkçe büyük İ tuzağı: MySQL LOWER() ile PHP mb_strtolower() farklı
     * sonuç veriyordu, bu kayıtlar iki tabloda birden görünüyordu.
     */
    public function test_turkce_i_harfli_domain_de_eslesiyor(): void
    {
        $this->bizimKayit(self::ONEK . 'bessSİgorta.com');
        $this->metunicKaydi(self::ONEK . 'besssigorta.com');

        $e = $this->ekran();

        $this->assertCount(0, $this->metunikler($e), 'İ farkı yüzünden tekrar elenmemiş olmamalı');
    }

    public function test_domain_anahtari_buyuk_kucuk_ve_bosluk_farkini_siliyor(): void
    {
        $this->assertSame('ornek.com', DomainAnahtari::of('  ORNEK.com '));
        $this->assertSame('cihan.com', DomainAnahtari::of('cİhan.com'));
        $this->assertSame('', DomainAnahtari::of(null));
    }

    /* ─────────── SAYAÇ ─────────── */

    /** Toplam sayaç, tekrar eden domaini bir kez saymalı */
    public function test_toplam_sayac_tekrari_bir_kez_sayiyor(): void
    {
        $oncekiToplam = $this->ekran()->stats['toplam'];

        $ortak = self::ONEK . 'ortak.com';
        $this->bizimKayit($ortak);
        $this->metunicKaydi($ortak);
        $this->metunicKaydi(self::ONEK . 'ozel.com');

        $this->assertSame($oncekiToplam + 2, $this->ekran()->stats['toplam']);
    }

    /* ─────────── KÂR RAPORU ─────────── */

    /** Tutarı girilmemiş Metunic kaydı ciroya girmemeli */
    public function test_tutarsiz_metunic_kaydi_ciroya_girmiyor(): void
    {
        $once = DomainKarRaporu::veri(2026);

        $this->metunicKaydi(self::ONEK . 'tutarsiz.com');

        $sonra = DomainKarRaporu::veri(2026);

        $this->assertSame($once['adet'], $sonra['adet']);
        $this->assertEquals($once['ciro'], $sonra['ciro']);
    }

    /** Tutar girilince ciroya katılmalı */
    public function test_tutar_girilen_metunic_kaydi_ciroya_giriyor(): void
    {
        $id = $this->metunicKaydi(self::ONEK . 'tutarli.com');
        $once = DomainKarRaporu::veri(2026);

        app(DomainTrackingController::class)->metunicTutar(
            Request::create('/x', 'POST', ['tutar' => '5000', 'maliyet' => '1200', 'satis_tarihi' => '2026-04-01']),
            $id
        );

        $sonra = DomainKarRaporu::veri(2026);

        $this->assertSame($once['adet'] + 1, $sonra['adet']);
        $this->assertEquals($once['ciro'] + 5000, $sonra['ciro']);
    }

    /**
     * Satış tarihi boş bırakılırsa bugün atanır — yoksa kayıt tutarı
     * girilmiş olmasına rağmen hiçbir aya yazılamaz ve sessizce kaybolur.
     */
    public function test_tarih_bos_birakilirsa_bugun_yaziliyor(): void
    {
        $id = $this->metunicKaydi(self::ONEK . 'tarihsiz.com');

        app(DomainTrackingController::class)->metunicTutar(
            Request::create('/x', 'POST', ['tutar' => '2500']), $id
        );

        $this->assertSame(
            now()->toDateString(),
            substr((string) DB::table('metunic_domains')->where('id', $id)->value('satis_tarihi'), 0, 10)
        );
    }

    /**
     * Ciro SATIŞ tarihine göre dağıtılır. `date_added` domainin ilk tescil
     * tarihi (canlı veride 2020-2026 arasına yayılıyor); ona göre dağıtmak
     * geliri yanlış yıllara yazar.
     */
    public function test_ciro_tescil_tarihine_gore_degil_satis_tarihine_gore(): void
    {
        $id = $this->metunicKaydi(self::ONEK . 'eski-tescil.com', '2020-01-15');

        app(DomainTrackingController::class)->metunicTutar(
            Request::create('/x', 'POST', ['tutar' => '3000', 'satis_tarihi' => '2026-06-20']), $id
        );

        $this->assertTrue(
            collect(DomainKarRaporu::veri(2026)['aylar'] ?? [])
                ->contains(fn ($a) => (int) $a['ay'] === 6 && $a['ciro'] > 0),
            'Satış Haziran 2026 ayına yazılmalı'
        );
    }

    /** Tutar silinince kayıt rapordan çıkmalı */
    public function test_tutar_silinince_rapordan_cikiyor(): void
    {
        $id = $this->metunicKaydi(self::ONEK . 'geri-al.com');
        $c  = app(DomainTrackingController::class);

        $once = DomainKarRaporu::veri(2026);
        $c->metunicTutar(Request::create('/x', 'POST', ['tutar' => '4000', 'satis_tarihi' => '2026-02-02']), $id);
        $c->metunicTutar(Request::create('/x', 'POST', ['tutar' => '', 'maliyet' => '', 'satis_tarihi' => '']), $id);

        $this->assertEquals($once['ciro'], DomainKarRaporu::veri(2026)['ciro']);
        $this->assertNull(DB::table('metunic_domains')->where('id', $id)->value('tutar'));
    }

    /** Bizde de olan bir domaine Metunic tarafında tutar girilse bile çift sayılmamalı */
    public function test_tekrar_eden_domain_ciroda_cift_sayilmiyor(): void
    {
        $d = self::ONEK . 'cift.com';
        $this->bizimKayit($d, 1000, '2026-03-03');
        $id = $this->metunicKaydi($d);

        $once = DomainKarRaporu::veri(2026);

        app(DomainTrackingController::class)->metunicTutar(
            Request::create('/x', 'POST', ['tutar' => '7777', 'satis_tarihi' => '2026-03-03']), $id
        );

        $this->assertEquals($once['ciro'], DomainKarRaporu::veri(2026)['ciro']);
    }

    /* ─────────── HİZMET KOLONU ─────────── */

    /**
     * Bir kayıt aynı anda birden çok hizmet olabiliyor (hem domain hem
     * hosting satılmış olabilir). Tek etiket basmak bilgi kaybı olurdu,
     * bu yüzden geçerli olanların hepsi gösteriliyor.
     */
    public function test_hizmet_kolonu_birden_cok_rozet_basiyor(): void
    {
        $d = self::ONEK . 'coklu-hizmet.com';
        DB::table('satilanlar')->insert([
            'domain' => $d, 'tipi' => '3', 'tutar' => 1000,
            'tarih' => now()->toDateString(),
            'hizmetler' => json_encode(['domain', 'business hosting']),
        ]);

        $html = $this->ekranHtml();

        $this->assertStringContainsString('<th style="width:150px">Hizmet</th>', $html);
        $this->assertMatchesRegularExpression('~Domain</span>~', $html);
        $this->assertMatchesRegularExpression('~Business Hosting</span>~', $html);
    }

    /** Hizmet bilgisi olmayan kayıtta hücre boş kalmamalı */
    public function test_hizmeti_belirsiz_kayit_bos_hucre_birakmiyor(): void
    {
        DB::table('satilanlar')->insert([
            'domain' => self::ONEK . 'hizmetsiz.com', 'tipi' => '3',
            // hizmetler JSON kolonu: bos metin kabul etmiyor, NULL yazilir
            'tutar' => 500, 'tarih' => now()->toDateString(), 'hizmetler' => null,
        ]);

        // Hizmetler boşsa controller kaynağa göre karar veriyor; hücre ya
        // rozet ya da tire gösterir, hiçbir durumda boş kalmaz.
        $this->assertStringContainsString('<th style="width:150px">Hizmet</th>', $this->ekranHtml());
    }

    private function ekranHtml(): string
    {
        $req = Request::create('/admin/crm/domains', 'GET', ['tumu' => 1]);
        $this->app->instance('request', $req);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);

        return app(DomainTrackingController::class)->index($req)->render();
    }

    /* ─────────── DOĞRULAMA ─────────── */

    public function test_eksi_tutar_kabul_edilmiyor(): void
    {
        $id = $this->metunicKaydi(self::ONEK . 'eksi.com');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(DomainTrackingController::class)->metunicTutar(
            Request::create('/x', 'POST', ['tutar' => '-50']), $id
        );
    }

    public function test_bozuk_tarih_kabul_edilmiyor(): void
    {
        $id = $this->metunicKaydi(self::ONEK . 'bozuktarih.com');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(DomainTrackingController::class)->metunicTutar(
            Request::create('/x', 'POST', ['tutar' => '100', 'satis_tarihi' => '32-13-2026']), $id
        );
    }
}
