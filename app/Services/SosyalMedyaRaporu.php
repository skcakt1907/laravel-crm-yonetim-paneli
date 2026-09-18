<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * SOSYAL MEDYA — gün sonu rapor maili
 *
 * Veriyi kendisi hesaplamaz; SosyalMedyaTakip::gunSonuVerisi() sonucunu
 * HTML'e çevirir. Hesap mantığı tek yerde kalsın diye — panelde görünen
 * sayılarla maildekiler birbirini tutmak zorunda.
 *
 * Gövde, EmailNotificationService::send() tarafından ortak şablona sarılır;
 * burada yalnızca iç içerik üretilir.
 */
class SosyalMedyaRaporu
{
    /**
     * Raporu alacak yöneticiler (kullaniciadi).
     *
     * Finans raporunun alıcı listesinden BİLEREK ayrı: bu rapor sosyal medya
     * işini takip edenlere gidiyor, muhasebe listesine değil. Seda Hanım
     * burada var, finans listesinde yok.
     */
    public const ALICILAR = ['nurselinan', 'NesimiAtes', 'dilanatescom', 'Seda'];

    public static function alicilar(): array
    {
        try {
            // Panelden secilenler oncelikli
            if (\Illuminate\Support\Facades\Schema::hasTable('sosyal_medya_rapor_alicilari')) {
                $secilen = DB::table('sosyal_medya_rapor_alicilari as a')
                    ->join('yoneticiler as y', 'y.id', '=', 'a.yonetici_id')
                    ->where('y.durum', 1)
                    ->whereNotNull('y.email')->where('y.email', '!=', '')
                    ->pluck('y.email')->unique()->values()->all();

                if (! empty($secilen)) {
                    return $secilen;
                }
            }

            // Panelden hic secim yapilmamissa koddaki liste -- rapor
            // kurulumdan hemen sonra da gitsin diye
            return DB::table('yoneticiler')
                ->whereIn('kullaniciadi', self::ALICILAR)
                ->where('durum', 1)
                ->whereNotNull('email')->where('email', '!=', '')
                ->pluck('email')->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Panelde secili olan alicilarin yonetici id'leri */
    public static function seciliIdler(): array
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('sosyal_medya_rapor_alicilari')) {
                return [];
            }
            return DB::table('sosyal_medya_rapor_alicilari')->pluck('yonetici_id')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Gün sonu raporunun varsayılan gönderim saati */
    public const VARSAYILAN_SAAT = '19:05';

    /**
     * Raporun gideceği saat (HH:MM).
     *
     * routes/console.php her artisan çağrısında bunu okur; veritabanına
     * ulaşılamazsa (kurulum anı, bakım) varsayılana düşer — yoksa zamanlayıcı
     * komple çöker ve panelin DİĞER görevleri de çalışmaz.
     *
     * Biçim doğrulaması burada da yapılıyor: tabloya elle bozuk bir değer
     * girilirse dailyAt() patlar, o da aynı sonucu doğurur.
     */
    public static function raporSaati(): string
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('sosyal_medya_ayarlar')) {
                return self::VARSAYILAN_SAAT;
            }

            $saat = DB::table('sosyal_medya_ayarlar')
                ->where('anahtar', 'rapor_saati')->value('deger');

            return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $saat)
                ? $saat
                : self::VARSAYILAN_SAAT;
        } catch (\Throwable $e) {
            return self::VARSAYILAN_SAAT;
        }
    }

    /** Konu satırı — eksik sayısı başlıkta görünsün, mail açılmadan anlaşılsın */
    public static function konu(array $v, Carbon $tarih): string
    {
        $eksik = count($v['eksik']);

        return ($eksik > 0 ? '⚠️ ' : '✅ ')
            . 'Sosyal Medya Gün Sonu — ' . $tarih->format('d.m.Y')
            . ($eksik > 0 ? ' (' . $eksik . ' eksik)' : ' (eksik yok)');
    }

    public static function mailGovdesi(array $v, Carbon $tarih): string
    {
        $planlanan   = array_merge($v['tamamlanan'], $v['eksik']);
        $hedefToplam = array_sum(array_column($planlanan, 'hedef'));
        $yapilanTop  = array_sum(array_column($planlanan, 'yapilan'));

        $h = '';

        // ── EKSİK UYARISI ────────────────────────────────────────
        // Mailin EN ÜSTÜNDE, özetten bile önce. Raporu açan kişi başka
        // hiçbir şey okumasa bile bu bandı görmeli; istek listesindeki
        // "belirgin uyarı vurgusu" maddesi bu.
        if (count($v['eksik']) > 0) {
            $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
                . 'style="margin:0 0 18px"><tr>'
                . '<td style="background:#9C2B2B;padding:14px 16px;border-radius:10px">'
                . '<div style="font-size:15px;font-weight:800;color:#ffffff;letter-spacing:.2px">'
                . '⚠️ DİKKAT: Eksik Paylaşımlar Bulunmaktadır!</div>'
                . '<div style="font-size:13px;color:#f6dcdc;margin-top:4px">'
                . count($v['eksik']) . ' platformda bugünün paylaşımı tamamlanmadı. '
                . 'Ayrıntı aşağıdaki <strong style="color:#ffffff">Eksik kalanlar</strong> bölümünde.'
                . '</div></td></tr></table>';
        }

        $h .= '<p style="margin:0 0 16px;font-size:14px;color:#5b6168">'
           . $tarih->translatedFormat('d F Y, l') . ' günü sosyal medya paylaşım özeti.</p>';

        $stat = function (string $b, $d, string $renk, string $zemin) {
            return '<td style="padding:14px 8px;background:' . $zemin . ';border-radius:10px;text-align:center">'
                 . '<div style="font-size:11px;font-weight:800;color:' . $renk . ';text-transform:uppercase">' . $b . '</div>'
                 . '<div style="font-size:19px;font-weight:800;color:' . $renk . ';margin-top:3px">' . $d . '</div></td>';
        };

        $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px"><tr>'
            . $stat('Eksik', count($v['eksik']), '#9C2B2B', '#F8D7DA') . '<td style="width:8px"></td>'
            . $stat('Tamamlanan', count($v['tamamlanan']), '#1E6B2F', '#E2EFDA') . '<td style="width:8px"></td>'
            . $stat('Paylaşım', $yapilanTop . ' / ' . $hedefToplam, '#1F4E78', '#D9E1F2') . '<td style="width:8px"></td>'
            . $stat('Ertelenen', count($v['ertelenen']), '#8A6200', '#FFF2CC') . '<td style="width:8px"></td>'
            . $stat('İptal', count($v['iptal']), '#5b6168', '#ECECEC')
            . '</tr></table>';

        // Eksikler EN ÜSTTE: raporun var olma sebebi bu. Boşsa da yazılır,
        // "eksik yok" bilgisi de bir haber.
        $h .= self::bolum('Eksik kalanlar', $v['eksik'], '#9C2B2B', 'Bugün eksik paylaşım yok. 👏');
        $h .= self::bolum('Ertelenenler', $v['ertelenen'], '#8A6200');
        $h .= self::bolum('İptal edilenler', $v['iptal'], '#5b6168');
        $h .= self::bolum('Tamamlananlar', $v['tamamlanan'], '#1E6B2F', 'Bugün tamamlanan paylaşım yok.');

        // Bugüne ERTELENMİŞ olanlar: hedefe eklenmez (plan mantığı bozulmasın),
        // ama uzmanın bugün ayrıca yapması gereken işler olduğu için görünür.
        if (! empty($v['bugune_ertelenen'])) {
            $sat = '';
            foreach ($v['bugune_ertelenen'] as $r) {
                $sat .= '<tr>'
                    . self::hucre($r['marka'] . ' · ' . $r['platform'])
                    . self::hucre($r['eski_tarih'] . ' tarihinden ertelendi', '#5b6168')
                    . self::hucre($r['sebep'] ?: '—', '#5b6168')
                    . '</tr>';
            }
            $h .= self::baslik('Bugüne ertelenmiş olanlar', '#1F4E78')
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
                . 'style="border:1px solid #f0efe6;border-radius:8px">' . $sat . '</table>';
        }

        $h .= '<p style="margin:22px 0 0;font-size:11px;color:#9aa0a6">'
            . 'Bu rapor her gün ' . self::raporSaati() . '&rsquo;te otomatik gönderilir. '
            . '&ldquo;geç&rdquo; etiketi, paylaşımın yapıldığı günden sonra işaretlendiğini gösterir.</p>';

        return $h;
    }

    /* ───────────────────────────────────────────────────────── */

    private static function baslik(string $metin, string $renk): string
    {
        return '<h3 style="font-size:13px;font-weight:800;color:' . $renk . ';margin:22px 0 8px;'
             . 'text-transform:uppercase;letter-spacing:.3px">' . $metin . '</h3>';
    }

    /**
     * Bir bölümün tablosu. $bosMesaj verilmezse boş bölüm hiç yazılmaz —
     * her gün "İptal edilenler: yok" satırı görmek raporu şişiriyor.
     */
    private static function bolum(string $baslik, array $satirlar, string $renk, ?string $bosMesaj = null): string
    {
        if (empty($satirlar)) {
            return $bosMesaj === null ? ''
                : self::baslik($baslik, $renk)
                  . '<p style="margin:0;font-size:13px;color:#9aa0a6">' . $bosMesaj . '</p>';
        }

        $sat = '';
        foreach ($satirlar as $r) {
            // "geç" = paylaşım tarihinden sonra işaretlenmiş
            $etiket = empty($r['gec']) ? ''
                : ' <span style="font-size:10px;background:#FFF2CC;color:#8A6200;'
                  . 'padding:1px 5px;border-radius:4px;font-weight:700">geç</span>';

            // Orta sütun: sebep varsa sebep (+ erteleme tarihi), yoksa paylaşım saatleri
            $orta = $r['sebep'] ?? '';
            if ($orta !== '' && ! empty($r['yeni_tarih'])) {
                $orta .= ' → ' . $r['yeni_tarih'];
            }
            if ($orta === '') {
                $orta = $r['saatler'] ? implode(', ', $r['saatler']) : '—';
            }

            $sat .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">'
                . '<strong>' . e($r['marka']) . '</strong> · ' . e($r['platform']) . $etiket . '</td>'
                . self::hucre($r['sorumlu'], '#5b6168')
                . self::hucre($orta, '#5b6168')
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px;'
                . 'text-align:right;font-weight:700;color:' . $renk . '">'
                . $r['yapilan'] . ' / ' . $r['hedef'] . '</td>'
                . '</tr>';
        }

        return self::baslik($baslik . ' (' . count($satirlar) . ')', $renk)
             . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
             . 'style="border:1px solid #f0efe6;border-radius:8px">' . $sat . '</table>';
    }

    private static function hucre(string $icerik, ?string $renk = null): string
    {
        return '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:'
             . ($renk ? '12px;color:' . $renk : '13px') . '">' . e($icerik) . '</td>';
    }
}
