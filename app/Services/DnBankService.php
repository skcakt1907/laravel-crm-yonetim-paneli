<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * DN Bank — müşteri coin (kredi) cüzdanı.
 *
 * Kavram:
 *  - 1 coin = 1 TL.
 *  - Admin "DN Bank kredisi" açtığında: hem taksitli geri ödeme planı
 *    (musteri_krediler / musteri_kredi_taksitleri) oluşur, hem de ana_para kadar coin
 *    müşterinin harcanabilir bakiyesine (uyeler.dnbank_bakiye) yüklenir.
 *  - Müşteri checkout'ta coin harcar; harcama BORCU değiştirmez (borç ayrı deftere,
 *    taksitlere göre admin tarafından kapatılır).
 *
 * NOT: Bu sürüm KrediService'e BAĞIMLI DEĞİLDİR. Kredi + taksit oluşturma mantığı
 *      doğrudan bu servisin içindedir (musteri_krediler / musteri_kredi_taksitleri).
 */
class DnBankService
{
    /** Üyenin güncel DN Bank coin bakiyesi. (Kolon yoksa 0 — migration öncesi güvenli) */
    public static function bakiye(int $uyeId): float
    {
        if (!Schema::hasColumn('uyeler', 'dnbank_bakiye')) {
            return 0.0;
        }
        return (float) (DB::table('uyeler')->where('id', $uyeId)->value('dnbank_bakiye') ?? 0);
    }

    /**
     * DN Bank kredisi ver: taksit planı oluştur + coin yükle.
     * Döner: kredi_id.
     */
    public static function krediVer(
        int $uyeId,
        ?int $musteriId,
        float $anaPara,
        float $aylikFaizYuzde,
        int $vadeAy,
        ?string $baslangicTarihi = null,
        ?string $aciklama = null,
        ?int $olusturanId = null
    ): int {
        return DB::transaction(function () use ($uyeId, $musteriId, $anaPara, $aylikFaizYuzde, $vadeAy, $baslangicTarihi, $aciklama, $olusturanId) {
            // 1) Kredi + taksit planını kendi içinde oluştur (KrediService gerekmiyor)
            $krediId = self::krediAcInternal(
                $uyeId, $musteriId, $anaPara, $aylikFaizYuzde, $vadeAy,
                $baslangicTarihi, $aciklama, $olusturanId
            );

            // 2) ana_para kadar coin'i müşterinin harcanabilir bakiyesine yükle
            self::bakiyeEkle(
                $uyeId,
                $anaPara,
                'kredi_yukleme',
                $aciklama ? ('DN Bank kredisi: ' . $aciklama) : 'DN Bank kredisi yüklendi',
                $krediId,
                null,
                $olusturanId
            );

            return $krediId;
        });
    }

    /**
     * Kredi kaydı + aylık taksit planı oluşturur.
     * musteri_krediler ve musteri_kredi_taksitleri tablolarına yazar.
     * Basit aylık faiz: toplam = ana_para * (1 + (aylikFaizYuzde/100) * vadeAy).
     * Döner: kredi_id.
     */
    protected static function krediAcInternal(
        int $uyeId,
        ?int $musteriId,
        float $anaPara,
        float $aylikFaizYuzde,
        int $vadeAy,
        ?string $baslangicTarihi = null,
        ?string $aciklama = null,
        ?int $olusturanId = null
    ): int {
        $anaPara = round($anaPara, 2);
        $vadeAy  = max(1, (int) $vadeAy);
        $faiz    = max(0.0, (float) $aylikFaizYuzde);

        // Toplam geri ödeme (basit faiz, aylık)
        $toplamGeriOdeme = round($anaPara * (1 + ($faiz / 100) * $vadeAy), 2);
        $aylikTaksit     = round($toplamGeriOdeme / $vadeAy, 2);

        // Başlangıç / bitiş
        $baslangic = $baslangicTarihi
            ? Carbon::parse($baslangicTarihi)->startOfDay()
            : Carbon::now()->startOfDay();
        $bitis = (clone $baslangic)->addMonths($vadeAy);

        // Benzersiz kredi_no üret
        $krediNo = self::krediNoUret();

        $payload = [
            'uye_id'             => $uyeId,
            'musteri_id'         => $musteriId,
            'kredi_no'           => $krediNo,
            'ana_para'           => $anaPara,
            'faiz_orani'         => $faiz,
            'vade_ay'            => $vadeAy,
            'aylik_taksit'       => $aylikTaksit,
            'toplam_geri_odeme'  => $toplamGeriOdeme,
            'odenen_tutar'       => 0,
            'kalan_borc'         => $toplamGeriOdeme,
            'baslangic_tarihi'   => $baslangic->toDateString(),
            'bitis_tarihi'       => $bitis->toDateString(),
            'durum'              => 'aktif',
            'aciklama'           => $aciklama,
            'olusturan_id'       => $olusturanId,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];

        // Sadece tabloda gerçekten var olan kolonları yaz (şema farkına dayanıklı)
        $payload = array_filter(
            $payload,
            fn ($k) => Schema::hasColumn('musteri_krediler', $k),
            ARRAY_FILTER_USE_KEY
        );

        $krediId = (int) DB::table('musteri_krediler')->insertGetId($payload);

        // Taksitleri oluştur — son taksitte yuvarlama farkını düzelt
        if (Schema::hasTable('musteri_kredi_taksitleri')) {
            $taksitler = [];
            $toplananTaksit = 0.0;
            for ($i = 1; $i <= $vadeAy; $i++) {
                $tutar = ($i < $vadeAy)
                    ? $aylikTaksit
                    : round($toplamGeriOdeme - $toplananTaksit, 2); // son taksit = kalan
                $toplananTaksit = round($toplananTaksit + $tutar, 2);

                $satir = [
                    'kredi_id'     => $krediId,
                    'sira'         => $i,
                    'vade_tarihi'  => (clone $baslangic)->addMonths($i)->toDateString(),
                    'taksit_tutari'=> $tutar,
                    'odenen_tutar' => 0,
                    'durum'        => 'bekliyor',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
                $satir = array_filter(
                    $satir,
                    fn ($k) => Schema::hasColumn('musteri_kredi_taksitleri', $k),
                    ARRAY_FILTER_USE_KEY
                );
                $taksitler[] = $satir;
            }
            if (!empty($taksitler)) {
                DB::table('musteri_kredi_taksitleri')->insert($taksitler);
            }
        }

        return $krediId;
    }

    /** Benzersiz kredi numarası üret (DNB-YYYY-XXXXXX). */
    protected static function krediNoUret(): string
    {
        do {
            $no = 'DNB-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $varMi = DB::table('musteri_krediler')->where('kredi_no', $no)->exists();
        } while ($varMi);

        return $no;
    }

    /**
     * Taksit ödemesi kaydet (KrediService'siz, bağımsız).
     * Taksiti günceller (odenen_tutar/durum/odeme_tarihi) + krediyi yeniden hesaplar
     * (odenen_tutar/kalan_borc/durum). Coin bakiyesine DOKUNMAZ — geri ödeme ayrı defter.
     */
    public static function taksitOde(
        int $taksitId,
        float $tutar,
        ?string $odemeTarihi = null,
        ?string $not = null
    ): void {
        if (!Schema::hasTable('musteri_kredi_taksitleri')) {
            return;
        }

        DB::transaction(function () use ($taksitId, $tutar, $odemeTarihi, $not) {
            $taksit = DB::table('musteri_kredi_taksitleri')->where('id', $taksitId)->first();
            if (!$taksit) {
                return;
            }

            $tutar      = round(abs($tutar), 2);
            $yeniOdenen = round((float) ($taksit->odenen_tutar ?? 0) + $tutar, 2);
            $taksitTut  = (float) ($taksit->taksit_tutari ?? 0);

            $durum = 'kismi';
            if ($yeniOdenen + 0.0001 >= $taksitTut) {
                $durum      = 'odendi';
                $yeniOdenen = $taksitTut; // fazla ödemeyi taksit tutarına sabitle
            }

            $guncelle = [
                'odenen_tutar' => $yeniOdenen,
                'durum'        => $durum,
                'odeme_tarihi' => $odemeTarihi ?: now()->toDateString(),
                'updated_at'   => now(),
            ];
            if ($not !== null && Schema::hasColumn('musteri_kredi_taksitleri', 'not')) {
                $guncelle['not'] = $not;
            }
            $guncelle = array_filter(
                $guncelle,
                fn ($k) => Schema::hasColumn('musteri_kredi_taksitleri', $k),
                ARRAY_FILTER_USE_KEY
            );
            DB::table('musteri_kredi_taksitleri')->where('id', $taksitId)->update($guncelle);

            // Krediyi yeniden topla
            $krediId = (int) $taksit->kredi_id;
            if ($krediId && Schema::hasTable('musteri_krediler')) {
                $toplamOdenen = (float) DB::table('musteri_kredi_taksitleri')
                    ->where('kredi_id', $krediId)
                    ->sum('odenen_tutar');

                $kredi = DB::table('musteri_krediler')->where('id', $krediId)->first();
                if ($kredi) {
                    $toplamGeri = (float) ($kredi->toplam_geri_odeme ?? 0);
                    $kalan      = round(max(0, $toplamGeri - $toplamOdenen), 2);
                    $krediDurum = $kalan <= 0.0001 ? 'kapandi' : ($kredi->durum ?? 'aktif');
                    if ($krediDurum === 'kapandi' && ($kredi->durum ?? '') === 'iptal') {
                        $krediDurum = 'iptal'; // iptal edileni geri açma
                    }

                    $krediGuncelle = [
                        'odenen_tutar' => round($toplamOdenen, 2),
                        'kalan_borc'   => $kalan,
                        'durum'        => $krediDurum,
                        'updated_at'   => now(),
                    ];
                    $krediGuncelle = array_filter(
                        $krediGuncelle,
                        fn ($k) => Schema::hasColumn('musteri_krediler', $k),
                        ARRAY_FILTER_USE_KEY
                    );
                    DB::table('musteri_krediler')->where('id', $krediId)->update($krediGuncelle);
                }
            }
        });
    }

    /** Coin ekle (+). Döner: yeni bakiye. */
    public static function bakiyeEkle(
        int $uyeId,
        float $tutar,
        string $tip = 'duzeltme',
        ?string $aciklama = null,
        ?int $krediId = null,
        ?int $faturaId = null,
        ?int $yoneticiId = null
    ): float {
        $tutar  = round(abs($tutar), 2);
        $mevcut = self::bakiye($uyeId);
        $yeni   = round($mevcut + $tutar, 2);

        DB::table('uyeler')->where('id', $uyeId)->update(['dnbank_bakiye' => $yeni]);
        self::hareketEkle($uyeId, $tip, $tutar, $yeni, $aciklama, $krediId, $faturaId, $yoneticiId);

        return $yeni;
    }

    /** Coin harca (-). Yetersizse RuntimeException. Döner: yeni bakiye. */
    public static function bakiyeHarca(
        int $uyeId,
        float $tutar,
        ?string $aciklama = null,
        ?int $faturaId = null
    ): float {
        $tutar  = round(abs($tutar), 2);
        $mevcut = self::bakiye($uyeId);

        if ($mevcut + 0.0001 < $tutar) {
            throw new \RuntimeException('Yetersiz DN Bank bakiyesi.');
        }

        $yeni = round($mevcut - $tutar, 2);
        DB::table('uyeler')->where('id', $uyeId)->update(['dnbank_bakiye' => $yeni]);
        self::hareketEkle($uyeId, 'harcama', -$tutar, $yeni, $aciklama, null, $faturaId, null);

        return $yeni;
    }

    /**
     * Coin geri al / iade (örn. kredi silinince). Bakiye 0'ın altına inmez;
     * müşteri coin'i zaten harcadıysa sadece kalan kısmı geri alınır.
     */
    public static function bakiyeGeriAl(
        int $uyeId,
        float $tutar,
        ?string $aciklama = null,
        ?int $krediId = null,
        ?int $yoneticiId = null
    ): float {
        $tutar     = round(abs($tutar), 2);
        $mevcut    = self::bakiye($uyeId);
        $dusulecek = min($mevcut, $tutar);
        $yeni      = round($mevcut - $dusulecek, 2);

        DB::table('uyeler')->where('id', $uyeId)->update(['dnbank_bakiye' => $yeni]);
        self::hareketEkle($uyeId, 'iade', -$dusulecek, $yeni, $aciklama, $krediId, null, $yoneticiId);

        return $yeni;
    }

    protected static function hareketEkle(
        int $uyeId,
        string $tip,
        float $tutar,
        float $bakiyeSonra,
        ?string $aciklama,
        ?int $krediId,
        ?int $faturaId,
        ?int $yoneticiId
    ): void {
        if (!Schema::hasTable('dnbank_hareketleri')) {
            return;
        }

        DB::table('dnbank_hareketleri')->insert([
            'uye_id'       => $uyeId,
            'kredi_id'     => $krediId,
            'tip'          => $tip,
            'tutar'        => $tutar,
            'bakiye_sonra' => $bakiyeSonra,
            'aciklama'     => $aciklama,
            'fatura_id'    => $faturaId,
            'yonetici_id'  => $yoneticiId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
    /**
     * Krediyi onaylar ve (henüz yüklenmediyse) ana_para kadar coin yükler.
     * Çift yüklemeye karşı coin_yuklendi bayrağı ile korumalı.
     * Döner: true = coin yüklendi, false = zaten yüklüydü / kredi yok.
     */
    public static function krediOnayla(int $krediId, ?int $yoneticiId = null): bool
    {
        return DB::transaction(function () use ($krediId, $yoneticiId) {
            $kredi = DB::table('musteri_krediler')->where('id', $krediId)->lockForUpdate()->first();
            if (!$kredi) {
                return false;
            }

            // onay_durumu = onaylandi
            $guncelle = [];
            if (Schema::hasColumn('musteri_krediler', 'onay_durumu')) {
                $guncelle['onay_durumu'] = 'onaylandi';
            }

            // Zaten coin yüklendiyse tekrar yükleme
            $zatenYuklu = Schema::hasColumn('musteri_krediler', 'coin_yuklendi')
                ? (int) ($kredi->coin_yuklendi ?? 0) === 1
                : false;

            if (!$zatenYuklu) {
                $uyeId = (int) ($kredi->uye_id ?? 0);
                if ($uyeId > 0) {
                    self::bakiyeEkle(
                        $uyeId,
                        (float) $kredi->ana_para,
                        'kredi_yukleme',
                        'Kredi onaylandı, coin yüklendi: ' . ($kredi->kredi_no ?? ''),
                        $krediId,
                        null,
                        $yoneticiId
                    );
                }
                if (Schema::hasColumn('musteri_krediler', 'coin_yuklendi')) {
                    $guncelle['coin_yuklendi'] = 1;
                }
            }

            if (Schema::hasColumn('musteri_krediler', 'updated_at')) {
                $guncelle['updated_at'] = now();
            }
            if (!empty($guncelle)) {
                DB::table('musteri_krediler')->where('id', $krediId)->update($guncelle);
            }

            return !$zatenYuklu;
        });
    }

    /**
     * Krediyi reddeder (coin yüklenmez, durum iptal/reddedildi olur).
     */
    public static function krediReddet(int $krediId, ?int $yoneticiId = null): void
    {
        $guncelle = [];
        if (Schema::hasColumn('musteri_krediler', 'onay_durumu')) {
            $guncelle['onay_durumu'] = 'reddedildi';
        }
        if (Schema::hasColumn('musteri_krediler', 'durum')) {
            $guncelle['durum'] = 'iptal';
        }
        if (Schema::hasColumn('musteri_krediler', 'updated_at')) {
            $guncelle['updated_at'] = now();
        }
        if (!empty($guncelle)) {
            DB::table('musteri_krediler')->where('id', $krediId)->update($guncelle);
        }
    }

    /**
     * Müşteri kredi talebi oluşturur (onay_durumu=bekliyor, talep_eden=musteri).
     * Coin YÜKLENMEZ; admin onaylayınca yüklenir. Döner: kredi_id.
     */
    public static function krediTalepEt(
        int $uyeId,
        ?int $musteriId,
        float $anaPara,
        int $vadeAy,
        ?string $aciklama = null
    ): int {
        $krediId = self::krediAcInternal(
            $uyeId, $musteriId, $anaPara, 0.0, $vadeAy, null, $aciklama, null
        );

        $guncelle = [];
        if (Schema::hasColumn('musteri_krediler', 'onay_durumu')) {
            $guncelle['onay_durumu'] = 'bekliyor';
        }
        if (Schema::hasColumn('musteri_krediler', 'talep_eden')) {
            $guncelle['talep_eden'] = 'musteri';
        }
        if (Schema::hasColumn('musteri_krediler', 'coin_yuklendi')) {
            $guncelle['coin_yuklendi'] = 0;
        }
        if (!empty($guncelle)) {
            DB::table('musteri_krediler')->where('id', $krediId)->update($guncelle);
        }

        return $krediId;
    }
}