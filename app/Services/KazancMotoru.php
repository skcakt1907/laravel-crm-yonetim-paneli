<?php

namespace App\Services;

use App\Models\Bayi;
use App\Models\Oneri;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KAZANÇ MOTORU — tüm komisyon hesapları buradan geçer.
 *
 * Oranlar:
 *   İnternet Bayiliği     %15   (bayiler.komisyon_orani)
 *   DN Ofis Partnerliği   %30   (bayiler.komisyon_orani)
 *   Öner-Kazan            %50   → önerilen bayinin KOMİSYONUNUN yarısı, süresiz.
 *                                 Önerilen bayinin kazancı azalmaz, farkı biz karşılarız.
 *   Müşteri referansı     %10   → bağlanan müşterinin alım tutarının %10'u
 *
 * ÖNEMLİ: uyeler/bayiler tabloları MyISAM — transaction ÇALIŞMAZ.
 * Bu yüzden sıra şudur: önce deftere yaz (benzersiz indeks mükerreri engeller),
 * sonra DN Coin'e işle, sonra "işlendi" damgası vur. Coin adımı patlarsa
 * defterde dnbank_islendi=0 kalır; kayıp olmaz, sonradan görülür.
 */
class KazancMotoru
{
    /**
     * Bir bayi satışını işler.
     *  1) Bayinin kendi komisyonu
     *  2) Bu bayiyi öneren varsa, onun %50'si
     *
     * @param  float|null $uygulananOran  Bayi komisyonundan feragat ettiyse (indirim verdiyse) düşen oran
     * @return array  oluşturulan kazanç kayıtları
     */
    public static function satisIsle(
        int $bayiId,
        float $satisTutari,
        string $kaynakTip,
        string|int $kaynakId,
        ?float $uygulananOran = null,
        ?string $aciklama = null
    ): array {
        $sonuc = [];

        $bayi = Bayi::find($bayiId);
        if (!$bayi || !$bayi->uye_id || $satisTutari <= 0) {
            return $sonuc;
        }

        /* ── 1) Bayinin kendi komisyonu ── */
        $oran = $uygulananOran ?? (float) $bayi->komisyon_orani;
        $oran = max(0, min(100, $oran));
        $komisyon = round($satisTutari * $oran / 100, 2);

        if ($komisyon > 0) {
            $k = self::kazancYaz([
                'kazanan_uye_id'  => (int) $bayi->uye_id,
                'kazanan_bayi_id' => $bayi->id,
                'tip'             => 'bayi_komisyonu',
                'satis_bayi_id'   => $bayi->id,
                'kaynak_tip'      => $kaynakTip,
                'kaynak_id'       => (string) $kaynakId,
                'satis_tutari'    => $satisTutari,
                'oran'            => $oran,
                'tutar'           => $komisyon,
                'aciklama'        => $aciklama ?? ('Satış komisyonu (%' . rtrim(rtrim(number_format($oran, 2, '.', ''), '0'), '.') . ')'),
            ]);
            if ($k) $sonuc[] = $k;
        }

        /* ── 2) Öner-Kazan: bu bayiyi öneren bayi ── */
        $oneri = Oneri::onereniniBul('bayi', $bayi->id, Oneri::TIP_BAYI);
        if ($oneri && $oneri->gecerliMi() && $komisyon > 0) {
            $onerenUyeId = self::onerenUyeId($oneri);
            if ($onerenUyeId) {
                $payOrani = max(0, min(100, (float) $oneri->oran));
                $pay = round($komisyon * $payOrani / 100, 2);

                if ($pay > 0) {
                    $k = self::kazancYaz([
                        'kazanan_uye_id'  => $onerenUyeId,
                        'kazanan_bayi_id' => $oneri->oneren_tip === 'bayi' ? $oneri->oneren_id : null,
                        'tip'             => 'oner_kazan',
                        'oneri_id'        => $oneri->id,
                        'satis_bayi_id'   => $bayi->id,
                        'kaynak_tip'      => $kaynakTip,
                        'kaynak_id'       => (string) $kaynakId,
                        'satis_tutari'    => $satisTutari,
                        'oran'            => $payOrani,
                        'tutar'           => $pay,
                        'aciklama'        => 'Öner-Kazan: önerdiğiniz bayinin satışından pay (%' . (int) $payOrani . ')',
                    ]);
                    if ($k) {
                        $oneri->kazancEkle($pay);
                        $sonuc[] = $k;

                        // Önerene "önerdiğin bayi satış yaptı" bildirimi
                        try {
                            OneriEslestirici::aksiyonBildir(
                                'bayi', $bayi->id, 'yeni satış yaptı',
                                number_format($satisTutari, 2, ',', '.') . ' TL · payınız '
                                . number_format($pay, 2, ',', '.') . ' DN Coin'
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Öner-Kazan aksiyon bildirimi', ['hata' => $e->getMessage()]);
                        }
                    }
                }
            }
        }

        return $sonuc;
    }

    /**
     * Bir müşterinin alımını işler — referansla bağlanmışsa öneren %10 kazanır.
     */
    public static function musteriAlimiIsle(
        int $uyeId,
        float $tutar,
        string $kaynakTip,
        string|int $kaynakId,
        ?string $aciklama = null
    ): array {
        if ($tutar <= 0) return [];

        $oneri = Oneri::onereniniBul('uye', $uyeId, Oneri::TIP_MUSTERI);
        if (!$oneri || !$oneri->gecerliMi()) return [];

        $onerenUyeId = self::onerenUyeId($oneri);
        if (!$onerenUyeId || $onerenUyeId === $uyeId) return [];

        $oran = max(0, min(100, (float) $oneri->oran));
        $pay  = round($tutar * $oran / 100, 2);
        if ($pay <= 0) return [];

        $k = self::kazancYaz([
            'kazanan_uye_id'  => $onerenUyeId,
            'kazanan_bayi_id' => $oneri->oneren_tip === 'bayi' ? $oneri->oneren_id : null,
            'tip'             => 'musteri_referansi',
            'oneri_id'        => $oneri->id,
            'kaynak_tip'      => $kaynakTip,
            'kaynak_id'       => (string) $kaynakId,
            'satis_tutari'    => $tutar,
            'oran'            => $oran,
            'tutar'           => $pay,
            'aciklama'        => $aciklama ?? ('Referans kazancı: bağlı müşterinizin alımından %' . (int) $oran),
        ]);

        if ($k) {
            $oneri->kazancEkle($pay);
            return [$k];
        }

        return [];
    }

    /* ───────────── iç işler ───────────── */

    /** Öneri kaydındaki öneren tarafın üye id'si (coin oraya yatar) */
    private static function onerenUyeId(Oneri $oneri): ?int
    {
        if ($oneri->oneren_tip === 'uye') {
            return (int) $oneri->oneren_id;
        }

        $b = Bayi::find($oneri->oneren_id);
        return $b && $b->uye_id ? (int) $b->uye_id : null;
    }

    /**
     * Deftere yazar ve DN Coin'e işler.
     * Aynı satış/kişi/tür için ikinci kez çağrılırsa null döner (mükerrer engeli).
     */
    private static function kazancYaz(array $veri): ?object
    {
        // Zaten yazılmış mı?
        $mevcut = DB::table('kazanclar')
            ->where('kaynak_tip', $veri['kaynak_tip'])
            ->where('kaynak_id', $veri['kaynak_id'])
            ->where('kazanan_uye_id', $veri['kazanan_uye_id'])
            ->where('tip', $veri['tip'])
            ->first();

        if ($mevcut) {
            return null; // mükerrer — sessizce atla
        }

        try {
            $id = DB::table('kazanclar')->insertGetId(array_merge([
                'oneri_id'        => null,
                'kazanan_bayi_id' => null,
                'satis_bayi_id'   => null,
                'dnbank_islendi'  => 0,
                'durum'           => 'odendi',
                'created_at'      => now(),
                'updated_at'      => now(),
            ], $veri));
        } catch (\Throwable $e) {
            // Benzersiz indeks yakaladıysa mükerrer demektir
            Log::info('Kazanç yazılamadı (muhtemelen mükerrer)', ['veri' => $veri, 'hata' => $e->getMessage()]);
            return null;
        }

        // DN Coin'e işle
        try {
            DnBankService::bakiyeEkle(
                (int) $veri['kazanan_uye_id'],
                (float) $veri['tutar'],
                'komisyon',
                $veri['aciklama'] ?? 'Komisyon kazancı'
            );
            DB::table('kazanclar')->where('id', $id)->update(['dnbank_islendi' => 1, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Kazanç DN Coin\'e işlenemedi', ['kazanc_id' => $id, 'hata' => $e->getMessage()]);
            // Kayıt defterde durur (dnbank_islendi=0) — kayıp olmaz, sonradan düzeltilebilir
        }

        return DB::table('kazanclar')->find($id);
    }

    /** DN Coin'e işlenememiş kazançlar (elle kontrol için) */
    public static function islenmeyenler()
    {
        return DB::table('kazanclar')->where('dnbank_islendi', 0)->where('durum', 'odendi')->get();
    }
}
