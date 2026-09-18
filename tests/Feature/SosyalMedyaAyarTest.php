<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SosyalMedyaPlanController;
use App\Models\CRM\SosyalMedyaHesap;
use App\Models\CRM\SosyalMedyaKayit;
use App\Models\Yonetici;
use App\Services\SosyalMedyaRaporu;
use App\Services\SosyalMedyaTakip;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * İstek listesinde olup sonradan tamamlanan üç madde:
 *   - rapor alıcılarının panelden seçilebilmesi
 *   - mailin üstünde belirgin "DİKKAT" uyarısı
 *   - hızlı ekleme formunda kullanıcı adı / profil linki
 */
class SosyalMedyaAyarTest extends TestCase
{
    use DatabaseTransactions;
    use SosyalMedyaTestVerisi;

    private Carbon $gun;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
        $this->gun = Carbon::today();
        $this->olarakGir($this->patron());
    }

    protected function tearDown(): void
    {
        // MyISAM degil ama DatabaseTransactions disinda kalmasin diye
        // yine de temizleniyor -- tablo InnoDB oldugu icin rollback zaten
        // yeterli, bu ikinci guvence.
        DB::table('sosyal_medya_rapor_alicilari')->delete();

        parent::tearDown();
    }

    private function planC(): SosyalMedyaPlanController
    {
        return app(SosyalMedyaPlanController::class);
    }

    /* ─────────── 1. RAPOR ALICILARI ─────────── */

    public function test_alicilar_panelden_secilebilir(): void
    {
        $kisiler = Yonetici::where('durum', 1)
            ->whereNotNull('email')->where('email', '!=', '')
            ->whereNotIn('rol', [Yonetici::ROL_BAYI, Yonetici::ROL_MUSTERI])
            ->limit(2)->get();

        if ($kisiler->count() < 2) {
            $this->markTestSkipped('E-postalı en az iki aktif yönetici gerekiyor.');
        }

        $this->planC()->raporAlicilari(Request::create('/x', 'POST', [
            'alicilar' => $kisiler->pluck('id')->all(),
        ]));

        $alicilar = SosyalMedyaRaporu::alicilar();

        $this->assertCount(2, $alicilar);
        foreach ($kisiler as $k) {
            $this->assertContains($k->email, $alicilar);
        }
    }

    /**
     * SEÇİM TEMİZLENİRSE RAPOR SUSMAZ.
     *
     * Boş liste "kimseye gönderme" anlamına gelseydi, yanlışlıkla temizleyen
     * biri raporu sessizce kapatmış olurdu. Bu yüzden boşsa koddaki
     * varsayılan listeye dönülür.
     */
    public function test_secim_temizlenince_varsayilan_listeye_donulur(): void
    {
        $varsayilan = SosyalMedyaRaporu::alicilar();

        $kisi = Yonetici::where('durum', 1)->whereNotNull('email')
            ->where('email', '!=', '')->first();

        $this->planC()->raporAlicilari(Request::create('/x', 'POST', ['alicilar' => [$kisi->id]]));
        $this->assertCount(1, SosyalMedyaRaporu::alicilar());

        $this->planC()->raporAlicilari(Request::create('/x', 'POST', []));

        $this->assertSame($varsayilan, SosyalMedyaRaporu::alicilar(),
            'seçim boşalınca varsayılan listeye dönmeli');
    }

    /** Pasif hesap seçili olsa bile mail listesine girmez */
    public function test_pasif_yonetici_alici_listesine_girmez(): void
    {
        $pasif = Yonetici::where('durum', 0)->first();

        if (! $pasif) {
            $this->markTestSkipped('Pasif yönetici yok.');
        }

        DB::table('sosyal_medya_rapor_alicilari')->insert([
            'yonetici_id' => $pasif->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertNotContains($pasif->email, SosyalMedyaRaporu::alicilar());
    }

    /* ─────────── 2. MAİLDE DİKKAT UYARISI ─────────── */

    public function test_eksik_varken_mailin_ustunde_dikkat_uyarisi_cikar(): void
    {
        $h = $this->hesap($this->marka('ZZ Uyarı'), 'Instagram',
            [$this->gun->dayOfWeekIso => 2]);

        $v     = app(SosyalMedyaTakip::class)->gunSonuVerisi($this->gun);
        $govde = SosyalMedyaRaporu::mailGovdesi($v, $this->gun);

        $this->assertStringContainsString('DİKKAT: Eksik Paylaşımlar Bulunmaktadır!', $govde);

        // Uyarı EN ÜSTTE olmalı — özet kutularından önce
        $uyariYeri = strpos($govde, 'DİKKAT');
        $ozetYeri  = strpos($govde, 'Tamamlanan');
        $this->assertLessThan($ozetYeri, $uyariYeri, 'uyarı özetten önce gelmeli');
    }

    public function test_eksik_yokken_dikkat_uyarisi_cikmaz(): void
    {
        $bos = [
            'tamamlanan' => [[
                'marka' => 'X', 'platform' => 'Y', 'hedef' => 1, 'yapilan' => 1,
                'sorumlu' => 'Z', 'saatler' => ['10:00'], 'gec' => false,
                'eksik_kalemler' => [], 'yapilan_kalemler' => [],
            ]],
            'eksik' => [], 'ertelenen' => [], 'iptal' => [], 'bugune_ertelenen' => [],
        ];

        $this->assertStringNotContainsString('DİKKAT',
            SosyalMedyaRaporu::mailGovdesi($bos, $this->gun));
    }

    /* ─────────── 3. KULLANICI ADI / PROFİL LİNKİ ─────────── */

    public function test_hizli_ekleme_kullanici_adi_ve_link_kaydeder(): void
    {
        $this->planC()->hesapEkle(Request::create('/x', 'POST', [
            'marka_adi'     => 'ZZ Profil',
            'platform'      => 'Instagram',
            'kullanici_adi' => '@zzprofil',
            'link'          => 'https://instagram.com/zzprofil',
        ]));

        $kayit = SosyalMedyaKayit::where('baslik', 'ZZ Profil')->first();
        $hesap = SosyalMedyaHesap::where('kayit_id', $kayit->id)->first();

        $this->assertSame('@zzprofil', $hesap->kullanici_adi);
        $this->assertSame('https://instagram.com/zzprofil', $hesap->link);
    }

    /** İkisi de opsiyonel — boş bırakılınca kayıt yine oluşur */
    public function test_kullanici_adi_ve_link_opsiyoneldir(): void
    {
        $this->planC()->hesapEkle(Request::create('/x', 'POST', [
            'marka_adi' => 'ZZ Opsiyonel', 'platform' => 'LinkedIn',
        ]));

        $kayit = SosyalMedyaKayit::where('baslik', 'ZZ Opsiyonel')->first();
        $hesap = SosyalMedyaHesap::where('kayit_id', $kayit->id)->first();

        $this->assertNotNull($hesap);
        $this->assertNull($hesap->kullanici_adi);
        $this->assertNull($hesap->link);
    }
}
