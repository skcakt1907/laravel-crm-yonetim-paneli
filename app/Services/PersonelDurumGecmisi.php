<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERSONEL DURUM GEÇMİŞİ — raporlama katmanı.
 *
 * `personel_durumlari` tablosu her durum değişikliğinde yeni satır açıyor;
 * önceki satırın `bitis` alanı dolduruluyor. Bu sınıf o ham kayıtları
 * okunabilir bir rapora çeviriyor: "bu hafta kim kaç saat çekimdeydi",
 * "dün kaçta izne çıktı".
 *
 * ── ARALIĞA KIRPMA ────────────────────────────────────────────────────
 * Bir durum aralığın dışında başlayıp içinde bitebilir (ya da tersi).
 * Süre hesaplanırken kayıt ARALIĞA KIRPILIR: Pazar akşamı başlayıp
 * Pazartesi biten "Mesai dışı" kaydı, haftalık rapora yalnızca Pazartesi'ye
 * düşen kısmıyla girer. Kırpmasaydık bir günlük raporda 24 saati aşan
 * süreler çıkardı ve rapor anlamsız olurdu.
 *
 * ── AÇIK KAYIT ────────────────────────────────────────────────────────
 * bitis = NULL, durumun hâlâ sürdüğü anlamına gelir. Süresi ŞU ANA kadar
 * sayılır, ama aralığın sonunu geçemez — geçmiş bir haftanın raporunda
 * hâlâ açık duran bir kayıt, o haftanın sonuna kadar sayılır.
 */
class PersonelDurumGecmisi
{
    /** Rapor tek seferde en fazla bu kadar satır gösterir. */
    public const SATIR_SINIRI = 500;

    /**
     * Aralıktaki ham kayıtlar (en yeni önce).
     *
     * @param  int|null  $yoneticiId  null ise herkes
     * @return \Illuminate\Support\Collection
     */
    public static function kayitlar(Carbon $bas, Carbon $son, ?int $yoneticiId = null)
    {
        if (! Schema::hasTable('personel_durumlari')) {
            return collect();
        }

        $q = DB::table('personel_durumlari as d')
            ->join('personel_durum_tipleri as t', 't.id', '=', 'd.durum_tipi_id')
            ->leftJoin('yoneticiler as y', 'y.id', '=', 'd.yonetici_id')
            ->leftJoin('yoneticiler as dg', 'dg.id', '=', 'd.degistiren_id')
            /*
             * Aralikla KESISEN kayitlar: aralik icinde baslamis olmasi
             * gerekmiyor. Onceki gunden devam eden bir durum da rapora
             * girmeli, yoksa gunun ilk saatleri bos gorunur.
             */
            ->where('d.baslangic', '<', $son)
            ->where(function ($w) use ($bas) {
                $w->whereNull('d.bitis')->orWhere('d.bitis', '>', $bas);
            })
            ->select(
                'd.id', 'd.yonetici_id', 'd.not', 'd.baslangic', 'd.bitis',
                't.ad as durum_adi', 't.emoji as durum_emoji', 't.renk as durum_renk',
                'y.adi as kisi_adi', 'y.kullaniciadi as kisi_kullanici',
                'dg.adi as degistiren_adi', 'd.degistiren_id'
            )
            ->orderByDesc('d.baslangic')
            ->orderByDesc('d.id')
            ->limit(self::SATIR_SINIRI);

        if ($yoneticiId) {
            $q->where('d.yonetici_id', $yoneticiId);
        }

        return $q->get()->map(fn ($k) => self::zenginlestir($k, $bas, $son));
    }

    /**
     * Kayda hesaplanmış alanları ekler: aralığa kırpılmış süre ve
     * kaydın hâlâ sürüp sürmediği.
     */
    private static function zenginlestir(object $k, Carbon $bas, Carbon $son): object
    {
        $basladi = Carbon::parse($k->baslangic);
        $bitti   = $k->bitis ? Carbon::parse($k->bitis) : null;

        $k->suruyor = $bitti === null;

        // Aralığa kırp
        $kBas = $basladi->greaterThan($bas) ? $basladi : $bas->copy();
        $kSon = $bitti ?: Carbon::now();
        if ($kSon->greaterThan($son)) {
            $kSon = $son->copy();
        }

        // (int) sart: Carbon diffInMinutes ondalik dondurur, ondalik
        // dakika hem ekranda hem toplamlarda anlamsiz.
        $k->dakika = $kSon->greaterThan($kBas) ? (int) $kBas->diffInMinutes($kSon) : 0;
        $k->sure   = self::sureMetni($k->dakika);

        return $k;
    }

    /**
     * Kişi bazında özet: her durumda toplam kaç dakika geçirilmiş.
     *
     * @param  \Illuminate\Support\Collection  $kayitlar
     * @return array<int, array{kisi: string, toplam: int, toplam_metin: string, durumlar: array}>
     */
    public static function ozet($kayitlar): array
    {
        $ozet = [];

        foreach ($kayitlar as $k) {
            $kid = (int) $k->yonetici_id;

            if (! isset($ozet[$kid])) {
                $ozet[$kid] = [
                    'kisi'     => $k->kisi_adi ?: $k->kisi_kullanici ?: ('#' . $kid),
                    'toplam'   => 0,
                    'durumlar' => [],
                ];
            }

            $ad = $k->durum_adi;

            if (! isset($ozet[$kid]['durumlar'][$ad])) {
                $ozet[$kid]['durumlar'][$ad] = [
                    'ad' => $ad, 'emoji' => $k->durum_emoji, 'renk' => $k->durum_renk,
                    'dakika' => 0, 'adet' => 0,
                ];
            }

            $ozet[$kid]['durumlar'][$ad]['dakika'] += $k->dakika;
            $ozet[$kid]['durumlar'][$ad]['adet']++;
            $ozet[$kid]['toplam'] += $k->dakika;
        }

        foreach ($ozet as $kid => $satir) {
            // En çok vakit geçirilen durum üstte
            uasort($ozet[$kid]['durumlar'], fn ($a, $b) => $b['dakika'] <=> $a['dakika']);

            foreach ($ozet[$kid]['durumlar'] as $ad => $d) {
                $ozet[$kid]['durumlar'][$ad]['sure'] = self::sureMetni($d['dakika']);
            }

            $ozet[$kid]['toplam_metin'] = self::sureMetni($satir['toplam']);
        }

        // Toplam süresi en yüksek kişi üstte
        uasort($ozet, fn ($a, $b) => $b['toplam'] <=> $a['toplam']);

        return $ozet;
    }

    /**
     * Dakikayı okunur süreye çevirir: 90 -> "1sa 30dk".
     *
     * Saniye gösterilmiyor: durum değişiklikleri elle yapılıyor, saniye
     * hassasiyeti bilgi vermiyor, tabloyu okunmaz yapıyor.
     */
    public static function sureMetni(int $dakika): string
    {
        if ($dakika <= 0) {
            return '—';
        }

        $saat = intdiv($dakika, 60);
        $kalan = $dakika % 60;

        if ($saat === 0) {
            return $kalan . 'dk';
        }

        return $kalan === 0 ? $saat . 'sa' : $saat . 'sa ' . $kalan . 'dk';
    }

    /**
     * Rapor aralığını çözer. Geçersiz/eksik parametrede bu haftaya düşer.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}  [başlangıç, bitiş, etiket]
     */
    public static function aralik(?string $bas, ?string $son): array
    {
        $b = self::tariheCevir($bas);
        $s = self::tariheCevir($son);

        if (! $b || ! $s) {
            // Varsayilan: icinde bulunulan hafta (Pazartesi - Pazar)
            return [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
                'Bu hafta',
            ];
        }

        // Ters girilmişse düzelt — kullanıcı tarihleri karıştırınca
        // rapor boş çıkıyordu, sebebi de görünmüyordu.
        if ($b->greaterThan($s)) {
            [$b, $s] = [$s, $b];
        }

        return [$b->startOfDay(), $s->endOfDay(), ''];
    }

    private static function tariheCevir(?string $deger): ?Carbon
    {
        if (! $deger) {
            return null;
        }

        try {
            return Carbon::parse($deger);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
