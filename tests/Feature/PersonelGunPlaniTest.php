<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Hrm\PlanController;
use App\Models\Yonetici;
use App\Services\PersonelGunPlani;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * GÜN PLANI — saatli program.
 *
 * Korunanlar:
 *  - Saat mantığı: bitiş başlangıçtan önce olamaz, süre doğru hesaplanmalı
 *  - Yetki: kimse başkasının planına izinsiz yazamaz
 *  - Kopyalama: sabahları planı elle yazmayı öldürmesin diye eklendi,
 *    mevcut satırları silmemeli
 */
class PersonelGunPlaniTest extends TestCase
{
    use DatabaseTransactions;

    private Yonetici $kisi;

    private Carbon $gun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kisi = Yonetici::where('durum', 1)->firstOrFail();
        session(['admin_id' => $this->kisi->id, 'admin_rol' => $this->kisi->rol]);

        $this->gun = Carbon::today();

        DB::table('personel_gun_plani')->where('yonetici_id', $this->kisi->id)->delete();
    }

    private function controller(): PlanController
    {
        return app(PlanController::class);
    }

    /** @param array<string, mixed> $ustuneYaz */
    private function ekle(array $ustuneYaz = [])
    {
        return $this->controller()->ekle(Request::create('/x', 'POST', array_merge([
            'tarih'     => $this->gun->toDateString(),
            'baslangic' => '09:00',
            'bitis'     => '11:00',
            'baslik'    => 'Vidal Dent çekimi',
        ], $ustuneYaz)));
    }

    /* ─────────── EKLEME ─────────── */

    public function test_plan_satiri_ekleniyor(): void
    {
        $this->ekle();

        $satirlar = PersonelGunPlani::kisiGun($this->kisi->id, $this->gun);

        $this->assertCount(1, $satirlar);
        $this->assertSame('Vidal Dent çekimi', $satirlar->first()->baslik);
        $this->assertSame('09:00:00', $satirlar->first()->baslangic);
    }

    /** Bitişi belirsiz iş olabilir — "14:00 kurgu" gibi */
    public function test_bitis_saati_bos_birakilabiliyor(): void
    {
        $this->ekle(['bitis' => null, 'baslik' => 'Kurgu']);

        $this->assertNull(PersonelGunPlani::kisiGun($this->kisi->id, $this->gun)->first()->bitis);
    }

    /**
     * Bitiş başlangıçtan önce olamaz: öyle bir kayıt ekranda sırayı bozar
     * ve süre hesabı eksi çıkar.
     */
    public function test_bitis_baslangictan_once_olamaz(): void
    {
        $this->expectException(ValidationException::class);

        $this->ekle(['baslangic' => '14:00', 'bitis' => '10:00']);
    }

    public function test_bozuk_saat_kabul_edilmiyor(): void
    {
        $this->expectException(ValidationException::class);

        $this->ekle(['baslangic' => '25:00']);
    }

    /* ─────────── SIRALAMA VE ÖZET ─────────── */

    public function test_satirlar_saate_gore_siraliniyor(): void
    {
        $this->ekle(['baslangic' => '14:00', 'bitis' => '15:00', 'baslik' => 'Kurgu']);
        $this->ekle(['baslangic' => '09:00', 'bitis' => '11:00', 'baslik' => 'Çekim']);

        $satirlar = PersonelGunPlani::kisiGun($this->kisi->id, $this->gun);

        $this->assertSame('Çekim', $satirlar->first()->baslik);
        $this->assertSame('Kurgu', $satirlar->last()->baslik);
    }

    public function test_ozet_saat_topluyor(): void
    {
        $this->ekle(['baslangic' => '09:00', 'bitis' => '11:00']);   // 2 saat
        $this->ekle(['baslangic' => '13:00', 'bitis' => '14:30']);   // 1.5 saat

        $ozet = PersonelGunPlani::ozet(PersonelGunPlani::kisiGun($this->kisi->id, $this->gun));

        $this->assertSame(2, $ozet['adet']);
        $this->assertSame(3.5, $ozet['saat']);
    }

    /** Bitişi olmayan iş süreye katılmamalı — yoksa toplam uydurma olur */
    public function test_bitissiz_is_sureye_katilmiyor(): void
    {
        $this->ekle(['baslangic' => '09:00', 'bitis' => '11:00']);
        $this->ekle(['baslangic' => '14:00', 'bitis' => null]);

        $ozet = PersonelGunPlani::ozet(PersonelGunPlani::kisiGun($this->kisi->id, $this->gun));

        $this->assertSame(2, $ozet['adet']);
        $this->assertSame(2.0, $ozet['saat']);
    }

    /* ─────────── İŞARETLEME VE SİLME ─────────── */

    public function test_tamamlandi_isareti_cevriliyor(): void
    {
        $this->ekle();
        $satir = PersonelGunPlani::kisiGun($this->kisi->id, $this->gun)->first();

        $this->controller()->isaretle($satir->id);
        $this->assertSame(1, (int) DB::table('personel_gun_plani')->where('id', $satir->id)->value('tamamlandi'));

        $this->controller()->isaretle($satir->id);
        $this->assertSame(0, (int) DB::table('personel_gun_plani')->where('id', $satir->id)->value('tamamlandi'));
    }

    public function test_satir_silinebiliyor(): void
    {
        $this->ekle();
        $satir = PersonelGunPlani::kisiGun($this->kisi->id, $this->gun)->first();

        $this->controller()->sil($satir->id);

        $this->assertCount(0, PersonelGunPlani::kisiGun($this->kisi->id, $this->gun));
    }

    /* ─────────── KOPYALAMA ─────────── */

    public function test_dunku_plan_kopyalaniyor(): void
    {
        $dun = $this->gun->copy()->subDay();

        PersonelGunPlani::ekle([
            'yonetici_id' => $this->kisi->id, 'tarih' => $dun->toDateString(),
            'baslangic' => '09:00', 'bitis' => '11:00', 'baslik' => 'Çekim',
        ]);

        $adet = PersonelGunPlani::kopyala($this->kisi->id, $dun, $this->gun);

        $this->assertSame(1, $adet);
        $this->assertSame('Çekim', PersonelGunPlani::kisiGun($this->kisi->id, $this->gun)->first()->baslik);

        DB::table('personel_gun_plani')->where('yonetici_id', $this->kisi->id)
            ->whereDate('tarih', $dun->toDateString())->delete();
    }

    /** Kopyalanan satırlar yeni güne ait — tamamlandı işaretleri sıfırlanmalı */
    public function test_kopyalanan_satirlarin_isareti_sifirlaniyor(): void
    {
        $dun = $this->gun->copy()->subDay();

        PersonelGunPlani::ekle([
            'yonetici_id' => $this->kisi->id, 'tarih' => $dun->toDateString(),
            'baslangic' => '09:00', 'bitis' => '11:00', 'baslik' => 'Çekim',
        ]);
        DB::table('personel_gun_plani')->where('yonetici_id', $this->kisi->id)
            ->whereDate('tarih', $dun->toDateString())->update(['tamamlandi' => 1]);

        PersonelGunPlani::kopyala($this->kisi->id, $dun, $this->gun);

        $this->assertSame(0, (int) PersonelGunPlani::kisiGun($this->kisi->id, $this->gun)->first()->tamamlandi);

        DB::table('personel_gun_plani')->where('yonetici_id', $this->kisi->id)
            ->whereDate('tarih', $dun->toDateString())->delete();
    }

    /** Kopyalama mevcut satırları SİLMEMELİ, üzerine eklemeli */
    public function test_kopyalama_mevcut_satirlari_silmiyor(): void
    {
        $dun = $this->gun->copy()->subDay();

        $this->ekle(['baslik' => 'Bugüne özel iş']);

        PersonelGunPlani::ekle([
            'yonetici_id' => $this->kisi->id, 'tarih' => $dun->toDateString(),
            'baslangic' => '15:00', 'bitis' => '16:00', 'baslik' => 'Dünkü iş',
        ]);

        PersonelGunPlani::kopyala($this->kisi->id, $dun, $this->gun);

        $basliklar = PersonelGunPlani::kisiGun($this->kisi->id, $this->gun)->pluck('baslik');

        $this->assertContains('Bugüne özel iş', $basliklar->all());
        $this->assertContains('Dünkü iş', $basliklar->all());

        DB::table('personel_gun_plani')->where('yonetici_id', $this->kisi->id)
            ->whereDate('tarih', $dun->toDateString())->delete();
    }

    /* ─────────── YETKİ ─────────── */

    public function test_yetkisiz_kisi_baskasinin_planina_yazamiyor(): void
    {
        $baskasi = Yonetici::where('durum', 1)->where('id', '!=', $this->kisi->id)->first();

        if (! $baskasi) {
            $this->markTestSkipped('İkinci bir aktif yönetici gerekiyor.');
        }

        session(['admin_id' => $this->kisi->id, 'admin_rol' => 99]);

        $this->expectException(ValidationException::class);

        $this->ekle(['yonetici_id' => $baskasi->id]);
    }

    /* ─────────── GÜN AYRIMI ─────────── */

    /** Bir günün planı başka güne karışmamalı */
    public function test_gunler_birbirine_karismiyor(): void
    {
        $yarin = $this->gun->copy()->addDay();

        $this->ekle(['baslik' => 'Bugün']);
        $this->ekle(['tarih' => $yarin->toDateString(), 'baslik' => 'Yarın']);

        $this->assertSame('Bugün', PersonelGunPlani::kisiGun($this->kisi->id, $this->gun)->first()->baslik);
        $this->assertSame('Yarın', PersonelGunPlani::kisiGun($this->kisi->id, $yarin)->first()->baslik);

        DB::table('personel_gun_plani')->where('yonetici_id', $this->kisi->id)
            ->whereDate('tarih', $yarin->toDateString())->delete();
    }
}
