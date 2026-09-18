<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Satın alma bildirimi gönder (Müşteriye ve Admin'e)
     */
    public static function sendPurchaseNotification($faturaId, $userId, $tutar, $urunler = [])
    {
        try {
            $ayarlar = DB::table('ayarlar')->first();
            $uye = DB::table('uyeler')->where('id', $userId)->first();
            
            if (!$uye || !$uye->email) {
                Log::warning('Satın alma bildirimi: Kullanıcı e-postası bulunamadı', ['user_id' => $userId]);
                return false;
            }
            
            $firmaAdi = $ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım';
            $adminEmail = $ayarlar->firma_email ?? $ayarlar->email ?? null;
            
            // Ürün listesi oluştur
            $urunListesi = '';
            if (is_array($urunler) && count($urunler) > 0) {
                foreach ($urunler as $urun) {
                    $urunAdi = $urun['adi'] ?? $urun['name'] ?? 'Ürün';
                    $urunFiyat = $urun['fiyat'] ?? $urun['tutar'] ?? 0;
                    $urunListesi .= "- {$urunAdi}: " . number_format($urunFiyat, 2, ',', '.') . " ₺\n";
                }
            }
            
            // Müşteriye e-posta gönder
            self::sendMail(
                $uye->email,
                'Siparişiniz Alındı - #' . $faturaId,
                self::getPurchaseEmailTemplate('customer', [
                    'firma_adi' => $firmaAdi,
                    'musteri_adi' => ($uye->ad ?? '') . ' ' . ($uye->soyad ?? ''),
                    'fatura_id' => $faturaId,
                    'tutar' => number_format($tutar, 2, ',', '.'),
                    'urunler' => $urunListesi,
                    'tarih' => date('d.m.Y H:i'),
                ])
            );
            
            // Admin'e e-posta gönder
            if ($adminEmail) {
                self::sendMail(
                    $adminEmail,
                    'Yeni Sipariş - #' . $faturaId . ' - ' . number_format($tutar, 2, ',', '.') . ' ₺',
                    self::getPurchaseEmailTemplate('admin', [
                        'firma_adi' => $firmaAdi,
                        'musteri_adi' => ($uye->ad ?? '') . ' ' . ($uye->soyad ?? ''),
                        'musteri_email' => $uye->email,
                        'fatura_id' => $faturaId,
                        'tutar' => number_format($tutar, 2, ',', '.'),
                        'urunler' => $urunListesi,
                        'tarih' => date('d.m.Y H:i'),
                    ])
                );
            }
            
            Log::info('Satın alma bildirimi gönderildi', [
                'fatura_id' => $faturaId,
                'musteri' => $uye->email,
                'admin' => $adminEmail
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Satın alma bildirimi gönderilirken hata', [
                'error' => $e->getMessage(),
                'fatura_id' => $faturaId
            ]);
            return false;
        }
    }
    
    /**
     * Kayıt bildirimi gönder
     */
    public static function sendRegistrationNotification($userId)
    {
        try {
            $ayarlar = DB::table('ayarlar')->first();
            $uye = DB::table('uyeler')->where('id', $userId)->first();
            
            if (!$uye || !$uye->email) {
                return false;
            }
            
            $firmaAdi = $ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım';
            
            self::sendMail(
                $uye->email,
                'Hoş Geldiniz - ' . $firmaAdi,
                self::getWelcomeEmailTemplate([
                    'firma_adi' => $firmaAdi,
                    'musteri_adi' => ($uye->ad ?? '') . ' ' . ($uye->soyad ?? ''),
                    'site_url' => $ayarlar->site_url ?? url('/'),
                ])
            );
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Kayıt bildirimi gönderilirken hata', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Harici (public) — istediğiniz adrese serbest e-posta gönder
     * İçerik PARÇA HTML ise otomatik olarak standart şablona (logo + lime tema +
     * footer) sarmalanır. Zaten tam sayfa HTML (<!DOCTYPE / <html) verilmişse
     * olduğu gibi gönderilir (çift sarmalama önlenir).
     *
     * @param string      $to      Alıcı e-posta
     * @param string      $subject Konu (aynı zamanda şablon başlığı olur)
     * @param string      $body    İçerik (parça HTML ya da tam sayfa)
     * @param bool        $throw   Hata fırlatılsın mı
     * @param string|null $baslik  Şablon başlığı (verilmezse $subject kullanılır)
     * @throws \Exception gönderim başarısız olursa (yalnızca $throw=true iken)
     */
    public static function send($to, $subject, $body, bool $throw = false, ?string $baslik = null, bool $tanitim = false)
    {
        $body = self::sablonaSarmala($body, $baslik ?? $subject);

        // NOT: Bu kurulumda queue worker (php artisan queue:work) çalışmıyor.
        // QUEUE_CONNECTION=database olduğundan dispatch edilen mailler 'jobs'
        // tablosunda işlenmeden bekliyordu (mail hiç gitmiyordu). Bu yüzden
        // mailleri kuyruğa atmadan ANINDA (senkron) gönderiyoruz.
        return self::sendMail($to, $subject, $body, $throw, $tanitim);
    }

    /**
     * Tanıtım/kampanya maili için abonelikten çıkma bağlantısı (imzalı URL).
     * 12.08.2026 — Gmail'in toplu gönderici kuralları için eklendi.
     */
    public static function abonelikCikmaUrl(string $email): ?string
    {
        try {
            if (!\Illuminate\Support\Facades\Route::has('bulten.cik')) {
                return null;
            }
            return \Illuminate\Support\Facades\URL::signedRoute('bulten.cik', ['email' => $email]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * HTML gövdeden okunabilir düz metin üretir.
     *
     * NEDEN: Sadece-HTML mailler spam filtrelerinde ceza alır. Her mailin
     * bir düz metin alternatifi (multipart/alternative) olmalı.
     */
    private static function duzMetin(string $html): string
    {
        $m = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $m = preg_replace('#<br\s*/?>#i', "\n", $m);
        $m = preg_replace('#</(p|div|tr|h[1-6]|li)>#i', "\n", $m);
        $m = strip_tags($m);
        $m = html_entity_decode($m, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $m = preg_replace("/[ \t]+/", ' ', $m);
        $m = preg_replace("/\n{3,}/", "\n\n", $m);

        return trim($m);
    }

    /**
     * İçerik zaten tam bir HTML sayfa mı? (<!DOCTYPE veya <html ile başlıyorsa)
     * Değilse standart şablonla (wrap) sarmalar. Böylece tüm mailler tek tip olur.
     */
    private static function sablonaSarmala(string $body, string $baslik): string
    {
        $bas = ltrim($body);
        // Zaten tam sayfa HTML ise dokunma (çift sarmalama olmasın)
        if (stripos($bas, '<!doctype') === 0 || stripos($bas, '<html') === 0) {
            return $body;
        }
        return self::wrap($baslik, $body);
    }

    /**
     * E-posta gönder. $throw=true ise hata fırlatır.
     */
    public static function sendMail($to, $subject, $body, bool $throw = false, bool $tanitim = false)
    {
        /*
         * GÜVENLİK FRENİ — .env'de MAIL_MAILER=log / array ise gerçekten gönderme.
         *
         * Bu servis normalde mail.default'u zorla 'smtp' yapıp SMTP bilgilerini
         * `ayarlar` tablosundan (yoksa .env'den) okur. Bu yüzden .env'de
         * MAIL_MAILER=log yazmak TEK BAŞINA mail gitmesini engellemiyordu;
         * geliştirme makinesinde test komutu çalıştırınca gerçek kişilere
         * e-posta gidiyordu (30.07.2026'da yaşandı).
         *
         * Canlıda MAIL_MAILER=smtp olduğu için bu blok canlıda hiç çalışmaz.
         */
        $hedefSurucu = env('MAIL_MAILER', 'smtp');
        if (in_array($hedefSurucu, ['log', 'array'], true)) {
            \Illuminate\Support\Facades\Log::info('E-posta GÖNDERİLMEDİ (MAIL_MAILER=' . $hedefSurucu . ')', [
                'to'      => $to,
                'subject' => $subject,
            ]);
            return true;   // akış bozulmasın; çağıran taraf "gönderildi" sayar
        }

        $ayarlar = DB::table('ayarlar')->first();

        // Önce DB (admin panelinden değiştirilebilir), yoksa .env fallback
        $mailHost        = ($ayarlar->mail_host        ?? null) ?: env('MAIL_HOST');
        $mailPort        = ($ayarlar->mail_port        ?? null) ?: env('MAIL_PORT', 587);
        $mailUsername    = ($ayarlar->mail_username    ?? null) ?: env('MAIL_USERNAME');
        $mailPassword    = ($ayarlar->mail_password    ?? null) ?: env('MAIL_PASSWORD');
        $mailEncryption  = ($ayarlar->mail_encryption  ?? null) ?: env('MAIL_ENCRYPTION', 'tls');
        $mailFromAddress = ($ayarlar->mail_from_address ?? null) ?: env('MAIL_FROM_ADDRESS', $ayarlar->firma_email ?? $mailUsername);
        $mailFromName    = ($ayarlar->mail_from_name   ?? null) ?: env('MAIL_FROM_NAME', $ayarlar->firma_adi ?? 'DN İş Ortağım');

        if (!$mailHost || !$mailUsername || !$mailPassword) {
            $msg = 'SMTP ayarları eksik (host/username/password). Yönetim Paneli → Ayarlar → Mail Ayarları veya .env dosyasını kontrol edin.';
            Log::warning($msg, ['to' => $to]);
            if ($throw) throw new \RuntimeException($msg);
            return false;
        }

        try {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $mailHost,
                'mail.mailers.smtp.port' => (int) $mailPort,
                'mail.mailers.smtp.username' => $mailUsername,
                'mail.mailers.smtp.password' => $mailPassword,
                'mail.mailers.smtp.encryption' => $mailEncryption === 'ssl' ? 'ssl' : 'tls',
                'mail.from.address' => $mailFromAddress,
                'mail.from.name' => $mailFromName,
            ]);

            // Mailer'ı sıfırla ki yeni config geçerli olsun
            app()->forgetInstance('mail.manager');
            app()->forgetInstance('mailer');
            try {
                Mail::clearResolvedInstances();
            } catch (\Throwable $e) {}

            /*
             * TESLİM EDİLEBİLİRLİK (12.08.2026)
             *
             * 1) Düz metin alternatifi: sadece-HTML mailler spam filtresinde
             *    ceza alır. Aynı içeriğin metin hâli eklenir.
             * 2) Reply-To: yanıt adresi belirtilmeyen mailler şüpheli görülür.
             * 3) List-Unsubscribe (+ -Post): Gmail/Outlook toplu gönderimde
             *    ZORUNLU tutuyor. Yoksa mail spam'e düşer ve gönderen adresin
             *    itibarı bozulur — aynı adresten çıkan doğrulama kodu gibi
             *    kritik mailler de spam'e düşmeye başlar. Sadece tanıtım
             *    maillerine eklenir; şifre sıfırlama gibi işlemsel maillere
             *    eklenmez (onların aboneliği iptal edilemez).
             */
            $duzMetin = self::duzMetin($body);
            $cikmaUrl = $tanitim ? self::abonelikCikmaUrl(is_array($to) ? ($to[0] ?? '') : $to) : null;

            Mail::html($body, function ($message) use ($to, $subject, $mailFromAddress, $mailFromName, $duzMetin, $cikmaUrl) {
                $message->to($to)
                        ->subject($subject)
                        ->from($mailFromAddress, $mailFromName)
                        ->replyTo($mailFromAddress, $mailFromName)
                        ->text($duzMetin);

                if ($cikmaUrl) {
                    $message->getHeaders()->addTextHeader('List-Unsubscribe', '<' . $cikmaUrl . '>');
                    $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                }
            });

            Log::info('E-posta gönderildi', ['to' => $to, 'subject' => $subject]);
            return true;

        } catch (\Exception $e) {
            Log::error('E-posta gönderim hatası', [
                'to' => $to,
                'error' => $e->getMessage(),
                'host' => $mailHost,
                'port' => $mailPort,
            ]);
            if ($throw) throw $e;
            return false;
        }
    }
    
    /**
     * Satın alma e-posta şablonu
     */
    private static function getPurchaseEmailTemplate($type, $data)
    {
        if ($type === 'customer') {
            $urunlerBlok = $data['urunler']
                ? "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin:6px 0 0'><tr><td style='background-color:#f7f8f3;border-radius:8px;padding:14px 16px'><strong style='color:#3a4133'>Ürünler:</strong><div style='margin-top:8px;font-size:14px;color:#3a4133;white-space:pre-wrap'>{$data['urunler']}</div></td></tr></table>"
                : '';
            $govde = "<p style='margin:0 0 18px'>Merhaba <strong style='color:#6f7320'>{$data['musteri_adi']}</strong>,</p>"
                   . "<p style='margin:0 0 18px'>Siparişiniz başarıyla alındı. Detaylar aşağıdadır:</p>"
                   . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin:0 0 18px;border:1px solid #eceee6;border-radius:10px;overflow:hidden'>"
                   . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px;width:40%;border-bottom:1px solid #eceee6'>Sipariş No</td><td style='padding:12px 16px;font-size:14px;font-weight:600;border-bottom:1px solid #eceee6'>#{$data['fatura_id']}</td></tr>"
                   . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px;border-bottom:1px solid #eceee6'>Tarih</td><td style='padding:12px 16px;font-size:14px;font-weight:600;border-bottom:1px solid #eceee6'>{$data['tarih']}</td></tr>"
                   . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px'>Toplam Tutar</td><td style='padding:12px 16px;font-size:18px;font-weight:700;color:#6f7320'>{$data['tutar']} ₺</td></tr>"
                   . "</table>"
                   . $urunlerBlok
                   . "<p style='margin:18px 0 0;color:#7a8270;font-size:14px'>Sorularınız için bizimle iletişime geçebilirsiniz.</p>";
            return self::wrap('✓ Siparişiniz Alındı!', $govde);
        }

        // Admin sablonu (yeni siparis bildirimi)
        $urunlerBlokA = $data['urunler']
            ? "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin:14px 0 0'><tr><td style='background-color:#f7f8f3;border-radius:8px;padding:14px 16px'><strong>Ürünler:</strong><div style='margin-top:8px;font-size:14px;white-space:pre-wrap'>{$data['urunler']}</div></td></tr></table>"
            : '';
        $govdeA = "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border:1px solid #eceee6;border-radius:10px;overflow:hidden'>"
               . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px;width:35%;border-bottom:1px solid #eceee6'>Sipariş No</td><td style='padding:12px 16px;font-size:14px;font-weight:600;border-bottom:1px solid #eceee6'>#{$data['fatura_id']}</td></tr>"
               . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px;border-bottom:1px solid #eceee6'>Müşteri</td><td style='padding:12px 16px;font-size:14px;font-weight:600;border-bottom:1px solid #eceee6'>{$data['musteri_adi']}</td></tr>"
               . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px;border-bottom:1px solid #eceee6'>E-posta</td><td style='padding:12px 16px;font-size:14px;font-weight:600;border-bottom:1px solid #eceee6'>{$data['musteri_email']}</td></tr>"
               . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px;border-bottom:1px solid #eceee6'>Tarih</td><td style='padding:12px 16px;font-size:14px;font-weight:600;border-bottom:1px solid #eceee6'>{$data['tarih']}</td></tr>"
               . "<tr><td style='padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px'>Toplam</td><td style='padding:12px 16px;font-size:18px;font-weight:700;color:#6f7320'>{$data['tutar']} ₺</td></tr>"
               . "</table>"
               . $urunlerBlokA;
        return self::wrap('🛒 Yeni Sipariş', $govdeA);
    }
    
    /**
     * Hoş geldin e-posta şablonu
     */
    private static function getWelcomeEmailTemplate($data)
    {
        $govde = "<p style='margin:0 0 18px'>Merhaba <strong style='color:#6f7320'>{$data['musteri_adi']}</strong>,</p>"
               . "<p style='margin:0 0 22px'>{$data['firma_adi']} ailesine hoş geldiniz! Hesabınız başarıyla oluşturuldu.</p>"
               . self::buton($data['site_url'], 'Siteye Git')
               . "<p style='margin:0;color:#7a8270;font-size:14px'>Sorularınız için bizimle iletişime geçmekten çekinmeyin.</p>";

        return self::wrap('🎉 Hoş Geldiniz!', $govde);
    }
    
    /**
     * Doğrulama kodu e-posta gönder
     */
    public static function sendVerificationCode($email, $kod, $tip = 'sifre_sifirlama')
    {
        try {
            $ayarlar = DB::table('ayarlar')->first();
            $firmaAdi = $ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım';
            
            $baslik = $tip == 'sifre_sifirlama' ? 'Şifre Sıfırlama Kodu' : 'Doğrulama Kodu';
            $mesaj = $tip == 'sifre_sifirlama' 
                ? 'Şifrenizi sıfırlamak için doğrulama kodunuz:'
                : 'İşleminizi tamamlamak için doğrulama kodunuz:';
            
            $govde = "<p style='margin:0 0 18px'>Merhaba,</p>"
                   . "<p style='margin:0 0 8px'>{$mesaj}</p>"
                   . self::kodKutusu($kod)
                   . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td style='background-color:#fff8e6;border-left:4px solid #e0a800;border-radius:8px;padding:14px 18px'>"
                   . "<p style='margin:0;font-size:13px;color:#8a6d00;line-height:1.6'><strong>⚠️ Önemli:</strong> Bu kodu kimseyle paylaşmayın. Kod <strong>10 dakika</strong> geçerlidir. Eğer bu işlemi siz yapmadıysanız, lütfen hesabınızı kontrol edin.</p>"
                   . "</td></tr></table>";

            $template = self::wrap($baslik, $govde);

            return self::sendMail($email, $baslik, $template);
        } catch (\Exception $e) {
            Log::error('Doğrulama kodu e-posta gönderilemedi', ['error' => $e->getMessage(), 'email' => $email]);
            return false;
        }
    }

    // ════════════════════════════════════════════════════════════
    // ORTAK MAIL TASARIMI (lime tema + logo) — tum sablonlar bunu kullanir
    // ════════════════════════════════════════════════════════════

    /**
     * Ortak mail iskeleti: logo + lime serit + icerik + footer.
     */
    private static function wrap(string $baslik, string $govde): string
    {
        $ayarlar  = DB::table('ayarlar')->first();
        $firmaAdi = $ayarlar->firma_adi ?? $ayarlar->site_baslik ?? 'DN İş Ortağım';
        $siteUrl  = $ayarlar->site_url ?? 'https://crm.ornek.com/';
        $logoUrl  = 'https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png';
        $yil      = date('Y');

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419;-webkit-font-smoothing:antialiased">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:32px 36px 24px;text-align:center;border-bottom:1px solid #f0f1ec">
      <img src="'.$logoUrl.'" alt="'.htmlspecialchars($firmaAdi, ENT_QUOTES, 'UTF-8').'" width="148" style="display:block;margin:0 auto;max-width:148px;height:auto;border:0">
    </td></tr>
    <tr><td style="padding:30px 36px;font-size:15px;line-height:1.7;color:#3a4133">
      '.($baslik ? '<h2 style="margin:0 0 18px;font-size:20px;font-weight:700;color:#1f2419">'.htmlspecialchars($baslik, ENT_QUOTES, 'UTF-8').'</h2>' : '').'
      '.$govde.'
    </td></tr>
    <tr><td style="padding:22px 36px;background-color:#f7f8f3;border-top:1px solid #eceee6;text-align:center">
      <p style="margin:0 0 8px;font-size:13px;color:#5a6150;font-weight:600">'.htmlspecialchars($firmaAdi, ENT_QUOTES, 'UTF-8').'</p>
      <p style="margin:0 0 10px;font-size:12px;color:#9aa08e;line-height:1.6">Bu otomatik bir bildirim e-postasıdır.</p>
      <p style="margin:0;font-size:11px;color:#b3b8a8">© '.$yil.' '.htmlspecialchars($firmaAdi, ENT_QUOTES, 'UTF-8').' &middot; <a href="'.htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8').'" style="color:#8a8a1f;text-decoration:none">'.htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8').'</a></p>
    </td></tr>
  </table>
</td></tr>
</table></body></html>';
    }

    /**
     * Buyuk kod kutusu (dogrulama kodu icin).
     */
    private static function kodKutusu(string $kod): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 22px"><tr>'
             . '<td align="center" style="background-color:#f7f8f3;border:2px dashed #cfd0b0;border-radius:14px;padding:24px 20px">'
             . '<div style="font-size:12px;color:#9aa08e;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px">Doğrulama Kodu</div>'
             . '<div style="font-size:40px;font-weight:800;color:#6f7320;letter-spacing:10px;font-family:Courier New,monospace">'.htmlspecialchars($kod, ENT_QUOTES, 'UTF-8').'</div>'
             . '</td></tr></table>';
    }

    /**
     * Lime CTA buton.
     */
    private static function buton(string $url, string $label): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 20px"><tr><td align="center">'
             . '<a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" style="display:inline-block;background-color:#b8b62e;color:#1f2419;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;box-shadow:0 6px 16px rgba(184,182,46,0.32)">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</a>'
             . '</td></tr></table>';
    }
}