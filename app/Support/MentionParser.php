<?php

namespace App\Support;

use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\DB;

class MentionParser
{
    /**
     * İçerikteki @kullaniciadi etiketlerini bul, eşleşen yöneticilere mail gönder.
     * Dönüş: etiketlenen yöneticilerin koleksiyonu.
     */
    public static function processMentions(string $icerik, array $context = []): \Illuminate\Support\Collection
    {
        if (!preg_match_all('/@([a-zA-Z0-9_\.]+)/', $icerik, $matches)) {
            return collect();
        }
        $unames = array_unique($matches[1]);
        if (empty($unames)) return collect();

        $mentioned = DB::table('yoneticiler')
            ->whereIn('kullaniciadi', $unames)
            ->where('durum', 1)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('id', 'adi', 'kullaniciadi', 'email')
            ->get();

        $subject = $context['subject'] ?? 'Bir mesajda etiketlendiniz';
        $kaynak  = $context['kaynak'] ?? 'mesaj';
        $link    = $context['link']   ?? null;
        $by      = $context['by']     ?? 'Bir yönetici';
        $baslik  = $context['baslik'] ?? null;

        foreach ($mentioned as $u) {
            $aliciAdi = $u->adi ?: $u->kullaniciadi;
            $body = self::body($icerik, $by, $kaynak, $link, $aliciAdi, $baslik);
            try {
                EmailNotificationService::send($u->email, $subject, $body);
            } catch (\Throwable $e) {
                // sessiz geç
            }
        }
        return $mentioned;
    }

    private static function body(string $icerik, string $by, string $kaynak, ?string $link, string $aliciAdi, ?string $baslik = null): string
    {
        $aliciAdi = htmlspecialchars($aliciAdi);
        $by       = htmlspecialchars($by);
        $kaynak   = htmlspecialchars($kaynak);
        $preview  = nl2br(htmlspecialchars(mb_strlen($icerik) > 600 ? mb_substr($icerik, 0, 600) . '…' : $icerik));
        $yil      = date('Y');

        $baslikHtml = $baslik
            ? '<div style="font-size:13px;color:#8a8a1f;font-weight:700;margin-bottom:8px">📌 ' . htmlspecialchars($baslik) . '</div>'
            : '';

        $buttonHtml = $link ? '
        <tr>
          <td align="center" style="padding:8px 0 4px">
            <a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#b8b62e;color:#000000;text-decoration:none;padding:14px 34px;border-radius:10px;font-weight:700;font-size:15px">
              🔗 Görüntüle
            </a>
          </td>
        </tr>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f1f1f1;font-family:'Segoe UI',Arial,sans-serif;color:#1f2937">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f1f1;padding:32px 16px">
<tr><td align="center">

  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.08)">

    <!-- HEADER -->
    <tr>
      <td style="background-color:#b8b62e;background:linear-gradient(135deg,#d4d066,#b8b62e 55%,#8a8a1f);padding:34px 28px;text-align:center">
        <div style="font-size:42px;line-height:1;margin-bottom:8px">👋</div>
        <h1 style="margin:0;color:#1a1a00;font-size:22px;font-weight:800;letter-spacing:.3px">Etiketlendiniz</h1>
        <p style="margin:6px 0 0;color:rgba(26,26,0,.72);font-size:13px">Bir {$kaynak} içinde sizden bahsedildi</p>
      </td>
    </tr>

    <!-- BODY -->
    <tr>
      <td style="padding:30px 28px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
          <tr><td style="padding-bottom:14px;font-size:15px;line-height:1.6">
            Merhaba <strong style="color:#8a8a1f">{$aliciAdi}</strong>,
          </td></tr>
          <tr><td style="padding-bottom:18px;font-size:15px;line-height:1.6">
            <strong style="color:#8a8a1f">{$by}</strong>, bir <strong>{$kaynak}</strong> içinde sizi etiketledi.
          </td></tr>
          <tr><td style="padding-bottom:22px">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fbfbf3;border:1px solid #ecebcf;border-left:4px solid #b8b62e;border-radius:10px">
              <tr><td style="padding:16px 18px">
                {$baslikHtml}
                <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px">💬 Mesaj</div>
                <div style="font-size:14px;line-height:1.65;color:#374151">{$preview}</div>
              </td></tr>
            </table>
          </td></tr>
          {$buttonHtml}
        </table>
      </td>
    </tr>

    <!-- FOOTER -->
    <tr>
      <td style="background:#faf9ef;padding:18px 24px;text-align:center;border-top:1px solid #ecebcf">
        <p style="margin:0;font-size:13px;color:#6b7280"><strong style="color:#8a8a1f">İş Ortağım</strong> — DN Kreatif Yönetim Paneli</p>
        <p style="margin:6px 0 0;font-size:11px;color:#aeb4bd">Bu otomatik bir bildirim e-postasıdır · © {$yil} DN Grup Medya ve Teknoloji A.Ş.</p>
      </td>
    </tr>

  </table>

</td></tr>
</table>
</body>
</html>
HTML;
    }
}