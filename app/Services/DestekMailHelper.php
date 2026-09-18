<?php
/*
================================================================
DestekMailHelper.php — YENİ DOSYA
================================================================
Konum: app/Services/DestekMailHelper.php

Tek noktada toplanmış destek mail bildirim sistemi.
Controller'lardan tek satırla çağrılır:

    \App\Services\DestekMailHelper::yeniTalepMusteridenAdmine($talep, $musteri);
    \App\Services\DestekMailHelper::yeniTalepAdmindenMusteriye($talep, $musteri);
    \App\Services\DestekMailHelper::cevapMusteridenAdmine($talep, $cevap, $musteri);
    \App\Services\DestekMailHelper::cevapAdmindenMusteriye($talep, $cevap, $musteri);

Tüm hata yakalama içeride try-catch ile sarmalanmış. Mail gönderim
hatası ana işlemi (DB insert) bozmaz.
================================================================
*/

namespace App\Services;

use App\Services\EmailNotificationService;

class DestekMailHelper
{
    /**
     * Admin'e gönderilecek mail adresi
     */
    const ADMIN_EMAIL = 'isortagim@ornek.com';

    /**
     * Sitenin görünür domain'i (linkleri kurmak için)
     */
    public static function siteDomain(): string
    {
        // ayarlar tablosundan oku, yoksa fallback
        try {
            $ayar = \Illuminate\Support\Facades\DB::table('ayarlar')->first();
            return rtrim($ayar->site_url ?? config('app.url'), '/');
        } catch (\Throwable $e) {
            return rtrim(config('app.url'), '/');
        }
    }

    /**
     * SCENARIO 1: Müşteri yeni talep açtı → admin'e bildir
     */
    public static function yeniTalepMusteridenAdmine($talep, $musteri): void
    {
        try {
            $musteriAd = trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? '')) ?: ($musteri->email ?? 'Bilinmeyen');
            $musteriEmail = $musteri->email ?? '—';
            $talepId = $talep->id ?? '?';
            $konu = '🎫 Yeni Destek Talebi: ' . ($talep->baslik ?? 'Konu yok');

            $detayUrl = self::siteDomain() . '/admin/destek/' . $talepId . '/detay';

            $html = self::sablon([
                'baslik'   => 'Yeni Destek Talebi',
                'altBaslik'=> 'Bir müşteri yeni bir destek talebi açtı.',
                'satirlar' => [
                    'Müşteri'   => $musteriAd,
                    'E-posta'   => $musteriEmail,
                    'Konu'      => $talep->baslik ?? '—',
                    'Talep #'   => $talepId,
                    'Açılış'    => $talep->tarih ?? date('Y-m-d H:i:s'),
                    'Mesaj'     => nl2br(e($talep->mesaj ?? '')),
                ],
                'butonUrl'  => $detayUrl,
                'butonText' => 'Talebi Görüntüle',
                'renk'      => '#3b82f6',
            ]);

            EmailNotificationService::send(self::ADMIN_EMAIL, $konu, $html);

            // Uygulama ici admin bildirimi
            if (class_exists(\App\Services\CustomerNotifier::class)) {
                \App\Services\CustomerNotifier::adminBildirim(
                    'Yeni Destek Talebi',
                    $musteriAd . ' yeni bir destek talebi açtı: ' . ($talep->baslik ?? ''),
                    'mesaj',
                    $talepId,
                    'destek'
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('DestekMailHelper.yeniTalepMusteridenAdmine: ' . $e->getMessage());
        }
    }

    /**
     * SCENARIO 2: Admin yeni talep açtı (müşteri adına) → müşteriye bildir
     */
    public static function yeniTalepAdmindenMusteriye($talep, $musteri): void
    {
        try {
            if (empty($musteri->email)) return;

            $musteriAd = trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? ''));
            $talepId = $talep->id ?? '?';
            $konu = '🎫 Sizin için bir destek talebi açıldı: ' . ($talep->baslik ?? '');

            $detayUrl = self::siteDomain() . '/destek/' . $talepId;

            $html = self::sablon([
                'baslik'   => 'Sizin Adınıza Destek Talebi Açıldı',
                'altBaslik'=> 'Merhaba ' . e($musteriAd) . ', sizin adınıza bir destek talebi oluşturduk.',
                'satirlar' => [
                    'Konu'    => $talep->baslik ?? '—',
                    'Talep #' => $talepId,
                    'Tarih'   => $talep->tarih ?? date('Y-m-d H:i:s'),
                    'Mesaj'   => nl2br(e($talep->mesaj ?? '')),
                ],
                'butonUrl'  => $detayUrl,
                'butonText' => 'Talebi Görüntüle',
                'renk'      => '#10b981',
            ]);

            EmailNotificationService::send($musteri->email, $konu, $html);
        } catch (\Throwable $e) {
            \Log::warning('DestekMailHelper.yeniTalepAdmindenMusteriye: ' . $e->getMessage());
        }
    }

    /**
     * SCENARIO 3: Müşteri cevap yazdı → admin'e bildir
     */
    public static function cevapMusteridenAdmine($talep, $cevap, $musteri): void
    {
        try {
            $musteriAd = trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? '')) ?: ($musteri->email ?? 'Müşteri');
            $musteriEmail = $musteri->email ?? '—';
            $talepId = $talep->id ?? '?';
            $konu = '💬 Müşteri cevap yazdı: ' . ($talep->baslik ?? 'Talep #' . $talepId);

            $detayUrl = self::siteDomain() . '/admin/destek/' . $talepId . '/detay';

            $html = self::sablon([
                'baslik'   => 'Müşteri Cevap Yazdı',
                'altBaslik'=> e($musteriAd) . ' bir destek talebine cevap yazdı.',
                'satirlar' => [
                    'Müşteri' => $musteriAd,
                    'E-posta' => $musteriEmail,
                    'Talep'   => $talep->baslik ?? '—',
                    'Talep #' => $talepId,
                    'Tarih'   => $cevap->tarih ?? date('Y-m-d H:i:s'),
                    'Cevap'   => nl2br(e($cevap->mesaj ?? '')),
                ],
                'butonUrl'  => $detayUrl,
                'butonText' => 'Talebe Git',
                'renk'      => '#f59e0b',
            ]);

            EmailNotificationService::send(self::ADMIN_EMAIL, $konu, $html);

            // Uygulama ici admin bildirimi
            if (class_exists(\App\Services\CustomerNotifier::class)) {
                \App\Services\CustomerNotifier::adminBildirim(
                    'Destek Talebine Cevap',
                    $musteriAd . ' bir destek talebine cevap yazdı: ' . ($talep->baslik ?? ''),
                    'mesaj',
                    $talepId,
                    'destek'
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('DestekMailHelper.cevapMusteridenAdmine: ' . $e->getMessage());
        }
    }

    /**
     * SCENARIO 4: Admin cevap yazdı → müşteriye bildir
     */
    public static function cevapAdmindenMusteriye($talep, $cevap, $musteri): void
    {
        try {
            if (empty($musteri->email)) return;

            $musteriAd = trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? ''));
            $talepId = $talep->id ?? '?';
            $konu = '💬 Destek Talebinize Cevap: ' . ($talep->baslik ?? 'Talep #' . $talepId);

            $detayUrl = self::siteDomain() . '/destek/' . $talepId;

            $html = self::sablon([
                'baslik'   => 'Destek Talebinize Cevap Verildi',
                'altBaslik'=> 'Merhaba ' . e($musteriAd) . ', destek talebinize bir cevap geldi.',
                'satirlar' => [
                    'Konu'    => $talep->baslik ?? '—',
                    'Talep #' => $talepId,
                    'Tarih'   => $cevap->tarih ?? date('Y-m-d H:i:s'),
                    'Cevap'   => nl2br(e($cevap->mesaj ?? '')),
                ],
                'butonUrl'  => $detayUrl,
                'butonText' => 'Cevabı Görüntüle',
                'renk'      => '#b8b62e',
            ]);

            EmailNotificationService::send($musteri->email, $konu, $html);
        } catch (\Throwable $e) {
            \Log::warning('DestekMailHelper.cevapAdmindenMusteriye: ' . $e->getMessage());
        }
    }

    /**
     * Mail HTML şablonu — lime + logo kurumsal tasarim (wrap ile ayni dil)
     */
    protected static function sablon(array $data): string
    {
        $baslik    = $data['baslik']    ?? 'Bildirim';
        $altBaslik = $data['altBaslik'] ?? '';
        $satirlar  = $data['satirlar']  ?? [];
        $butonUrl  = $data['butonUrl']  ?? '#';
        $butonText = $data['butonText'] ?? 'Detay';
        $logoUrl   = mail_logo_url();
        $siteUrl   = self::siteDomain();
        $firmaAdi  = 'DN İş Ortağım';
        $yil       = date('Y');

        // Satirlar
        $satirHtml = '';
        $i = 0;
        $n = count($satirlar);
        foreach ($satirlar as $label => $value) {
            $i++;
            $sonMu = $i === $n;
            $alt = $sonMu ? '' : 'border-bottom:1px solid #eceee6;';
            $satirHtml .= '<tr>'
                . '<td style="padding:12px 16px;background-color:#f7f8f3;' . $alt . 'font-weight:600;font-size:13px;color:#7a8270;width:34%;vertical-align:top">'
                . e($label) . '</td>'
                . '<td style="padding:12px 16px;' . $alt . 'font-size:14px;color:#3a4133;vertical-align:top;line-height:1.6">'
                . $value
                . '</td></tr>';
        }

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($baslik) . '</title></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:32px 36px 24px;text-align:center;border-bottom:1px solid #f0f1ec">
      <img src="' . $logoUrl . '" alt="' . e($firmaAdi) . '" width="148" style="display:block;margin:0 auto;max-width:148px;height:auto;border:0">
    </td></tr>
    <tr><td style="padding:30px 36px 8px">
      <h2 style="margin:0 0 6px;font-size:20px;font-weight:700;color:#1f2419">' . e($baslik) . '</h2>'
      . ($altBaslik ? '<p style="margin:0 0 20px;font-size:14px;color:#7a8270;line-height:1.6">' . $altBaslik . '</p>' : '') . '
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #eceee6;border-radius:10px;overflow:hidden;margin:0 0 8px">
        ' . $satirHtml . '
      </table>
    </td></tr>
    <tr><td style="padding:8px 36px 30px">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
        <a href="' . e($butonUrl) . '" style="display:inline-block;background-color:#b8b62e;color:#1f2419;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;box-shadow:0 6px 16px rgba(184,182,46,0.32)">' . e($butonText) . ' →</a>
      </td></tr></table>
    </td></tr>
    <tr><td style="padding:22px 36px;background-color:#f7f8f3;border-top:1px solid #eceee6;text-align:center">
      <p style="margin:0 0 8px;font-size:13px;color:#5a6150;font-weight:600">' . e($firmaAdi) . '</p>
      <p style="margin:0 0 10px;font-size:12px;color:#9aa08e;line-height:1.6">Bu otomatik bir bildirim e-postasıdır.</p>
      <p style="margin:0;font-size:11px;color:#b3b8a8">© ' . $yil . ' DN Kreatif &middot; <a href="' . e($siteUrl) . '" style="color:#8a8a1f;text-decoration:none">' . e($siteUrl) . '</a></p>
    </td></tr>
  </table>
</td></tr>
</table></body></html>';
    }
}