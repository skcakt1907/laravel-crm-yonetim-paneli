<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SosyalMedyaPlanController;
use App\Http\Controllers\Admin\SosyalMedyaTakipController;
use App\Models\CRM\SosyalMedyaGunNotu;
use App\Models\CRM\SosyalMedyaHesap;
use App\Models\CRM\SosyalMedyaPaylasim;
use App\Models\CRM\SosyalMedyaPlan;
use App\Models\CRM\SosyalMedyaPlanIstisna;
use App\Models\Yonetici;
use App\Services\SosyalMedyaTakip;
use App\Support\SosyalMedyaYetki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Uzman ve plan ekranları — yazma korumaları ve yetki.
 *
 * Controller'lar DOĞRUDAN çağrılıyor, HTTP üzerinden değil: bu panelde giriş
 * session tabanlı (`admin_id`) ve AdminAuth middleware'i test isteğini giriş
 * ekranına yönlendiriyor. Buradaki amaç middleware'i değil, controller'ın
 * kendi kararlarını doğrulamak.
 */
class SosyalMedyaEkranTest extends TestCase
{
    use DatabaseTransactions;
    use SosyalMedyaTestVerisi;

    protected function setUp(): void
    {
        parent::setUp();

        // Layout $errors bekliyor; istek dışında render ederken tanımlı değil
        View::share('errors', new ViewErrorBag);

        $this->olarakGir($this->patron());
    }

    private function takipC(): SosyalMedyaTakipController
    {
        return app(SosyalMedyaTakipController::class);
    }

    private function planC(): SosyalMedyaPlanController
    {
        return app(SosyalMedyaPlanController::class);
    }

    /* ─────────── UZMAN EKRANI: YAZMA KORUMALARI ─────────── */

    public function test_paylasim_eklenebilir(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [Carbon::today()->dayOfWeekIso => 2]);

        $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => Carbon::today()->toDateString(),
            'link' => 'https://instagram.com/p/abc',
        ]));

        $this->assertSame(1, SosyalMedyaPaylasim::where('hesap_id', $h->id)->count());
    }

    public function test_sinirdan_eski_tarihe_paylasim_eklenemez(): void
    {
        $h    = $this->hesap($this->marka(), 'Instagram');
        $eski = Carbon::today()->subDays(SosyalMedyaTakip::GERIYE_GUN + 5);

        $cevap = $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => $eski->toDateString(),
        ]));

        $this->assertNotNull($cevap->getSession()->get('error'));
        $this->assertSame(0, SosyalMedyaPaylasim::where('hesap_id', $h->id)->count());
    }

    /**
     * Gün "ertelendi/iptal" işaretliyken paylaşım eklenirse kayıt kendi
     * içinde çelişir: hem yapılmadı hem yapıldı. Önce notun kaldırılması
     * isteniyor.
     */
    public function test_ertelenmis_gune_paylasim_eklenemez(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram', [Carbon::today()->dayOfWeekIso => 1]);

        SosyalMedyaGunNotu::create([
            'hesap_id' => $h->id, 'tarih' => Carbon::today()->toDateString(),
            'tip' => SosyalMedyaGunNotu::TIP_ERTELENDI,
            'ertelendi_tarih' => Carbon::tomorrow()->toDateString(), 'sebep' => 'onay yok',
        ]);

        $cevap = $this->takipC()->paylasimEkle(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => Carbon::today()->toDateString(),
        ]));

        $this->assertStringContainsString('ertelendi', $cevap->getSession()->get('error'));
        $this->assertSame(0, SosyalMedyaPaylasim::where('hesap_id', $h->id)->count());
    }

    public function test_erteleme_yeni_tarih_olmadan_kaydedilemez(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram');

        $cevap = $this->takipC()->gunNotu(Request::create('/x', 'POST', [
            'hesap_id' => $h->id, 'tarih' => Carbon::today()->toDateString(),
            'tip' => 'ertelendi', 'sebep' => 'sebep var ama tarih yok',
        ]));

        $this->assertNotNull($cevap->getSession()->get('error'));
        $this->assertSame(0, SosyalMedyaGunNotu::where('hesap_id', $h->id)->count());
    }

    /* ─────────── PLAN EKRANI ─────────── */

    public function test_plan_kaydedilir_ve_sifir_girilen_gun_silinir(): void
    {
        $h = $this->hesap($this->marka(), 'Instagram');

        $this->planC()->kaydet(Request::create('/x', 'POST', [
            'hedef' => [$h->id => [1 => 3, 2 => 0, 3 => 2]],
        ]));

        $this->assertSame(2, SosyalMedyaPlan::where('hesap_id', $h->id)->count());
        $this->assertSame(3, SosyalMedyaPlan::where('hesap_id', $h->id)->where('gun', 1)->value('hedef_adet'));
        $this->assertSame(0, SosyalMedyaPlan::where('hesap_id', $h->id)->where('gun', 2)->count(),
            'hedefi 0 yapılan günün plan satırı silinmeli');
    }

    public function test_sorumlu_atanir_ve_bosaltilabilir(): void
    {
        $ben = $this->patron();
        $h   = $this->hesap($this->marka(), 'Instagram');

        $this->planC()->kaydet(Request::create('/x', 'POST', ['sorumlu' => [$h->id => $ben->id]]));
        $this->assertSame($ben->id, SosyalMedyaHesap::find($h->id)->sorumlu_id);

        $this->planC()->kaydet(Request::create('/x', 'POST', ['sorumlu' => [$h->id => '']]));
        $this->assertNull(SosyalMedyaHesap::find($h->id)->sorumlu_id);
    }

    /** Aynı tarihe ikinci istisna çakışma değil güncelleme olmalı */
    public function test_ayni_tarihe_ikinci_istisna_gunceller(): void
    {
        $h     = $this->hesap($this->marka(), 'Instagram');
        $tarih = Carbon::parse('2026-10-29')->toDateString();

        foreach ([0, 5] as $adet) {
            $this->planC()->istisnaEkle(Request::create('/x', 'POST', [
                'hesap_id' => $h->id, 'tarih' => $tarih, 'hedef_adet' => $adet,
            ]));
        }

        $this->assertSame(1, SosyalMedyaPlanIstisna::where('hesap_id', $h->id)->count());
        $this->assertSame(5, SosyalMedyaPlanIstisna::where('hesap_id', $h->id)->value('hedef_adet'));
    }

    /**
     * Plan ızgarasında şifre kasası kayıtları görünmemeli. Aynı tabloda
     * cPanel/FTP girişleri de duruyor; bolum filtresi olmasa şifreli
     * kayıtlar bu ekrana sızardı.
     */
    public function test_web_sitesi_kayitlari_plan_ekraninda_gorunmez(): void
    {
        $marka = $this->marka('Kasa Testi');

        SosyalMedyaHesap::create([
            'kayit_id' => $marka->id, 'bolum' => SosyalMedyaHesap::BOLUM_WEB,
            'platform' => 'cPanel', 'kullanici_adi' => 'kasa_kullanici',
            'sifre' => 'CokGizliParola123',
        ]);

        $html = $this->planC()->index(Request::create('/x'))->render();

        $this->assertStringNotContainsString('cPanel', $html);
        $this->assertStringNotContainsString('CokGizliParola123', $html);
        $this->assertStringNotContainsString('kasa_kullanici', $html);
    }

    /* ─────────── YETKİ ─────────── */

    public function test_bayi_rolu_module_giremez(): void
    {
        $bayi = Yonetici::where('rol', Yonetici::ROL_BAYI)->first();

        if (! $bayi) {
            $this->markTestSkipped('Bayi rolünde kullanıcı yok.');
        }

        $this->olarakGir($bayi);

        $this->expectException(HttpException::class);
        $this->planC()->index(Request::create('/x'));
    }

    public function test_patron_her_zaman_girebilir(): void
    {
        $this->olarakGir($this->patron());

        $this->assertTrue(SosyalMedyaYetki::varMi());
    }

    /**
     * Yetki SABİT ROL LİSTESİYLE kontrol edilmemeli. Canlıda roller 1-4 ile
     * sınırlı değil (Muhasebe=5, Sosyal Medya=20, Yazılımcı=21). tam_yetki
     * işaretli her rol girebilmeli.
     */
    public function test_tam_yetkili_rol_girebilir(): void
    {
        $tamYetkili = Yonetici::where('durum', 1)
            ->whereIn('rol', \Illuminate\Support\Facades\DB::table('roller')
                ->where('tam_yetki', 1)->where('durum', 1)->pluck('id'))
            ->first();

        if (! $tamYetkili) {
            $this->markTestSkipped('tam_yetki işaretli rolde kullanıcı yok.');
        }

        $this->olarakGir($tamYetkili);

        $this->assertTrue(SosyalMedyaYetki::varMi(),
            'tam_yetki=1 olan rol, rol_yetkileri kaydı olmadan da girebilmeli');
    }

    public function test_oturum_yoksa_reddedilir(): void
    {
        session()->forget(['admin_id', 'admin_rol']);

        $this->assertFalse(SosyalMedyaYetki::varMi());
    }
}
