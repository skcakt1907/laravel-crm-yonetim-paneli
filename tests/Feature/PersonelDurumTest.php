<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Hrm\DurumController;
use App\Models\Yonetici;
use App\Services\PersonelDurumu;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * PERSONEL ANLIK DURUM BİLDİRİMİ.
 *
 * İki şey korunuyor:
 *  1. TARİHÇE bozulmamalı — "dün kaçta izne çıktı" sorusunun cevabı buna
 *     bağlı. Durum değişince eskisi kapanmalı, yenisi açılmalı.
 *  2. MAİL yağmuru olmamalı — aynı durum arka arkaya seçilirse ya da o
 *     durum için mail kapalıysa bildirim gitmemeli.
 */
class PersonelDurumTest extends TestCase
{
    use DatabaseTransactions;

    private Yonetici $kisi;

    private int $aktifTip;

    private int $molaTip;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->kisi = Yonetici::where('durum', 1)->firstOrFail();
        session(['admin_id' => $this->kisi->id, 'admin_rol' => $this->kisi->rol]);

        $this->aktifTip = (int) DB::table('personel_durum_tipleri')->where('ad', 'Aktif')->value('id');
        $this->molaTip  = (int) DB::table('personel_durum_tipleri')->where('ad', 'Molada')->value('id');

        // Önceki testlerden kalan açık durum olmasın
        DB::table('personel_durumlari')->where('yonetici_id', $this->kisi->id)->delete();
    }

    private function controller(): DurumController
    {
        return app(DurumController::class);
    }

    /* ─────────── TARİHÇE ─────────── */

    public function test_durum_degisince_kayit_aciliyor(): void
    {
        PersonelDurumu::degistir($this->kisi->id, $this->aktifTip, 'Panelde çalışıyorum');

        $suan = PersonelDurumu::suAnki($this->kisi->id);

        $this->assertNotNull($suan);
        $this->assertSame('Aktif', $suan->ad);
        $this->assertSame('Panelde çalışıyorum', $suan->not);
    }

    /**
     * Tarihçenin temeli: yeni duruma geçilince eskisi KAPANMALI.
     * Kapanmazsa aynı kişinin iki açık durumu olur ve "şu an ne yapıyor"
     * sorusunun tek cevabı kalmaz.
     */
    public function test_yeni_duruma_gecince_eskisi_kapaniyor(): void
    {
        PersonelDurumu::degistir($this->kisi->id, $this->aktifTip);
        PersonelDurumu::degistir($this->kisi->id, $this->molaTip);

        $acikKayit = DB::table('personel_durumlari')
            ->where('yonetici_id', $this->kisi->id)
            ->whereNull('bitis')->count();

        $this->assertSame(1, $acikKayit, 'aynı anda tek açık durum olmalı');
        $this->assertSame('Molada', PersonelDurumu::suAnki($this->kisi->id)->ad);
    }

    public function test_gecmis_kayitlari_duruyor(): void
    {
        PersonelDurumu::degistir($this->kisi->id, $this->aktifTip);
        PersonelDurumu::degistir($this->kisi->id, $this->molaTip);

        $gecmis = PersonelDurumu::gecmis($this->kisi->id);

        $this->assertCount(2, $gecmis);
        $this->assertSame('Molada', $gecmis->first()->ad);   // en yeni önce
    }

    /* ─────────── MAİL ─────────── */

    public function test_durum_degisince_mail_gonderilmeli_isareti_donuyor(): void
    {
        $this->assertTrue(PersonelDurumu::degistir($this->kisi->id, $this->aktifTip));
    }

    /**
     * Yanlış tıklayıp aynı durumu tekrar seçen biri ikinci mail
     * göndermemeli.
     */
    public function test_ayni_durum_tekrar_secilince_mail_gitmiyor(): void
    {
        PersonelDurumu::degistir($this->kisi->id, $this->aktifTip);

        $this->assertFalse(PersonelDurumu::degistir($this->kisi->id, $this->aktifTip));

        $kayit = DB::table('personel_durumlari')->where('yonetici_id', $this->kisi->id)->count();
        $this->assertSame(1, $kayit, 'aynı duruma geçiş yeni kayıt açmamalı');
    }

    /** Aynı durumda kalıp notu değiştirmek kaydı günceller ama mail atmaz */
    public function test_ayni_durumda_not_degisirse_guncelleniyor_mail_gitmiyor(): void
    {
        PersonelDurumu::degistir($this->kisi->id, $this->aktifTip, 'ilk not');

        $this->assertFalse(PersonelDurumu::degistir($this->kisi->id, $this->aktifTip, 'yeni not'));
        $this->assertSame('yeni not', PersonelDurumu::suAnki($this->kisi->id)->not);
    }

    /**
     * "Molada" günde on kez mail atmaya başlarsa panelden kapatılabilsin
     * diye durum tipinde ayrı bayrak var.
     */
    public function test_mail_kapali_durumda_bildirim_gitmiyor(): void
    {
        DB::table('personel_durum_tipleri')->where('id', $this->molaTip)->update(['mail_gonder' => 0]);

        $this->assertFalse(PersonelDurumu::degistir($this->kisi->id, $this->molaTip));

        DB::table('personel_durum_tipleri')->where('id', $this->molaTip)->update(['mail_gonder' => 1]);
    }

    public function test_alicilar_secilmemisse_varsayilan_listeye_dusuyor(): void
    {
        DB::table('personel_durum_alicilari')->delete();

        $alicilar = PersonelDurumu::alicilar();

        $this->assertNotEmpty($alicilar, 'varsayılan liste boş dönmemeli');
    }

    public function test_panelden_secilen_alicilar_oncelikli(): void
    {
        // Alıcı seçimi yönetici yetkisi ister
        $patron = Yonetici::where('rol', 1)->where('durum', 1)->first();

        if (! $patron) {
            $this->markTestSkipped('Patron rolünde kullanıcı gerekiyor.');
        }

        session(['admin_id' => $patron->id, 'admin_rol' => $patron->rol]);

        $hedef = Yonetici::where('durum', 1)->whereNotNull('email')
            ->where('email', '!=', '')->firstOrFail();

        $this->controller()->alicilar(Request::create('/x', 'POST', ['alicilar' => [$hedef->id]]));

        $this->assertSame([$hedef->email], PersonelDurumu::alicilar());

        DB::table('personel_durum_alicilari')->delete();
    }

    /* ─────────── YETKİ ─────────── */

    /**
     * Kendi durumunu herkes değiştirir; başkasınınki yönetici ister.
     * Yetkisiz biri başkasının durumunu değiştirebilseydi pano güvenilmez
     * olurdu.
     */
    public function test_yetkisiz_kisi_baskasinin_durumunu_degistiremiyor(): void
    {
        $baskasi = Yonetici::where('durum', 1)->where('id', '!=', $this->kisi->id)->first();

        if (! $baskasi) {
            $this->markTestSkipped('İkinci bir aktif yönetici gerekiyor.');
        }

        // Yetkisiz bir rol ile oturum
        session(['admin_id' => $this->kisi->id, 'admin_rol' => 99]);

        $this->expectException(ValidationException::class);

        $this->controller()->degistir(Request::create('/x', 'POST', [
            'durum_tipi_id' => $this->aktifTip,
            'yonetici_id'   => $baskasi->id,
        ]));
    }

    /* ─────────── DURUM TİPLERİ ─────────── */

    /**
     * Geçmişte kullanılmış tip SİLİNMEMELİ — silinirse o kayıtların bağlı
     * olduğu tip kaybolur, tarihçe okunamaz hale gelir.
     */
    public function test_kullanilmis_durum_tipi_silinmiyor_pasife_aliniyor(): void
    {
        $patron = Yonetici::where('rol', 1)->where('durum', 1)->first();

        if (! $patron) {
            $this->markTestSkipped('Patron rolünde kullanıcı gerekiyor.');
        }

        session(['admin_id' => $patron->id, 'admin_rol' => $patron->rol]);

        PersonelDurumu::degistir($this->kisi->id, $this->molaTip);

        $this->controller()->tipSil($this->molaTip);

        $tip = DB::table('personel_durum_tipleri')->where('id', $this->molaTip)->first();

        $this->assertNotNull($tip, 'tip silinmemeli');
        $this->assertSame(0, (int) $tip->aktif, 'tip pasife alınmalı');

        DB::table('personel_durum_tipleri')->where('id', $this->molaTip)->update(['aktif' => 1]);
    }

    /** Pasife alınmış durum seçilememeli */
    public function test_pasif_durum_secilemiyor(): void
    {
        DB::table('personel_durum_tipleri')->where('id', $this->molaTip)->update(['aktif' => 0]);

        try {
            $this->expectException(ValidationException::class);

            $this->controller()->degistir(Request::create('/x', 'POST', [
                'durum_tipi_id' => $this->molaTip,
            ]));
        } finally {
            DB::table('personel_durum_tipleri')->where('id', $this->molaTip)->update(['aktif' => 1]);
        }
    }

    /* ─────────── PANO ─────────── */

    public function test_herkes_listesinde_durum_gorunuyor(): void
    {
        PersonelDurumu::degistir($this->kisi->id, $this->aktifTip, 'Vidal Dent çekimi');

        $satir = PersonelDurumu::herkes()->firstWhere('id', $this->kisi->id);

        $this->assertNotNull($satir);
        $this->assertSame('Aktif', $satir->durum_adi);
        $this->assertSame('Vidal Dent çekimi', $satir->durum_notu);
        $this->assertSame('🟢', $satir->durum_emoji);
    }
}
