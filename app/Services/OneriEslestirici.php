<?php

namespace App\Services;

use App\Models\Bayi;
use App\Models\Oneri;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ÖNERİ EŞLEŞTİRİCİ — "A kişisi B kişisini önerdi" bağını otomatik kurar.
 *
 * Akış:
 *   1. A, B'yi e-posta ile davet eder            → oneri_davetleri (beklemede)
 *   2. B kayıt olur / bayi başvurusu onaylanır   → eslestir() çağrılır
 *   3. Bağ kurulur (oneriler) ve A'ya haber verilir
 *
 * B'nin sonraki her aksiyonunda A'ya bildirim gider (aksiyonBildir).
 */
class OneriEslestirici
{
    /** Davet kaydeder (henüz bağ kurulmaz — kişi sisteme girince kurulur). */
    public static function davetKaydet(
        string $onerenTip, int $onerenId, string $email,
        ?string $adSoyad = null, string $tip = Oneri::TIP_BAYI, ?string $mesaj = null
    ): bool {
        if (!Schema::hasTable('oneri_davetleri')) return false;

        try {
            // Aynı e-postaya bekleyen davet varsa tazele
            $mevcut = DB::table('oneri_davetleri')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->where('tip', $tip)->where('durum', 'beklemede')->first();

            if ($mevcut) {
                DB::table('oneri_davetleri')->where('id', $mevcut->id)->update([
                    'oneren_tip' => $onerenTip, 'oneren_id' => $onerenId,
                    'ad_soyad' => $adSoyad, 'mesaj' => $mesaj, 'updated_at' => now(),
                ]);
                return true;
            }

            DB::table('oneri_davetleri')->insert([
                'oneren_tip' => $onerenTip, 'oneren_id' => $onerenId,
                'email' => $email, 'ad_soyad' => $adSoyad, 'mesaj' => $mesaj,
                'tip' => $tip, 'durum' => 'beklemede',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Öneri daveti kaydedilemedi', ['hata' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Yeni kayıt olan / onaylanan kişi için bekleyen daveti bulur ve bağı kurar.
     *
     * @param  string $email        Kişinin e-postası
     * @param  string $onerilenTip  'bayi' | 'uye'
     * @param  int    $onerilenId   Oluşan kaydın id'si
     * @return ?Oneri Kurulan bağ (yoksa null)
     */
    public static function eslestir(string $email, string $onerilenTip, int $onerilenId): ?Oneri
    {
        if (!Schema::hasTable('oneri_davetleri')) return null;

        try {
            $tip = $onerilenTip === 'bayi' ? Oneri::TIP_BAYI : Oneri::TIP_MUSTERI;

            $davet = DB::table('oneri_davetleri')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->where('durum', 'beklemede')
                ->where('tip', $tip)
                ->orderByDesc('id')->first();

            if (!$davet) return null;

            [$oneri, $mesaj] = Oneri::bagKur(
                $davet->oneren_tip, (int) $davet->oneren_id,
                $onerilenTip, $onerilenId,
                $tip, null, 'davet'
            );

            if (!$oneri) {
                Log::info('Öneri eşleşmesi kurulamadı', ['davet' => $davet->id, 'sebep' => $mesaj]);
                return null;
            }

            DB::table('oneri_davetleri')->where('id', $davet->id)->update([
                'durum' => 'kullanildi',
                'olusan_oneri_id' => $oneri->id,
                'eslesme_tarihi' => now(),
                'updated_at' => now(),
            ]);

            // Öneren kişiye "davetin kabul oldu" haberi
            try {
                self::eslesmeBildir($oneri, $davet->ad_soyad ?: $email);
            } catch (\Throwable $e) {
                Log::warning('Eşleşme bildirimi gönderilemedi', ['hata' => $e->getMessage()]);
            }

            return $oneri;
        } catch (\Throwable $e) {
            Log::error('Öneri eşleştirme hatası', ['email' => $email, 'hata' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Önerilen kişinin bir aksiyonunu önerene bildirir.
     * (satış yaptı, paket aldı, yeniledi vb.)
     */
    public static function aksiyonBildir(string $onerilenTip, int $onerilenId, string $aksiyon, ?string $detay = null): void
    {
        try {
            $tip = $onerilenTip === 'bayi' ? Oneri::TIP_BAYI : Oneri::TIP_MUSTERI;
            $oneri = Oneri::onereniniBul($onerilenTip, $onerilenId, $tip);
            if (!$oneri || !$oneri->gecerliMi()) return;

            $onerenMail = self::onerenEposta($oneri);
            if (!$onerenMail) return;

            $kimAdi = self::taraAdi($onerilenTip, $onerilenId);

            EmailNotificationService::send(
                $onerenMail,
                '🔔 ' . $kimAdi . ' — ' . $aksiyon,
                self::mailGovde(
                    '🔔 Önerdiğiniz kişiden hareket var',
                    'Önerdiğiniz ' . $kimAdi . ' yeni bir işlem yaptı. Kazançlarınız hesabınıza otomatik yansır.',
                    array_filter([
                        'Kişi'   => $kimAdi,
                        'İşlem'  => $aksiyon,
                        'Detay'  => $detay,
                        'Toplam kazancınız' => number_format((float) $oneri->toplam_kazanc, 2, ',', '.') . ' DN Coin',
                    ])
                )
            );
        } catch (\Throwable $e) {
            Log::warning('Aksiyon bildirimi gönderilemedi', ['hata' => $e->getMessage()]);
        }
    }

    /* ───────────── iç işler ───────────── */

    private static function onerenEposta(Oneri $oneri): ?string
    {
        if ($oneri->oneren_tip === 'uye') {
            return DB::table('uyeler')->where('id', $oneri->oneren_id)->value('email') ?: null;
        }

        $b = Bayi::find($oneri->oneren_id);
        if (!$b || !$b->uye_id) return null;

        return DB::table('uyeler')->where('id', $b->uye_id)->value('email') ?: null;
    }

    private static function taraAdi(string $tip, int $id): string
    {
        if ($tip === 'bayi') {
            $b = Bayi::find($id);
            if ($b) return $b->firma_adi ?: ('Bayi #' . $id);
            return 'Bayi #' . $id;
        }

        $u = DB::table('uyeler')->find($id);
        if (!$u) return 'Müşteri #' . $id;

        $ad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
        return $ad !== '' ? $ad : ($u->firmaadi ?: ('Müşteri #' . $id));
    }

    private static function eslesmeBildir(Oneri $oneri, string $kisiAdi): void
    {
        $mail = self::onerenEposta($oneri);
        if (!$mail) return;

        $oran = (int) $oneri->oran;
        $aciklama = $oneri->tip === Oneri::TIP_BAYI
            ? 'Önerdiğiniz ' . $kisiAdi . ' iş ortağımız oldu! Bundan sonra yaptığı her satışta, '
              . 'onun komisyonunun %' . $oran . "'si kadar siz de kazanacaksınız. Süresizdir ve onun kazancını azaltmaz."
            : 'Önerdiğiniz ' . $kisiAdi . ' kaydoldu! Yaptığı her alımın %' . $oran
              . "'u hesabınıza DN Coin olarak yatacak.";

        EmailNotificationService::send(
            $mail,
            '🎉 Davetiniz kabul edildi — ' . $kisiAdi,
            self::mailGovde('🎉 Davetiniz kabul edildi!', $aciklama, [
                'Kişi' => $kisiAdi,
                'Kazanç oranınız' => '%' . $oran,
                'Süre' => $oneri->suresiz ? 'Süresiz' : 'Sınırlı',
            ])
        );
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

        $panel = rtrim(config('app.url', 'https://crm.ornek.com'), '/') . '/hesabim';

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px"><tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden">
    <tr><td style="background-color:#1a2332;padding:26px 36px 22px;text-align:center">
      <img src="https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png" alt="DN Kreatif" width="150" style="display:block;margin:0 auto 12px;max-width:150px;height:auto;border:0">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Öner-Kazan 🤝</div>
    </td></tr>
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:30px 36px 14px">
      <h2 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1a1a0e">' . htmlspecialchars($baslik) . '</h2>
      <p style="margin:0 0 18px;color:#6b6f63;font-size:14px;line-height:1.7">' . htmlspecialchars($aciklama) . '</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $ic . '</table>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 6px"><tr><td align="center">
        <a href="' . htmlspecialchars($panel) . '" style="display:inline-block;background-color:#b8b62e;color:#1a1a0e;text-decoration:none;padding:13px 38px;border-radius:10px;font-weight:800;font-size:15px">Hesabımı Gör &rarr;</a>
      </td></tr></table>
    </td></tr>
    <tr><td style="background-color:#1a2332;padding:20px 36px;text-align:center">
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . date('Y') . ' DN Kreatif &middot; İş Ortağım</p>
    </td></tr>
  </table>
</td></tr></table></body></html>';
    }
}
