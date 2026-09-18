<?php

namespace App\Support;

/**
 * DOMAIN KARŞILAŞTIRMA ANAHTARI.
 *
 * Aynı domain iki tabloda farklı yazılmış olabiliyor; listeyi ikiye
 * ayırırken ve kâr raporunda çift saymayı önlerken bu iki yazımın aynı
 * sayılması gerekiyor.
 *
 * NEDEN AYRI SINIF: Karşılaştırma hem PHP tarafında (koleksiyon süzme)
 * hem SQL tarafında (LOWER(TRIM(...))) yapılıyor. İkisi AYNI sonucu
 * vermek zorunda, yoksa bir domain her iki tabloda birden görünür.
 *
 * TÜRKÇE 'İ' TUZAĞI: Veride `besssİgorta.com` ve `cİhanturkhotel.com`
 * gibi, büyük İ (U+0130) ile girilmiş kayıtlar var. MySQL LOWER() bunu
 * düz `i` yapar; PHP mb_strtolower() ise `i` + birleşen nokta (U+0307)
 * üretir. İki sonuç eşit olmadığı için bu kayıtlar eşleşmeden kaçıyordu.
 * Bu yüzden İ ve I, küçültmeden ÖNCE elle `i`ye çevriliyor.
 *
 * Domain adları zaten ASCII'dir (Türkçe karakterli olanlar punycode ile
 * saklanır), dolayısıyla bu dönüşüm farklı iki domaini birbirine
 * karıştırmaz — yalnızca hatalı girilmiş olanı düzeltir.
 */
final class DomainAnahtari
{
    public static function of(?string $domain): string
    {
        $d = trim((string) $domain);

        if ($d === '') {
            return '';
        }

        // Türkçe büyük harfler, mb_strtolower'a girmeden düzeltilir
        $d = str_replace(['İ', 'I', 'ı'], 'i', $d);

        return mb_strtolower($d, 'UTF-8');
    }

    /** SQL tarafında aynı sonucu veren ifade — kolon adı verilir. */
    public static function sqlIfadesi(string $kolon): string
    {
        return "LOWER(TRIM(REPLACE(REPLACE({$kolon}, 'İ', 'i'), 'ı', 'i')))";
    }
}
