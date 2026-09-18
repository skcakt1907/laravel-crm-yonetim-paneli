<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Hrm\DurumController;
use App\Models\Yonetici;
use App\Services\PersonelDurumGecmisi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * DURUM GEÇMİŞİ RAPORU.
 *
 * Tarihçe zaten tutuluyordu, görüntüleyecek ekran yoktu.
 *
 * Buradaki asıl risk sessiz yanlış hesap: süre yanlış çıkarsa kimse fark
 * etmez, rapor "çalışıyor" görünür. Bu yüzden test edilenler:
 *  - Aralığın dışında kalan kısım sayılmamalı (kırpma)
 *  - Hâlâ süren kayıt, aralığın sonunu aşmamalı
 *  - Aralıkla hiç kesişmeyen kayıt rapora girmemeli
 *  - Bir gün 24 saati aşmamalı
 *
 * NOT: `personel_durumlari` InnoDB, ama `yoneticiler` MyISAM olduğu için
 * test kendi kayıtlarını tearDown'da elle siliyor.
 */
class PersonelDurumGecmisiTest extends TestCase
{
    private Yonetici $kisi;

    private int $tipId;

    private int $tipId2;

    protected function setUp(): void
    {
        parent::setUp();

        if (! \Illuminate\Support\Facades\Schema::hasTable('personel_durumlari')) {
            $this->markTestSkipped('personel_durumlari tablosu yok.');
        }

        $this->kisi = Yonetici::where('durum', 1)->firstOrFail();

        $tipler = DB::table('personel_durum_tipleri')->orderBy('sira')->limit(2)->get();
        $this->tipId  = (int) $tipler[0]->id;
        $this->tipId2 = (int) ($tipler[1]->id ?? $tipler[0]->id);

        session(['admin_id' => $this->kisi->id, 'admin_rol' => $this->kisi->rol]);

        $this->temizle();
    }

    protected function tearDown(): void
    {
        $this->temizle();
        parent::tearDown();
    }

    private function temizle(): void
    {
        DB::table('personel_durumlari')->where('yonetici_id', $this->kisi->id)->delete();
    }

    /** Ham kayıt ekler — servis katmanını atlayarak, tarihi kontrol edebilmek için */
    private function kayit(string $bas, ?string $son, ?int $tip = null, ?string $not = null): int
    {
        return DB::table('personel_durumlari')->insertGetId([
            'yonetici_id'    => $this->kisi->id,
            'durum_tipi_id'  => $tip ?? $this->tipId,
            'not'            => $not,
            'baslangic'      => $bas,
            'bitis'          => $son,
            'degistiren_id'  => $this->kisi->id,
            'created_at'     => $bas,
            'updated_at'     => $bas,
        ]);
    }

    private function rapor(string $bas, string $son)
    {
        [$b, $s] = PersonelDurumGecmisi::aralik($bas, $son);

        return PersonelDurumGecmisi::kayitlar($b, $s, $this->kisi->id);
    }

    /* ─────────── SÜRE HESABI ─────────── */

    public function test_kapali_kaydin_suresi_dogru(): void
    {
        $gun = Carbon::today()->toDateString();
        $this->kayit("{$gun} 09:00:00", "{$gun} 11:30:00");

        $k = $this->rapor($gun, $gun)->first();

        $this->assertSame(150, $k->dakika);
        $this->assertSame('2sa 30dk', $k->sure);
    }

    /**
     * Aralıktan ÖNCE başlayan kayıt, yalnızca aralığa düşen kısmıyla
     * sayılmalı. Kırpmasaydık tek günlük rapor 24 saati aşardı.
     */
    public function test_aralik_disinda_kalan_kisim_sayilmiyor(): void
    {
        $dun   = Carbon::today()->subDay()->toDateString();
        $bugun = Carbon::today()->toDateString();

        // Dün 22:00'de başladı, bugün 02:00'de bitti — 4 saat sürdü
        $this->kayit("{$dun} 22:00:00", "{$bugun} 02:00:00");

        // Yalnızca bugüne bakılırsa 2 saat sayılmalı
        $this->assertSame(120, $this->rapor($bugun, $bugun)->first()->dakika);

        // İki gün birden alınırsa 4 saatin tamamı
        $this->assertSame(240, $this->rapor($dun, $bugun)->first()->dakika);
    }

    /** Hâlâ süren kayıt: geçmiş bir aralıkta, aralığın sonunu aşmamalı */
    public function test_suren_kayit_araligin_sonunu_asmiyor(): void
    {
        $dun = Carbon::today()->subDay()->toDateString();

        // Dün 23:00'te başladı, hâlâ açık
        $this->kayit("{$dun} 23:00:00", null);

        /*
         * Dünün raporu: yalnızca 23:00'ten gün sonuna kadarki kısım.
         * endOfDay() 23:59:59 olduğu için 60 değil 59 dakika çıkar --
         * bir saniyelik fark, dakikaya yuvarlanmadan aşağı kırpılıyor.
         */
        $k = $this->rapor($dun, $dun)->first();

        $this->assertTrue($k->suruyor);
        $this->assertGreaterThanOrEqual(59, $k->dakika);
        $this->assertLessThanOrEqual(60, $k->dakika);
    }

    /** Aralıkla hiç kesişmeyen kayıt rapora girmemeli */
    public function test_aralikla_kesismeyen_kayit_rapora_girmiyor(): void
    {
        $eski = Carbon::today()->subDays(10)->toDateString();
        $this->kayit("{$eski} 09:00:00", "{$eski} 10:00:00");

        $bugun = Carbon::today()->toDateString();

        $this->assertCount(0, $this->rapor($bugun, $bugun));
    }

    /** Aralıktan önce başlayıp sonra biten kayıt: aralığın tamamı kadar */
    public function test_araligi_tamamen_kapsayan_kayit(): void
    {
        $once  = Carbon::today()->subDays(2)->toDateString();
        $sonra = Carbon::today()->addDay()->toDateString();
        $dun   = Carbon::today()->subDay()->toDateString();

        $this->kayit("{$once} 08:00:00", "{$sonra} 08:00:00");

        // Dünün tamamı = 1440 dakika (endOfDay 23:59:59 olduğu için 1 dk eksik)
        $dakika = $this->rapor($dun, $dun)->first()->dakika;

        $this->assertGreaterThanOrEqual(1439, $dakika);
        $this->assertLessThanOrEqual(1440, $dakika);
    }

    /* ─────────── ÖZET ─────────── */

    public function test_ozet_ayni_durumu_topluyor(): void
    {
        $gun = Carbon::today()->toDateString();
        $this->kayit("{$gun} 09:00:00", "{$gun} 10:00:00");
        $this->kayit("{$gun} 13:00:00", "{$gun} 14:30:00");

        $ozet = PersonelDurumGecmisi::ozet($this->rapor($gun, $gun));
        $satir = $ozet[$this->kisi->id];

        $this->assertSame(150, $satir['toplam']);
        $this->assertSame('2sa 30dk', $satir['toplam_metin']);

        $ilk = reset($satir['durumlar']);
        $this->assertSame(2, $ilk['adet'], 'İki kayıt tek satırda toplanmalı');
    }

    public function test_ozet_farkli_durumlari_ayri_tutuyor(): void
    {
        if ($this->tipId === $this->tipId2) {
            $this->markTestSkipped('İkinci bir durum tipi gerekiyor.');
        }

        $gun = Carbon::today()->toDateString();
        $this->kayit("{$gun} 09:00:00", "{$gun} 10:00:00", $this->tipId);
        $this->kayit("{$gun} 10:00:00", "{$gun} 13:00:00", $this->tipId2);

        $satir = PersonelDurumGecmisi::ozet($this->rapor($gun, $gun))[$this->kisi->id];

        $this->assertCount(2, $satir['durumlar']);
        $this->assertSame(240, $satir['toplam']);

        // En çok vakit geçirilen durum üstte olmalı
        $ilk = reset($satir['durumlar']);
        $this->assertSame(180, $ilk['dakika']);
    }

    /* ─────────── SÜRE METNİ ─────────── */

    public function test_sure_metni_okunur(): void
    {
        $this->assertSame('—',        PersonelDurumGecmisi::sureMetni(0));
        $this->assertSame('—',        PersonelDurumGecmisi::sureMetni(-5));
        $this->assertSame('45dk',     PersonelDurumGecmisi::sureMetni(45));
        $this->assertSame('1sa',      PersonelDurumGecmisi::sureMetni(60));
        $this->assertSame('2sa 30dk', PersonelDurumGecmisi::sureMetni(150));
    }

    /* ─────────── ARALIK ÇÖZÜMLEME ─────────── */

    /** Parametre yoksa bu haftaya düşülmeli */
    public function test_bos_aralik_bu_haftaya_dusuyor(): void
    {
        [$b, $s, $etiket] = PersonelDurumGecmisi::aralik(null, null);

        $this->assertSame(Carbon::now()->startOfWeek()->toDateString(), $b->toDateString());
        $this->assertSame('Bu hafta', $etiket);
    }

    /** Bozuk tarihte de rapor patlamamalı */
    public function test_bozuk_tarih_bu_haftaya_dusuyor(): void
    {
        [$b, , $etiket] = PersonelDurumGecmisi::aralik('abc', '32-13-2026');

        $this->assertSame(Carbon::now()->startOfWeek()->toDateString(), $b->toDateString());
        $this->assertSame('Bu hafta', $etiket);
    }

    /**
     * Tarihler ters girilirse düzeltilmeli — düzeltilmezse rapor boş
     * çıkıyordu ve kullanıcı sebebini göremiyordu.
     */
    public function test_ters_girilen_tarihler_duzeltiliyor(): void
    {
        [$b, $s] = PersonelDurumGecmisi::aralik('2026-09-20', '2026-09-10');

        $this->assertSame('2026-09-10', $b->toDateString());
        $this->assertSame('2026-09-20', $s->toDateString());
    }

    /* ─────────── EKRAN ─────────── */

    public function test_ekran_aciliyor_ve_kayitlari_gosteriyor(): void
    {
        $gun = Carbon::today()->toDateString();
        $this->kayit("{$gun} 09:00:00", "{$gun} 10:00:00", null, 'Vidal Dent çekimi');

        $veri = app(DurumController::class)
            ->gecmis(Request::create('/x', 'GET', ['bas' => $gun, 'son' => $gun]))
            ->getData();

        $this->assertCount(1, $veri['kayitlar']);
        $this->assertSame('Vidal Dent çekimi', $veri['kayitlar']->first()->not);
        $this->assertArrayHasKey($this->kisi->id, $veri['ozet']);
    }

    public function test_kisi_suzgeci_calisiyor(): void
    {
        $gun = Carbon::today()->toDateString();
        $this->kayit("{$gun} 09:00:00", "{$gun} 10:00:00");

        $baskasi = Yonetici::where('durum', 1)->where('id', '!=', $this->kisi->id)->first();

        if (! $baskasi) {
            $this->markTestSkipped('İkinci bir aktif yönetici gerekiyor.');
        }

        $veri = app(DurumController::class)
            ->gecmis(Request::create('/x', 'GET', [
                'bas' => $gun, 'son' => $gun, 'kisi' => $baskasi->id,
            ]))
            ->getData();

        $this->assertCount(0, $veri['kayitlar'], 'Başkasının süzgecinde bu kayıt çıkmamalı');
    }
}
