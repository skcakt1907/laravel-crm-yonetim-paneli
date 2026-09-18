<?php

namespace App\Services;

use App\Models\Bayi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * PARA ÇEKME — DN Coin'i gerçek paraya çevirme akışı.
 *
 * ÖNCEKİ SORUNLAR (düzeltildi):
 *  1) Çekilebilir tutar bayi_satislar'dan hesaplanıyordu; Öner-Kazan ve müşteri
 *     referansı kazançları hiç görünmüyordu.
 *  2) Onay, DN Coin bakiyesinden DÜŞMÜYORDU → aynı para defalarca çekilebilirdi.
 *
 * ŞİMDİ: tek doğru kaynak DN Coin bakiyesidir. Onayda coin harcanır.
 */
class OdemeTalebi
{
    /** Varsayılan en az çekim tutarı (ayarlarda tanımlıysa o kullanılır) */
    public const VARSAYILAN_ALT_LIMIT = 100.0;

    public static function altLimit(): float
    {
        try {
            if (Schema::hasTable('ayarlar') && Schema::hasColumn('ayarlar', 'min_cekim_tutari')) {
                $v = (float) DB::table('ayarlar')->value('min_cekim_tutari');
                if ($v > 0) return $v;
            }
        } catch (\Throwable $e) {}

        return self::VARSAYILAN_ALT_LIMIT;
    }

    /** Bayinin çekebileceği tutar = DN Coin bakiyesi − bekleyen talepler */
    public static function cekilebilir(Bayi $bayi): float
    {
        if (!$bayi->uye_id) return 0.0;

        $bakiye = DnBankService::bakiye((int) $bayi->uye_id);

        $bekleyen = (float) DB::table('bayi_odeme_talepleri')
            ->where('bayi_id', $bayi->id)
            ->where('durum', 'beklemede')
            ->sum('tutar');

        return max(0, round($bakiye - $bekleyen, 2));
    }

    /**
     * Çekim talebi oluşturur.
     * @return array{0: bool, 1: string}
     */
    public static function talepEt(Bayi $bayi, float $tutar, array $banka = [], ?string $aciklama = null): array
    {
        $tutar = round($tutar, 2);

        if ($tutar <= 0) {
            return [false, 'Tutar sıfırdan büyük olmalıdır.'];
        }

        $alt = self::altLimit();
        if ($tutar < $alt) {
            return [false, 'En az çekim tutarı ' . number_format($alt, 2, ',', '.') . ' DN Coin.'];
        }

        if (!$bayi->uye_id) {
            return [false, 'Üye hesabınız bulunamadı, çekim yapılamaz.'];
        }

        $cekilebilir = self::cekilebilir($bayi);
        if ($tutar > $cekilebilir) {
            return [false, 'Çekilebilir bakiyeniz ' . number_format($cekilebilir, 2, ',', '.')
                . ' DN Coin. Bekleyen talepleriniz düşülmüştür.'];
        }

        try {
            $veri = [
                'bayi_id'      => $bayi->id,
                'talep_tutari' => $tutar,
                'tutar'        => $tutar,
                'aciklama'     => $aciklama,
                'banka_adi'    => $banka['banka_adi'] ?? $bayi->banka_adi,
                'iban'         => $banka['iban'] ?? $bayi->iban,
                'hesap_sahibi' => $banka['hesap_sahibi'] ?? $bayi->hesap_sahibi,
                'durum'        => 'beklemede',
                'talep_tarihi' => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
            $kolonlar = Schema::getColumnListing('bayi_odeme_talepleri');
            DB::table('bayi_odeme_talepleri')->insert(array_intersect_key($veri, array_flip($kolonlar)));

            return [true, 'Ödeme talebiniz alındı. Onaylandığında bakiyenizden düşülecektir.'];
        } catch (\Throwable $e) {
            Log::error('Ödeme talebi oluşturulamadı', ['bayi' => $bayi->id, 'hata' => $e->getMessage()]);
            return [false, 'Talep oluşturulurken bir hata oluştu.'];
        }
    }

    /**
     * Talebi onaylar ve DN Coin bakiyesinden DÜŞER.
     * Coin düşülemezse talep onaylanmaz (aynı para iki kez ödenmesin).
     */
    public static function onayla(int $talepId, ?int $yoneticiId = null): array
    {
        $talep = DB::table('bayi_odeme_talepleri')->find($talepId);
        if (!$talep) return [false, 'Ödeme talebi bulunamadı.'];

        if (($talep->durum ?? '') !== 'beklemede') {
            return [false, 'Bu talep daha önce sonuçlandırılmış.'];
        }

        $bayi = Bayi::find($talep->bayi_id);
        if (!$bayi || !$bayi->uye_id) {
            return [false, 'Bayinin üye hesabı bulunamadı.'];
        }

        $tutar = (float) ($talep->tutar ?: $talep->talep_tutari);

        // ÖNCE coin düş — yetmiyorsa talep onaylanmaz
        try {
            DnBankService::bakiyeHarca(
                (int) $bayi->uye_id,
                $tutar,
                'Ödeme talebi #' . $talepId . ' onaylandı (banka havalesi)'
            );
        } catch (\Throwable $e) {
            Log::warning('Ödeme talebi onaylanamadı — bakiye yetersiz', [
                'talep' => $talepId, 'hata' => $e->getMessage(),
            ]);
            return [false, 'Bakiye yetersiz, talep onaylanamadı: ' . $e->getMessage()];
        }

        DB::table('bayi_odeme_talepleri')->where('id', $talepId)->update([
            'durum'                => 'onaylandi',
            'onay_tarihi'          => now(),
            'onaylayan_admin_id'   => $yoneticiId,
            'updated_at'           => now(),
        ]);

        // Bayi kartındaki toplamlar (görsel özet)
        try {
            DB::table('bayiler')->where('id', $bayi->id)->increment('cekilen_toplam', $tutar);
        } catch (\Throwable $e) {}

        try {
            self::bildir($bayi, $tutar, true);
        } catch (\Throwable $e) {}

        return [true, number_format($tutar, 2, ',', '.') . ' DN Coin bakiyeden düşüldü, talep onaylandı.'];
    }

    /** Talebi reddeder — bakiyeye dokunulmaz. */
    public static function reddet(int $talepId, ?string $neden = null, ?int $yoneticiId = null): array
    {
        $talep = DB::table('bayi_odeme_talepleri')->find($talepId);
        if (!$talep) return [false, 'Ödeme talebi bulunamadı.'];

        if (($talep->durum ?? '') !== 'beklemede') {
            return [false, 'Bu talep daha önce sonuçlandırılmış.'];
        }

        DB::table('bayi_odeme_talepleri')->where('id', $talepId)->update([
            'durum'              => 'reddedildi',
            'red_nedeni'         => $neden,
            'onaylayan_admin_id' => $yoneticiId,
            'updated_at'         => now(),
        ]);

        try {
            $bayi = Bayi::find($talep->bayi_id);
            if ($bayi) self::bildir($bayi, (float) ($talep->tutar ?: 0), false, $neden);
        } catch (\Throwable $e) {}

        return [true, 'Talep reddedildi. Bakiyeye dokunulmadı.'];
    }

    /**
     * BORÇTAN MAHSUP — kişinin DN Coin'i borcundan düşülür.
     * Fatura tutarından fazlası harcanmaz; bakiye yetmezse kısmi mahsup yapılır.
     *
     * @return array{0: bool, 1: string, 2: float}  [basarili, mesaj, mahsup_tutari]
     */
    public static function borctanMahsup(int $uyeId, int $faturaId, ?int $yoneticiId = null): array
    {
        $fatura = DB::table('faturalar')->find($faturaId);
        if (!$fatura) return [false, 'Fatura bulunamadı.', 0];

        $borc = (float) ($fatura->toplam ?: $fatura->tutar);
        if ($borc <= 0) return [false, 'Faturanın ödenecek tutarı yok.', 0];

        $bakiye = DnBankService::bakiye($uyeId);
        if ($bakiye <= 0) return [false, 'DN Coin bakiyesi yok.', 0];

        $mahsup = round(min($bakiye, $borc), 2);

        try {
            DnBankService::bakiyeHarca(
                $uyeId, $mahsup,
                'Borç mahsubu — fatura ' . ($fatura->fatura_no ?: ('#' . $faturaId)),
                $faturaId
            );
        } catch (\Throwable $e) {
            return [false, 'Mahsup yapılamadı: ' . $e->getMessage(), 0];
        }

        // Fatura tamamen kapandıysa ödenmiş işaretle
        if ($mahsup >= $borc - 0.01) {
            try {
                DB::table('faturalar')->where('id', $faturaId)->update([
                    'durum' => 1, 'odenen_tarih' => now(), 'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {}
        }

        Log::info('Borç mahsubu yapıldı', [
            'uye' => $uyeId, 'fatura' => $faturaId,
            'mahsup' => $mahsup, 'yonetici' => $yoneticiId,
        ]);

        $kalan = round($borc - $mahsup, 2);

        return [true, number_format($mahsup, 2, ',', '.') . ' DN Coin borçtan düşüldü.'
            . ($kalan > 0 ? ' Kalan borç: ' . number_format($kalan, 2, ',', '.') . ' TL.' : ' Fatura kapandı.'), $mahsup];
    }

    /* ───────────── bildirim ───────────── */

    private static function bildir(Bayi $bayi, float $tutar, bool $onaylandi, ?string $neden = null): void
    {
        $mail = DB::table('uyeler')->where('id', $bayi->uye_id)->value('email');
        if (!$mail) return;

        $baslik = $onaylandi ? '✅ Ödeme talebiniz onaylandı' : '❌ Ödeme talebiniz reddedildi';
        $aciklama = $onaylandi
            ? number_format($tutar, 2, ',', '.') . ' DN Coin tutarındaki çekim talebiniz onaylandı. '
              . 'Tutar banka hesabınıza aktarılacaktır.'
            : 'Çekim talebiniz onaylanmadı. Bakiyenizden herhangi bir kesinti yapılmadı.'
              . ($neden ? ' Gerekçe: ' . $neden : '');

        EmailNotificationService::send($mail, $baslik,
            '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px">'
            . '<h2 style="color:#1a2332">' . htmlspecialchars($baslik) . '</h2>'
            . '<p style="color:#5b6168;line-height:1.7">' . htmlspecialchars($aciklama) . '</p>'
            . '<p style="color:#9aa0a6;font-size:12px">DN Kreatif · İş Ortağım</p></div>');
    }
}
