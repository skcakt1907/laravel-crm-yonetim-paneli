<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KrediService
{
    /**
     * Annuity (eşit taksit) formülü ile aylık taksit tutarı hesaplar.
     * M = P × r × (1+r)^n / ((1+r)^n − 1)
     */
    public static function aylikTaksit(float $anaPara, float $aylikFaizYuzde, int $vadeAy): float
    {
        $vadeAy = max(1, $vadeAy);
        $r = $aylikFaizYuzde / 100;
        if ($r <= 0) {
            return round($anaPara / $vadeAy, 2);
        }
        $faktor = pow(1 + $r, $vadeAy);
        return round($anaPara * $r * $faktor / ($faktor - 1), 2);
    }

    /**
     * Yeni kredi aç + taksit planını otomatik oluştur.
     */
    public static function krediAc(
        int $uyeId,
        ?int $musteriId,
        float $anaPara,
        float $aylikFaizYuzde,
        int $vadeAy,
        ?string $baslangicTarihi = null,
        ?string $aciklama = null,
        ?int $olusturanId = null
    ): int {
        $baslangic = $baslangicTarihi ? Carbon::parse($baslangicTarihi) : Carbon::today();
        $bitis = $baslangic->copy()->addMonths($vadeAy);
        $aylik = self::aylikTaksit($anaPara, $aylikFaizYuzde, $vadeAy);
        $toplam = round($aylik * $vadeAy, 2);

        $krediNo = 'KRD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

        $krediId = DB::table('musteri_krediler')->insertGetId([
            'uye_id'             => $uyeId,
            'musteri_id'         => $musteriId,
            'kredi_no'           => $krediNo,
            'ana_para'           => $anaPara,
            'faiz_orani'         => $aylikFaizYuzde,
            'vade_ay'            => $vadeAy,
            'aylik_taksit'       => $aylik,
            'toplam_geri_odeme'  => $toplam,
            'odenen_tutar'       => 0,
            'kalan_borc'         => $toplam,
            'baslangic_tarihi'   => $baslangic->toDateString(),
            'bitis_tarihi'       => $bitis->toDateString(),
            'durum'              => 'aktif',
            'aciklama'           => $aciklama,
            'olusturan_id'       => $olusturanId,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Taksit planı
        $taksitler = [];
        for ($i = 1; $i <= $vadeAy; $i++) {
            $taksitler[] = [
                'kredi_id'      => $krediId,
                'sira'          => $i,
                'vade_tarihi'   => $baslangic->copy()->addMonths($i)->toDateString(),
                'taksit_tutari' => $aylik,
                'odenen_tutar'  => 0,
                'durum'         => 'bekliyor',
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        DB::table('musteri_kredi_taksitleri')->insert($taksitler);

        return $krediId;
    }

    /**
     * Bir taksite ödeme yap.
     */
    public static function taksitOde(int $taksitId, float $tutar, ?string $tarih = null, ?string $not = null): bool
    {
        $taksit = DB::table('musteri_kredi_taksitleri')->where('id', $taksitId)->first();
        if (!$taksit) return false;

        $yeniOdenen = (float) $taksit->odenen_tutar + $tutar;
        $tam = $yeniOdenen >= (float) $taksit->taksit_tutari;
        $durum = $tam ? 'odendi' : 'kismi';

        DB::table('musteri_kredi_taksitleri')->where('id', $taksitId)->update([
            'odenen_tutar' => $yeniOdenen,
            'odeme_tarihi' => $tarih ?: now()->toDateString(),
            'durum'        => $durum,
            'not'          => $not,
            'updated_at'   => now(),
        ]);

        // Kredi toplamlarını güncelle
        self::krediToplamGuncelle((int) $taksit->kredi_id);
        return true;
    }

    /**
     * Kredinin tüm taksitlerinden hesaplayıp odenen/kalan'ı tazele.
     */
    public static function krediToplamGuncelle(int $krediId): void
    {
        $sum = DB::table('musteri_kredi_taksitleri')->where('kredi_id', $krediId)
            ->selectRaw('SUM(odenen_tutar) AS odenen, SUM(taksit_tutari) AS toplam')
            ->first();

        $odenen = (float) ($sum->odenen ?? 0);
        $toplam = (float) ($sum->toplam ?? 0);
        $kalan = max(0, $toplam - $odenen);

        $durum = 'aktif';
        if ($kalan <= 0.009) {
            $durum = 'kapandi';
        } else {
            // Gecikme kontrolü: vadesi geçmiş henüz ödenmemiş taksit var mı
            $geciken = DB::table('musteri_kredi_taksitleri')
                ->where('kredi_id', $krediId)
                ->whereIn('durum', ['bekliyor', 'kismi'])
                ->where('vade_tarihi', '<', now()->toDateString())
                ->exists();
            if ($geciken) $durum = 'gecikmede';
        }

        DB::table('musteri_krediler')->where('id', $krediId)->update([
            'odenen_tutar' => $odenen,
            'kalan_borc'   => $kalan,
            'durum'        => $durum,
            'updated_at'   => now(),
        ]);
    }
}
