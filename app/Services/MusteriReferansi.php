<?php

namespace App\Services;

use App\Models\Oneri;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * MÜŞTERİ REFERANSI — "bu müşteriyi kim getirdi" bağı ve %10 kazancı.
 *
 * Bağ kurulduktan sonra, bağlanan müşterinin YAPTIĞI HER ALIM için
 * öneren kişiye tutarın %10'u DN Coin olarak yatar (KazancMotoru üzerinden).
 * Eski sistemdeki "sadece ilk faturanın %10'u, tek seferlik" davranışının yerini alır.
 */
class MusteriReferansi
{
    /**
     * Bağ kurar: $onerilenUyeId müşterisini $onerenUyeId getirdi.
     *
     * @return array{0: bool, 1: string}  [basarili, mesaj]
     */
    public static function bagla(int $onerenUyeId, int $onerilenUyeId, ?int $olusturanId = null, ?float $oran = null): array
    {
        if ($onerenUyeId === $onerilenUyeId) {
            return [false, 'Bir müşteri kendini öneremez.'];
        }

        [$oneri, $mesaj] = Oneri::bagKur(
            'uye', $onerenUyeId,
            'uye', $onerilenUyeId,
            Oneri::TIP_MUSTERI,
            $oran,
            'admin',
            $olusturanId
        );

        if (!$oneri) {
            return [false, $mesaj];
        }

        // Öneren kişiye bilgilendirme maili — akışı bozmasın
        try {
            self::bilgilendir($onerenUyeId, $onerilenUyeId, (float) $oneri->oran);
        } catch (\Throwable $e) {
            Log::warning('Referans bağlama maili gönderilemedi', ['hata' => $e->getMessage()]);
        }

        return [true, 'Referans bağı kuruldu. Bu müşterinin her alımından %'
            . (int) $oneri->oran . ' kazanç yatacak.'];
    }

    /** Bağı kaldırır (kazançlar defterde kalır, yalnızca yenisi işlemez). */
    public static function bagiKaldir(int $onerilenUyeId): bool
    {
        return (bool) DB::table('oneriler')
            ->where('onerilen_tip', 'uye')
            ->where('onerilen_id', $onerilenUyeId)
            ->where('tip', Oneri::TIP_MUSTERI)
            ->update(['durum' => 'iptal', 'updated_at' => now()]);
    }

    /**
     * Bir müşterinin alımını işler — bağlıysa öneren %10 kazanır.
     * Fatura/satış oluşturan yerlerden çağrılır.
     */
    public static function alimIsle(int $uyeId, float $tutar, string $kaynakTip, string|int $kaynakId): array
    {
        try {
            $kazanclar = KazancMotoru::musteriAlimiIsle($uyeId, $tutar, $kaynakTip, $kaynakId);

            foreach ($kazanclar as $k) {
                try {
                    self::kazancBildir((int) $k->kazanan_uye_id, (float) $k->tutar, $uyeId);
                } catch (\Throwable $e) {
                    Log::warning('Referans kazanç maili gönderilemedi', ['hata' => $e->getMessage()]);
                }
            }

            return $kazanclar;
        } catch (\Throwable $e) {
            Log::error('Referans kazancı işlenemedi', [
                'uye' => $uyeId, 'kaynak' => $kaynakTip . '/' . $kaynakId, 'hata' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /* ───────────── mailler ───────────── */

    private static function bilgilendir(int $onerenUyeId, int $onerilenUyeId, float $oran): void
    {
        $oneren   = DB::table('uyeler')->find($onerenUyeId);
        $onerilen = DB::table('uyeler')->find($onerilenUyeId);
        if (!$oneren || empty($oneren->email)) return;

        $govde = self::mailGovde(
            '🎉 Referansınız kaydedildi!',
            'Sayın ' . self::ad($oneren) . ', ' . self::ad($onerilen) . ' sizin referansınızla kaydedildi. '
            . 'Bundan sonra yaptığı her alımın %' . (int) $oran . "'u hesabınıza DN Coin olarak yatacak. "
            . 'Yönlendirdiğiniz her kişi için kazanmaya devam edeceksiniz.',
            ['Referansınız' => self::ad($onerilen), 'Kazanç oranınız' => '%' . (int) $oran]
        );

        EmailNotificationService::send($oneren->email, '🎉 Referansınız kaydedildi — kazanmaya başlıyorsunuz', $govde);
    }

    private static function kazancBildir(int $onerenUyeId, float $tutar, int $onerilenUyeId): void
    {
        $oneren   = DB::table('uyeler')->find($onerenUyeId);
        $onerilen = DB::table('uyeler')->find($onerilenUyeId);
        if (!$oneren || empty($oneren->email)) return;

        $bakiye = 0;
        try { $bakiye = DnBankService::bakiye($onerenUyeId); } catch (\Throwable $e) {}

        $govde = self::mailGovde(
            '💰 Hesabınıza ' . number_format($tutar, 2, ',', '.') . ' DN Coin yattı!',
            'Sayın ' . self::ad($oneren) . ', referansınız ' . self::ad($onerilen)
            . ' yeni bir alım yaptı ve siz kazandınız. Yönlendirmeye devam ettikçe kazancınız artar.',
            [
                'Kazancınız'      => number_format($tutar, 2, ',', '.') . ' DN Coin',
                'Referansınız'    => self::ad($onerilen),
                'Güncel bakiyeniz' => number_format($bakiye, 2, ',', '.') . ' DN Coin',
            ]
        );

        EmailNotificationService::send($oneren->email, '💰 Referans kazancınız: ' . number_format($tutar, 2, ',', '.') . ' DN Coin', $govde);
    }

    private static function ad($uye): string
    {
        if (!$uye) return 'Müşterimiz';
        $ad = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? ''));
        return $ad !== '' ? $ad : ($uye->firmaadi ?? 'Müşterimiz');
    }

    /** Teşvik edici kurumsal mail şablonu */
    private static function mailGovde(string $baslik, string $aciklama, array $satirlar): string
    {
        $ic = '';
        foreach ($satirlar as $etiket => $deger) {
            $deger = trim((string) $deger);
            if ($deger === '') continue;
            $ic .= '<tr><td style="padding:12px 0;border-bottom:1px solid #f0efe6">'
                . '<div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">' . htmlspecialchars($etiket) . '</div>'
                . '<div style="font-size:15px;color:#2b2b1f;font-weight:700">' . htmlspecialchars($deger) . '</div></td></tr>';
        }

        $panelUrl = rtrim(config('app.url', 'https://crm.ornek.com'), '/') . '/hesabim';

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px"><tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden">
    <tr><td style="background-color:#1a2332;padding:26px 36px 22px;text-align:center">
      <img src="https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png" alt="DN Kreatif" width="150" style="display:block;margin:0 auto 12px;max-width:150px;height:auto;border:0">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Referans Programı 🎁</div>
    </td></tr>
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:30px 36px 14px">
      <h2 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1a1a0e">' . htmlspecialchars($baslik) . '</h2>
      <p style="margin:0 0 18px;color:#6b6f63;font-size:14px;line-height:1.7">' . htmlspecialchars($aciklama) . '</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $ic . '</table>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 6px"><tr><td align="center">
        <a href="' . htmlspecialchars($panelUrl) . '" style="display:inline-block;background-color:#b8b62e;color:#1a1a0e;text-decoration:none;padding:13px 38px;border-radius:10px;font-weight:800;font-size:15px">Hesabımı Gör &rarr;</a>
      </td></tr></table>
    </td></tr>
    <tr><td style="background-color:#1a2332;padding:20px 36px;text-align:center">
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . date('Y') . ' DN Kreatif &middot; İş Ortağım</p>
    </td></tr>
  </table>
</td></tr></table></body></html>';
    }
}
