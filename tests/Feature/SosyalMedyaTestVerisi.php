<?php

namespace Tests\Feature;

use App\Models\CRM\SosyalMedyaHesap;
use App\Models\CRM\SosyalMedyaKayit;
use App\Models\CRM\SosyalMedyaPlan;
use App\Models\Yonetici;

/**
 * Sosyal medya testleri için ortak kurulum.
 *
 * Factory yazılmadı: bu tablolar canlıdan gelen bir şema üzerinde duruyor
 * ve projede hiç factory yok. Doğrudan create() daha dürüst — testin
 * gerçekten hangi kolonları doldurduğu görünüyor.
 */
trait SosyalMedyaTestVerisi
{
    protected function marka(string $ad = 'Test Markası'): SosyalMedyaKayit
    {
        return SosyalMedyaKayit::create(['baslik' => $ad]);
    }

    /**
     * Bir platform hesabı.
     *
     * $hedefler: [gun => adet] — verilirse haftalık plan da kurulur.
     */
    protected function hesap(SosyalMedyaKayit $marka, string $platform,
                             array $hedefler = [], ?int $sorumluId = null): SosyalMedyaHesap
    {
        $hesap = SosyalMedyaHesap::create([
            'kayit_id'   => $marka->id,
            'bolum'      => SosyalMedyaHesap::BOLUM_SOSYAL,
            'platform'   => $platform,
            'sorumlu_id' => $sorumluId,
        ]);

        foreach ($hedefler as $gun => $adet) {
            SosyalMedyaPlan::create([
                'hesap_id' => $hesap->id, 'gun' => $gun,
                'hedef_adet' => $adet, 'aktif' => true,
            ]);
        }

        return $hesap;
    }

    /** Panelde oturum açmış gibi davran (middleware'siz controller testleri için) */
    protected function olarakGir(Yonetici $kim): void
    {
        session(['admin_id' => $kim->id, 'admin_rol' => $kim->rol]);
    }

    protected function patron(): Yonetici
    {
        $p = Yonetici::where('rol', Yonetici::ROL_PATRON)->where('durum', 1)->first();

        $this->assertNotNull($p, 'Testler için aktif bir patron kullanıcısı gerekiyor.');

        return $p;
    }
}
