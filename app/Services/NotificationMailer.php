<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Sistem genelinde mail bildirim servisi.
 * Login, register, password change/reset, order, ticket vb. olaylarda kullanılır.
 *
 * Tüm metodlar fail-safe — Yandex/SMTP arızalanırsa log'a yazıp sessizce geçer,
 * kullanıcı akışı bozulmaz.
 */
class NotificationMailer
{
    /**
     * Genel mail gönderici — hata loglar ama atmaz.
     */
    public static function send(string $to, string $subject, string $body, ?string $name = null): bool
    {
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        try {
            Mail::html(self::wrap($subject, $body), function ($m) use ($to, $name, $subject) {
                $m->to($to, $name ?: $to)->subject($subject);
            });
            Log::info('NotificationMailer sent', ['to' => $to, 'subject' => $subject]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('NotificationMailer fail', ['to' => $to, 'subject' => $subject, 'err' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * V1 dark theme'e uyumlu HTML wrapper (basit sarı vurgu).
     */
    protected static function wrap(string $subject, string $body): string
    {
        $year = date('Y');
        $siteName = config('mail.from.name', 'İş Ortağım');
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>{$subject}</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#0f172a">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 0">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">
<tr><td style="background:linear-gradient(135deg,#facc15,#f59e0b);padding:24px 32px;color:#000">
<div style="font-size:22px;font-weight:700">{$siteName}</div>
<div style="font-size:13px;opacity:.8;margin-top:4px">{$subject}</div>
</td></tr>
<tr><td style="padding:32px;color:#0f172a;line-height:1.6;font-size:15px">{$body}</td></tr>
<tr><td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#64748b;text-align:center;border-top:1px solid #e2e8f0">
© {$year} {$siteName}. Bu e-posta sistem tarafından otomatik gönderilmiştir.
</td></tr>
</table>
</td></tr>
</table>
</body></html>
HTML;
    }

    // ========== AUTH ==========

    public static function loginNotice(object $user, string $ip): void
    {
        $to = $user->email ?? '';
        $appUrl = (string) config('app.url');

        if (app()->environment(['local', 'testing']) || str_contains($appUrl, '127.0.0.1') || str_contains($appUrl, 'localhost')) {
            Log::info('Login notice skipped outside production', [
                'to' => $to,
                'ip' => $ip,
                'env' => app()->environment(),
                'app_url' => $appUrl,
            ]);
            return;
        }

        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>Hesabınıza yeni bir giriş yapıldı.</p>"
            . "<table cellpadding='6' style='font-size:14px;border-collapse:collapse'>"
            . "<tr><td><strong>Tarih:</strong></td><td>" . now('Europe/Istanbul')->format('d.m.Y H:i') . "</td></tr>"
            . "<tr><td><strong>IP:</strong></td><td>{$ip}</td></tr>"
            . "</table>"
            . "<p style='margin-top:20px;font-size:13px;color:#64748b'>Bu giriş size ait değilse hemen şifrenizi değiştirin.</p>";
        self::send($user->email ?? '', 'Yeni giriş bildirimi', $body, $name);
    }

    public static function welcome(object $user): void
    {
        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $verifyUrl = self::buildEmailVerifyUrl($user);
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>İş Ortağım ailesine hoş geldiniz! Hesabınız başarıyla oluşturuldu.</p>"
            . "<p>Hesabınızı kullanabilmek için lütfen e-posta adresinizi doğrulayın:</p>"
            . "<p style='margin:24px 0'><a href='{$verifyUrl}' style='background:#facc15;color:#000;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px'>✉️ E-postamı Doğrula</a></p>"
            . "<p style='font-size:12px;color:#64748b;word-break:break-all'>Buton çalışmazsa şu adresi tarayıcınıza yapıştırın:<br><code>{$verifyUrl}</code></p>"
            . "<p style='font-size:13px;color:#64748b;margin-top:20px'>Bu link 24 saat geçerlidir.</p>";
        self::send($user->email ?? '', 'Hoş geldiniz! E-postanızı doğrulayın', $body, $name);
    }

    /**
     * Sadece doğrulama maili (resend için).
     */
    public static function emailVerify(object $user): bool
    {
        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $verifyUrl = self::buildEmailVerifyUrl($user);
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>E-posta doğrulama bağlantınız aşağıda:</p>"
            . "<p style='margin:24px 0'><a href='{$verifyUrl}' style='background:#facc15;color:#000;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px'>✉️ E-postamı Doğrula</a></p>"
            . "<p style='font-size:13px;color:#64748b'>Bu link 24 saat geçerlidir. Bu isteği siz yapmadıysanız dikkate almayın.</p>";
        return self::send($user->email ?? '', 'E-posta doğrulama', $body, $name);
    }

    /**
     * Kayıt sırasında 6 haneli doğrulama KODU gönderir (kod tabanlı doğrulama ekranı için).
     */
    public static function dogrulamaKodu(string $email, ?string $ad, string $kod): bool
    {
        $name = $ad ?: $email;
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>Kaydınızı tamamlamak için doğrulama kodunuz:</p>"
            . "<p style='margin:26px 0;text-align:center'><span style='display:inline-block;background:#111827;color:#facc15;font-size:34px;font-weight:800;letter-spacing:10px;padding:16px 30px;border-radius:12px'>{$kod}</span></p>"
            . "<p style='font-size:13px;color:#64748b'>Bu kod <strong>15 dakika</strong> geçerlidir. Bu isteği siz yapmadıysanız dikkate almayın.</p>";
        // Kod SADECE gövdede — konu satırına koyma (log'a/başlıklara sızmasın)
        return self::send($email, 'E-posta Doğrulama Kodunuz', $body, $name);
    }

    public static function emailVerified(object $user): void
    {
        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>✅ E-posta adresiniz başarıyla doğrulandı. Artık tüm özellikleri kullanabilirsiniz.</p>"
            . "<p style='margin-top:24px'><a href='" . url('/hesabim') . "' style='background:#facc15;color:#000;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700'>Hesabıma Git</a></p>";
        self::send($user->email ?? '', 'E-posta doğrulandı', $body, $name);
    }

    protected static function buildEmailVerifyUrl(object $user): string
    {
        return \URL::temporarySignedRoute(
            'email.verify',
            now()->addHours(24),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
    }

    public static function passwordChanged(object $user, string $ip = ''): void
    {
        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>Hesabınızın şifresi <strong>" . now('Europe/Istanbul')->format('d.m.Y H:i') . "</strong> tarihinde değiştirildi.</p>"
            . ($ip ? "<p>IP: <code>{$ip}</code></p>" : '')
            . "<p style='color:#b91c1c;font-weight:600;margin-top:20px'>⚠️ Bu işlemi siz yapmadıysanız acilen destek ekibimize ulaşın.</p>";
        self::send($user->email ?? '', 'Şifre değiştirildi', $body, $name);
    }

    public static function passwordResetCode(string $email, string $code, ?string $name = null): void
    {
        $body = "<p>Merhaba,</p>"
            . "<p>Şifre sıfırlama isteğiniz alındı. Aşağıdaki kodu kullanın:</p>"
            . "<div style='background:#fef3c7;color:#92400e;font-size:28px;font-weight:700;letter-spacing:8px;text-align:center;padding:20px;border-radius:12px;margin:20px 0'>{$code}</div>"
            . "<p style='font-size:13px;color:#64748b'>Bu kod 15 dakika içinde geçerlidir. Bu isteği siz yapmadıysanız bu maili dikkate almayın.</p>";
        self::send($email, 'Şifre sıfırlama kodu', $body, $name);
    }

    // ========== SİPARİŞ / FATURA ==========

    public static function invoiceCreated(object $user, object $invoice): void
    {
        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $tutar = number_format((float)($invoice->tutar ?? 0), 2, ',', '.');
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>Hesabınıza yeni bir fatura tanımlandı:</p>"
            . "<table cellpadding='8' style='border-collapse:collapse;width:100%;border:1px solid #e2e8f0'>"
            . "<tr style='background:#fef3c7'><td><strong>Fatura No:</strong></td><td>#" . ($invoice->id ?? '') . "</td></tr>"
            . "<tr><td><strong>Başlık:</strong></td><td>" . ($invoice->baslik ?? '') . "</td></tr>"
            . "<tr><td><strong>Tutar:</strong></td><td><strong>₺{$tutar}</strong></td></tr>"
            . "<tr><td><strong>Vade:</strong></td><td>" . ($invoice->bitis_tarih ?? '—') . "</td></tr>"
            . "</table>"
            . "<p style='margin-top:24px'><a href='" . url('/hesabim/faturalar') . "' style='background:#facc15;color:#000;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700'>Faturayı Görüntüle</a></p>";
        self::send($user->email ?? '', 'Yeni faturanız hazır', $body, $name);
    }

    public static function paymentReceived(object $user, object $invoice): void
    {
        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $tutar = number_format((float)($invoice->tutar ?? 0), 2, ',', '.');
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>✅ Ödemeniz alındı, teşekkür ederiz.</p>"
            . "<p>Fatura #" . ($invoice->id ?? '') . " — <strong>₺{$tutar}</strong></p>";
        self::send($user->email ?? '', 'Ödemeniz alındı', $body, $name);
    }

    // ========== DESTEK / TICKET ==========

    public static function ticketReply(object $user, object $ticket): void
    {
        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>Destek talebinize yeni bir yanıt geldi:</p>"
            . "<table cellpadding='8' style='border-collapse:collapse;border:1px solid #e2e8f0;width:100%'>"
            . "<tr style='background:#fef3c7'><td><strong>Ticket:</strong></td><td>#" . ($ticket->id ?? '') . " — " . ($ticket->baslik ?? '') . "</td></tr>"
            . "</table>"
            . "<p style='margin-top:24px'><a href='" . url('/hesabim/destek/' . ($ticket->id ?? '')) . "' style='background:#facc15;color:#000;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700'>Görüntüle</a></p>";
        self::send($user->email ?? '', 'Destek talebinize yanıt', $body, $name);
    }

    // ========== BAYİ ==========

    public static function bayiOnay(object $user): void
    {
        $name = trim(($user->ad ?? '') . ' ' . ($user->soyad ?? '')) ?: ($user->email ?? '');
        $body = "<p>Merhaba <strong>{$name}</strong>,</p>"
            . "<p>🎉 Bayilik başvurunuz onaylandı! Artık bayilik panelinize giriş yapabilirsiniz.</p>"
            . "<p style='margin-top:24px'><a href='" . url('/admin/bayi/dashboard') . "' style='background:#facc15;color:#000;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700'>Bayilik Paneline Git</a></p>";
        self::send($user->email ?? '', 'Bayilik başvurunuz onaylandı', $body, $name);
    }
}
