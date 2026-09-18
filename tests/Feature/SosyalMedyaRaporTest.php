<?php

namespace Tests\Feature;

use App\Models\CRM\SosyalMedyaGunNotu;
use App\Models\CRM\SosyalMedyaPaylasim;
use App\Services\SosyalMedyaRaporu;
use App\Services\SosyalMedyaTakip;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Gün sonu raporu — içerik ve çift gönderim kilidi.
 */
class SosyalMedyaRaporTest extends TestCase
{
    use DatabaseTransactions;
    use SosyalMedyaTestVerisi;

    private const GOREV = 'sosyal-medya-gun-sonu';

    /** Kesinlikle planı olmayan bir tarih — "gönderme" yolunu sınamak için */
    private const PLANSIZ_GUN = '2000-01-01';

    private SosyalMedyaTakip $takip;
    private Carbon $gun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->takip = app(SosyalMedyaTakip::class);
        $this->gun   = Carbon::today();
    }

    /**
     * gunluk_gorev_log MyISAM: DatabaseTransactions onu geri ALMAZ.
     * Bu tabloya yazan test kendi çöpünü kendisi temizlemek zorunda.
     */
    protected function tearDown(): void
    {
        DB::table('gunluk_gorev_log')
            ->where('gorev', self::GOREV)
            ->whereIn('tarih', [$this->gun->toDateString(), self::PLANSIZ_GUN])
            ->delete();

        parent::tearDown();
    }

    public function test_rapor_dogru_bolumlere_ayirir(): void
    {
        $marka = $this->marka('Rapor Testi');
        $g     = $this->gun->dayOfWeekIso;

        $tamam = $this->hesap($marka, 'Instagram', [$g => 2]);
        $eksik = $this->hesap($marka, 'LinkedIn',  [$g => 3]);
        $ertel = $this->hesap($marka, 'Facebook',  [$g => 1]);
        $iptal = $this->hesap($marka, 'TikTok',    [$g => 2]);

        $this->paylasim($tamam);
        $this->paylasim($tamam);
        $this->paylasim($eksik);

        $this->gunNotu($ertel, SosyalMedyaGunNotu::TIP_ERTELENDI, 'görsel onayı yok');
        $this->gunNotu($iptal, SosyalMedyaGunNotu::TIP_IPTAL, 'kampanya iptal');

        $v = $this->takip->gunSonuVerisi($this->gun);

        $this->assertSame(['Instagram'], $this->platformlar($v['tamamlanan'], $marka->id));
        $this->assertSame(['LinkedIn'],  $this->platformlar($v['eksik'], $marka->id));
        $this->assertSame(['Facebook'],  $this->platformlar($v['ertelenen'], $marka->id));
        $this->assertSame(['TikTok'],    $this->platformlar($v['iptal'], $marka->id));
    }

    /**
     * Paylaşım tarihinden SONRA işaretlenen kayıt "geç" sayılır. Raporun
     * asıl değeri bu: sayı tutuyor olsa bile iş zamanında yapılmamış olabilir.
     */
    public function test_sonradan_isaretlenen_paylasim_gec_damgasi_alir(): void
    {
        $marka = $this->marka('Geç Testi');
        $h     = $this->hesap($marka, 'YouTube', [$this->gun->dayOfWeekIso => 1]);

        SosyalMedyaPaylasim::create([
            'hesap_id' => $h->id,
            'tarih' => $this->gun->toDateString(),
            'isaretlendi_at' => $this->gun->copy()->addDay()->setTime(9, 30),
        ]);

        $v      = $this->takip->gunSonuVerisi($this->gun);
        $satir  = collect($v['tamamlanan'])->firstWhere('platform', 'YouTube');

        $this->assertTrue($satir['gec']);
    }

    public function test_konu_satiri_eksik_sayisini_gosterir(): void
    {
        $marka = $this->marka('Konu Testi');
        $this->hesap($marka, 'LinkedIn', [$this->gun->dayOfWeekIso => 3]);

        $v = $this->takip->gunSonuVerisi($this->gun);

        $this->assertStringContainsString('eksik', SosyalMedyaRaporu::konu($v, $this->gun));
    }

    public function test_mail_govdesi_bolumleri_icerir(): void
    {
        $marka = $this->marka('Gövde Testi');
        $h     = $this->hesap($marka, 'LinkedIn', [$this->gun->dayOfWeekIso => 3]);
        $this->paylasim($h);

        $v     = $this->takip->gunSonuVerisi($this->gun);
        $govde = SosyalMedyaRaporu::mailGovdesi($v, $this->gun);

        $this->assertStringContainsString('Eksik kalanlar', $govde);
        $this->assertStringContainsString('Gövde Testi', $govde);
        $this->assertStringContainsString('1 / 3', $govde);
    }

    /** Marka adı HTML olarak yorumlanmamalı */
    public function test_marka_adi_kacislanir(): void
    {
        $marka = $this->marka('<script>alert(1)</script>');
        $h     = $this->hesap($marka, 'LinkedIn', [$this->gun->dayOfWeekIso => 2]);
        $this->paylasim($h);

        $govde = SosyalMedyaRaporu::mailGovdesi($this->takip->gunSonuVerisi($this->gun), $this->gun);

        $this->assertStringNotContainsString('<script>', $govde);
        $this->assertStringContainsString('&lt;script&gt;', $govde);
    }

    /**
     * ÇİFT MAİL KORUMASI.
     *
     * Ödeme hatırlatmasında yaşandı: cPanel'de iki "schedule:run" satırı
     * vardı ve her mail iki kez gitti. Çözüm önce-yeri-kap: log satırı
     * yazılamazsa gönderim yapılmaz.
     */
    public function test_ayni_gun_ikinci_kez_gonderilemez(): void
    {
        $satir = ['gorev' => self::GOREV, 'tarih' => $this->gun->toDateString(), 'created_at' => now()];

        $this->assertTrue(DB::table('gunluk_gorev_log')->insertOrIgnore($satir) > 0,
            'ilk gönderim yeri kapatabilmeli');

        $this->assertSame(0, DB::table('gunluk_gorev_log')->insertOrIgnore($satir),
            'ikinci çalışma reddedilmeli — yoksa rapor iki kez gider');
    }

    /**
     * Planı olmayan günde mail gitmemeli VE günün kilidi harcanmamalı.
     *
     * İkinci kısım gerçek bir hatayı yakaladı: komut kilidi içerik
     * kontrolünden ÖNCE kapıyordu. Plan henüz girilmemişken çalışan bir
     * komut günü "gönderilmiş" işaretliyor, akşamki gerçek gönderim
     * atlanıyordu. Testler arka arkaya koşunca ortaya çıktı.
     */
    public function test_komut_plansiz_gunde_mail_gondermez(): void
    {
        $this->artisan('sosyal-medya:gun-sonu', ['--tarih' => self::PLANSIZ_GUN])
            ->expectsOutputToContain('planlanmış paylaşım yok')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('gunluk_gorev_log')
            ->where('gorev', self::GOREV)->where('tarih', self::PLANSIZ_GUN)->count(),
            'gönderilecek bir şey yokken günün kilidi harcanmamalı');

        // Aynı komut ikinci kez de aynı cevabı vermeli (kilide takılmamalı)
        $this->artisan('sosyal-medya:gun-sonu', ['--tarih' => self::PLANSIZ_GUN])
            ->expectsOutputToContain('planlanmış paylaşım yok')
            ->assertExitCode(0);
    }

    public function test_kuru_calisma_log_satiri_yazmaz(): void
    {
        $marka = $this->marka('Kuru Testi');
        $h     = $this->hesap($marka, 'Instagram', [$this->gun->dayOfWeekIso => 1]);
        $this->paylasim($h);

        $this->artisan('sosyal-medya:gun-sonu', ['--kuru' => true])->assertExitCode(0);

        $this->assertSame(0, DB::table('gunluk_gorev_log')
            ->where('gorev', self::GOREV)->where('tarih', $this->gun->toDateString())->count(),
            'kuru çalışma günü "gönderilmiş" işaretlememeli');
    }

    /* ───────────────────────────────────────────── */

    private function paylasim($hesap): void
    {
        SosyalMedyaPaylasim::create([
            'hesap_id' => $hesap->id,
            'tarih' => $this->gun->toDateString(),
            'isaretlendi_at' => $this->gun->copy()->setTime(10, 0),
        ]);
    }

    private function gunNotu($hesap, string $tip, string $sebep): void
    {
        SosyalMedyaGunNotu::create([
            'hesap_id' => $hesap->id,
            'tarih' => $this->gun->toDateString(),
            'tip' => $tip,
            'ertelendi_tarih' => $tip === SosyalMedyaGunNotu::TIP_ERTELENDI
                ? $this->gun->copy()->addDays(2)->toDateString() : null,
            'sebep' => $sebep,
            'isaretlendi_at' => now(),
        ]);
    }

    /**
     * Rapor tüm markaları kapsıyor (veritabanında başka kayıtlar da var);
     * yalnızca bu testin markasına ait satırları süz.
     */
    private function platformlar(array $satirlar, int $markaId): array
    {
        $marka = \App\Models\CRM\SosyalMedyaKayit::find($markaId);

        return collect($satirlar)
            ->where('marka', $marka->baslik)
            ->pluck('platform')->sort()->values()->all();
    }
}
