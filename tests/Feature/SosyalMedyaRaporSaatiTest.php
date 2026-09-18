<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SosyalMedyaPlanController;
use App\Services\SosyalMedyaRaporu;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * GÜN SONU RAPORUNUN GÖNDERİM SAATİ — panelden ayarlanabilir.
 *
 * Kritik nokta: bu değeri routes/console.php okuyor. Oradan fırlayan bir
 * hata SADECE bu raporu değil, panelin TÜM zamanlanmış görevlerini
 * durdurur. O yüzden testlerin çoğu "bozuk değerde ne oluyor" sorusunda.
 */
class SosyalMedyaRaporSaatiTest extends TestCase
{
    use DatabaseTransactions;
    use SosyalMedyaTestVerisi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->olarakGir($this->patron());
        DB::table('sosyal_medya_ayarlar')->where('anahtar', 'rapor_saati')->delete();
    }

    protected function tearDown(): void
    {
        DB::table('sosyal_medya_ayarlar')->where('anahtar', 'rapor_saati')->delete();
        parent::tearDown();
    }

    private function planC(): SosyalMedyaPlanController
    {
        return app(SosyalMedyaPlanController::class);
    }

    private function kaydet(array $veri): void
    {
        $this->planC()->raporAlicilari(Request::create('/x', 'POST', $veri));
    }

    public function test_ayar_yokken_varsayilan_saat_donuyor(): void
    {
        $this->assertSame(SosyalMedyaRaporu::VARSAYILAN_SAAT, SosyalMedyaRaporu::raporSaati());
    }

    public function test_panelden_kaydedilen_saat_okunuyor(): void
    {
        $this->kaydet(['rapor_saati' => '08:30']);

        $this->assertSame('08:30', SosyalMedyaRaporu::raporSaati());
    }

    public function test_saat_guncellenince_ikinci_satir_acilmiyor(): void
    {
        $this->kaydet(['rapor_saati' => '08:30']);
        $this->kaydet(['rapor_saati' => '21:00']);

        $this->assertSame('21:00', SosyalMedyaRaporu::raporSaati());
        $this->assertSame(1, DB::table('sosyal_medya_ayarlar')
            ->where('anahtar', 'rapor_saati')->count());
    }

    public function test_saat_bos_birakilinca_varsayilana_donuyor(): void
    {
        $this->kaydet(['rapor_saati' => '08:30']);
        $this->kaydet([]);

        $this->assertSame(SosyalMedyaRaporu::VARSAYILAN_SAAT, SosyalMedyaRaporu::raporSaati());
    }

    #[DataProvider('bozukSaatler')]
    public function test_bozuk_saat_kaydedilemiyor(string $deger): void
    {
        $this->expectException(ValidationException::class);
        $this->kaydet(['rapor_saati' => $deger]);
    }

    public static function bozukSaatler(): array
    {
        return [
            'saat sinirinin ustu' => ['24:00'],
            'dakika sinirinin ustu' => ['19:60'],
            'saniyeli'            => ['19:05:00'],
            'saat degil'          => ['aksam'],
            'tek haneli'          => ['9:5'],
        ];
    }

    /**
     * Tabloya elle bozuk değer girilmiş olsa bile zamanlayıcı ayakta kalmalı;
     * dailyAt('aksam') çağrısı zamanlayıcının tamamını çökertir.
     */
    public function test_tabloda_bozuk_deger_varsa_varsayilana_dusuyor(): void
    {
        DB::table('sosyal_medya_ayarlar')->insert([
            'anahtar' => 'rapor_saati', 'deger' => '25:99',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(SosyalMedyaRaporu::VARSAYILAN_SAAT, SosyalMedyaRaporu::raporSaati());
    }

    /** Mailin dip notunda yazan saat ile gerçek gönderim saati aynı olmalı */
    public function test_mail_dip_notu_ayarlanan_saati_yaziyor(): void
    {
        $this->kaydet(['rapor_saati' => '08:30']);

        $bos = [
            'tamamlanan' => [], 'eksik' => [], 'ertelenen' => [],
            'iptal' => [], 'bugune_ertelenen' => [],
        ];
        $govde = SosyalMedyaRaporu::mailGovdesi($bos, now());

        $this->assertStringContainsString('her gün 08:30', $govde);
        $this->assertStringNotContainsString('her gün 19:05', $govde);
    }

    /** Alıcı seçimi ile saat aynı formda — biri diğerini silmemeli */
    public function test_saat_kaydi_alici_secimini_bozmuyor(): void
    {
        $kisi = \App\Models\Yonetici::where('durum', 1)
            ->whereNotNull('email')->where('email', '!=', '')->first();

        $this->kaydet(['alicilar' => [$kisi->id], 'rapor_saati' => '08:30']);

        $this->assertSame('08:30', SosyalMedyaRaporu::raporSaati());
        $this->assertSame([$kisi->email], SosyalMedyaRaporu::alicilar());

        DB::table('sosyal_medya_rapor_alicilari')->delete();
    }
}
