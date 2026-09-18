<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * MÜŞTERİ BİLDİRİM MERKEZ SERVİSİ
 * 
 * Her müşteri olayı buradan tetiklenir:
 *   CustomerNotifier::faturaKesildi($uyeId, $faturaId);
 *   CustomerNotifier::teklifGonderildi($uyeId, $teklifId);
 *   ...
 * 
 * Her metot:
 *   1. Müşteri tercihini kontrol eder (sadece kampanya için)
 *   2. HTML mail içeriğini hazırlar
 *   3. EmailNotificationService::send() ile gönderir
 *   4. musteri_bildirimleri tablosuna log atar
 *   5. WhatsApp hook'u boş bırakır (ileride)
 * 
 * KULLANIM:
 *   - Sadece SELECT'ten sonra çağır (DB transaction güvenliği için değil)
 *   - Hata fırlatmaz, sessizce log'lar (controller akışını bozmaz)
 *   - throw'a izin verirsin: CustomerNotifier::faturaKesildi(..., throwOnError: true)
 */
class CustomerNotifier
{
    // ════════════════════════════════════════════════════════════
    // 🔴 KATEGORİ 1: ZORUNLU MAILLER (tercih kontrolsüz)
    // ════════════════════════════════════════════════════════════

    /**
     * Fatura kesildi
     */
    public static function faturaKesildi($uyeId, $faturaId, array $extra = []): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'fatura_kesildi', 'atlandi', 'Üye bulunamadı', 'faturalar', $faturaId);

        $fatura = DB::table('faturalar')->where('id', $faturaId)->first();
        if (!$fatura) return self::log($uyeId, 'mail', 'fatura_kesildi', 'atlandi', 'Fatura bulunamadı', 'faturalar', $faturaId);

        // 'toplam' kolonu 0/boş olabilir (?? sadece NULL'da geçer, 0'da değil) -> 0 ise 'tutar'a düş
        $tutarDeger = (float) ($fatura->toplam ?? 0);
        if ($tutarDeger <= 0) {
            $tutarDeger = (float) ($fatura->tutar ?? 0);
        }
        $tutar = number_format($tutarDeger, 2, ',', '.');
        $baslik = "Yeni Faturanız: #{$fatura->fatura_no}";
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Hesabınıza yeni bir fatura tanımlandı.</p>" .
            self::infoBox([
                'Fatura No' => $fatura->fatura_no ?? '—',
                'Başlık'    => $fatura->baslik ?? '—',
                'Tutar'     => "₺{$tutar}",
                'Vade'      => $fatura->bitis_tarih ?? '—',
                'Hizmet'    => $fatura->hizmet ?? '—',
            ]) .
            "<p style='margin-top:16px'>Faturayı görüntülemek için panele giriş yapabilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'fatura_kesildi', 'faturalar', $faturaId);
    }

    /**
     * Ödeme onaylandı
     */
    public static function odemeOnaylandi($uyeId, $faturaId = null, ?float $tutar = null): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'odeme_onaylandi', 'atlandi', 'Üye bulunamadı', 'faturalar', $faturaId);

        $tutarStr = $tutar !== null ? '₺' . number_format($tutar, 2, ',', '.') : '—';
        $baslik = 'Ödemeniz Onaylandı ✓';
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Ödemeniz başarıyla onaylandı ve hesabınıza yansıtıldı.</p>" .
            self::infoBox([
                'Tutar'  => $tutarStr,
                'Tarih'  => date('d.m.Y H:i'),
                'Fatura' => $faturaId ? "#{$faturaId}" : '—',
            ]) .
            "<p style='margin-top:16px'>Hizmetlerimizi kullanmaya devam edebilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'odeme_onaylandi', 'faturalar', $faturaId);
    }

    /**
     * Bakiye yüklendi
     */
    public static function bakiyeYuklendi($uyeId, float $tutar, ?float $yeniBakiye = null): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'bakiye_yuklendi', 'atlandi', 'Üye bulunamadı');

        $tutarStr = '₺' . number_format($tutar, 2, ',', '.');
        $bakiyeStr = $yeniBakiye !== null ? '₺' . number_format($yeniBakiye, 2, ',', '.') : '—';

        $baslik = "Bakiyenize {$tutarStr} Yüklendi";
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Hesabınıza bakiye yüklendi.</p>" .
            self::infoBox([
                'Yüklenen' => $tutarStr,
                'Tarih'    => date('d.m.Y H:i'),
                'Güncel Bakiye' => $bakiyeStr,
            ]) .
            "<p style='margin-top:16px'>Bakiyenizi kullanarak hizmet satın alabilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'bakiye_yuklendi');
    }

    /**
     * Şifre değişti (güvenlik bildirimi)
     */
    public static function sifreDegisti($uyeId, ?string $ip = null): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'sifre_degisti', 'atlandi', 'Üye bulunamadı');

        $baslik = '🔒 Şifreniz Değiştirildi - Güvenlik Bildirimi';
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p><strong>Hesabınızın şifresi az önce değiştirildi.</strong></p>" .
            self::infoBox([
                'Tarih' => date('d.m.Y H:i'),
                'IP'    => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '—'),
            ]) .
            "<p style='margin-top:16px;color:#dc2626'><strong>Bu işlemi siz yapmadıysanız:</strong> Hemen bizimle iletişime geçin ve hesabınızı kontrol edin.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'sifre_degisti');
    }

    /**
     * Hesap engellendi
     */
    public static function hesapEngellendi($uyeId, ?string $sebep = null): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'hesap_engellendi', 'atlandi', 'Üye bulunamadı');

        $baslik = 'Hesabınız Askıya Alındı';
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Hesabınız geçici olarak askıya alınmıştır.</p>" .
            ($sebep ? "<p><strong>Sebep:</strong> " . e($sebep) . "</p>" : '') .
            "<p style='margin-top:16px'>Daha fazla bilgi için müşteri hizmetlerimizle iletişime geçebilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'hesap_engellendi');
    }

    /**
     * Hesap tekrar aktif edildi
     */
    public static function hesapAktifEdildi($uyeId): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'hesap_aktif_edildi', 'atlandi', 'Üye bulunamadı');

        $baslik = 'Hesabınız Tekrar Aktif ✓';
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Hesabınızın engeli kaldırıldı. Tekrar sisteme giriş yapabilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'hesap_aktif_edildi');
    }

    // ════════════════════════════════════════════════════════════
    // 🟡 KATEGORİ 2: ÖNERİLEN MAILLER
    // ════════════════════════════════════════════════════════════

    /**
     * Yeni teklif gönderildi (CRM fırsat)
     * NOT: CRM müşterileri uyeler tablosunda olmayabilir, crm_customers ayrı.
     */
    public static function teklifGonderildi($crmCustomerId, $opportunityId): bool
    {
        $crm = DB::table('crm_customers')->where('id', $crmCustomerId)->first();
        if (!$crm || empty($crm->email)) {
            return self::logRaw(null, $crmCustomerId, null, 'mail', 'teklif_gonderildi', 'atlandi', 'CRM müşteri/email bulunamadı', 'crm_opportunities', $opportunityId);
        }

        $teklif = DB::table('crm_opportunities')->where('id', $opportunityId)->first();
        if (!$teklif) {
            return self::logRaw(null, $crmCustomerId, $crm->email, 'mail', 'teklif_gonderildi', 'atlandi', 'Teklif bulunamadı', 'crm_opportunities', $opportunityId);
        }

        $tutar = number_format((float)($teklif->tutar ?? 0), 2, ',', '.');
        $paraBirim = $teklif->para_birimi ?? 'TRY';
        $sembol = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€', 'AED' => 'د.إ'][$paraBirim] ?? '';

        $baslik = "Size Özel Teklifimiz: {$teklif->baslik}";
        $icerik = self::mailWrap(
            "Sayın " . ($crm->unvan ?: $crm->adi) . ",",
            "<p>Size özel bir teklif hazırladık:</p>" .
            self::infoBox([
                'Başlık' => $teklif->baslik,
                'Tutar'  => "{$sembol}{$tutar}",
                'Geçerlilik' => $teklif->beklenen_kapanis ?? '—',
            ]) .
            ($teklif->aciklama ? "<p style='margin-top:16px'><strong>Açıklama:</strong><br>" . nl2br(e($teklif->aciklama)) . "</p>" : '') .
            "<p style='margin-top:16px'>Detaylar için bizimle iletişime geçebilirsiniz.</p>"
        );

        return self::gonderCrm($crm, $baslik, $icerik, 'teklif_gonderildi', 'crm_opportunities', $opportunityId);
    }

    /**
     * Hosting alındı/yenilendi
     * NOT: hosting_satislar.musteri string olarak tutuluyor — uye tespiti yapamayabiliriz.
     * O yüzden hem uyeId hem email parametresi destekliyoruz.
     */
    public static function hostingAlindi($uyeIdOrEmail, $satisId, ?string $paketAdi = null): bool
    {
        // Üye ID veya email olabilir
        if (is_numeric($uyeIdOrEmail)) {
            $uye = self::uyeBul($uyeIdOrEmail);
            if (!$uye) return self::log($uyeIdOrEmail, 'mail', 'hosting_alindi', 'atlandi', 'Üye bulunamadı', 'hosting_satislar', $satisId);
        } else {
            $uye = (object)['ad' => 'Değerli Müşterimiz', 'soyad' => '', 'email' => $uyeIdOrEmail, 'id' => null];
            if (!self::validEmail($uyeIdOrEmail)) {
                return self::logRaw(null, null, $uyeIdOrEmail, 'mail', 'hosting_alindi', 'atlandi', 'Geçersiz email', 'hosting_satislar', $satisId);
            }
        }

        $baslik = '🖥️ Hosting Hizmetiniz Aktif Edildi';
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Hosting hizmetiniz aktif edildi ve kullanıma hazırdır.</p>" .
            ($paketAdi ? self::infoBox(['Paket' => $paketAdi]) : '') .
            "<p style='margin-top:16px'>Hesap bilgileriniz ayrı bir mailde iletilecektir. Sorularınız için bizimle iletişime geçebilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'hosting_alindi', 'hosting_satislar', $satisId);
    }

    /**
     * Domain alındı/aktif edildi
     */
    public static function domainAlindi($uyeId, $domainOrderId): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'domain_alindi', 'atlandi', 'Üye bulunamadı', 'domain_orders', $domainOrderId);

        $order = DB::table('domain_orders')->where('id', $domainOrderId)->first();
        if (!$order) return self::log($uyeId, 'mail', 'domain_alindi', 'atlandi', 'Domain order bulunamadı', 'domain_orders', $domainOrderId);

        $baslik = "🌐 Domain Aktif: {$order->domain}";
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Domain kaydınız başarıyla tamamlandı.</p>" .
            self::infoBox([
                'Domain'      => $order->domain,
                'Süre'        => ($order->years ?? 1) . ' yıl',
                'Bitiş Tarihi'=> $order->expires_at ?? '—',
            ]) .
            "<p style='margin-top:16px'>DNS ayarlarınızı kontrol panelinden yapabilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'domain_alindi', 'domain_orders', $domainOrderId);
    }

    /**
     * Ticket cevaplandı / durum değişti
     */
    public static function ticketCevaplandi($uyeId, $ticketId, ?string $durum = null): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'ticket_cevaplandi', 'atlandi', 'Üye bulunamadı', 'destek', $ticketId);

        $durumMetin = $durum ? " — Durum: " . self::ticketDurumLabel($durum) : '';
        $baslik = "🎫 Destek Talebiniz Güncellendi{$durumMetin}";
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Destek talebinizde yeni bir güncelleme var.</p>" .
            self::infoBox([
                'Talep No' => "#{$ticketId}",
                'Durum'    => self::ticketDurumLabel($durum ?? '1'),
            ]) .
            "<p style='margin-top:16px'>Detaylar için panelinizden talebi görüntüleyebilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'ticket_cevaplandi', 'destek', $ticketId);
    }

    /**
     * Hizmet süresi dolmak üzere (cron tarafından çağrılır)
     */
    public static function hizmetSuresiDoluyor($uyeId, $hizmetId, int $kalanGun, ?string $hizmetAdi = null): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'hizmet_suresi_doluyor', 'atlandi', 'Üye bulunamadı', 'hizmetler', $hizmetId);

        $baslik = "⏰ Hizmetiniz {$kalanGun} Gün Sonra Dolacak";
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<p>Bir hizmetinizin süresi yakında dolacak.</p>" .
            self::infoBox([
                'Hizmet' => $hizmetAdi ?? '—',
                'Kalan'  => "{$kalanGun} gün",
            ]) .
            "<p style='margin-top:16px'>Kesintisiz hizmet için lütfen yenilemenizi unutmayın.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'hizmet_suresi_doluyor', 'hizmetler', $hizmetId);
    }

    /**
     * Kampanya bildirimi (TERCIH KONTROLÜ YAPAR - sadece izin verenler alır)
     */
    public static function kampanyaBildirimi($uyeId, $kampanyaId): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'kampanya_bildirimi', 'atlandi', 'Üye bulunamadı', 'kampanyalar', $kampanyaId);

        // KVKK: kampanya izni kontrol
        $izinKolonu = Schema::hasColumn('uyeler', 'kampanya_mail_izin') ? 'kampanya_mail_izin' : 'email_bildirim';
        if ((int)($uye->$izinKolonu ?? 0) !== 1) {
            return self::log($uyeId, 'mail', 'kampanya_bildirimi', 'devre_disi', 'Kampanya izni yok', 'kampanyalar', $kampanyaId);
        }

        $kampanya = DB::table('kampanyalar')->where('id', $kampanyaId)->first();
        if (!$kampanya) return self::log($uyeId, 'mail', 'kampanya_bildirimi', 'atlandi', 'Kampanya bulunamadı', 'kampanyalar', $kampanyaId);

        $indirim = $kampanya->indirim > 0 ? "%{$kampanya->indirim}" : '';
        $baslik = "🎉 {$kampanya->baslik}" . ($indirim ? " - {$indirim} İndirim!" : '');
        $icerik = self::mailWrap(
            "Sayın {$uye->ad} {$uye->soyad},",
            "<h3 style='color:#b8b62e;margin-top:0'>{$kampanya->baslik}</h3>" .
            ($kampanya->aciklama ? "<p>" . nl2br(e($kampanya->aciklama)) . "</p>" : '') .
            ($kampanya->bitis_tarihi ? self::infoBox(['Geçerlilik' => "Son: " . date('d.m.Y', strtotime($kampanya->bitis_tarihi))]) : '') .
            ($kampanya->link ? "<p style='margin-top:20px;text-align:center'><a href='" . e($kampanya->link) . "' style='background:#b8b62e;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600'>Fırsatı Yakala</a></p>" : '') .
            "<hr style='margin:24px 0;border:0;border-top:1px solid #e5e7eb'>" .
            "<p style='font-size:11px;color:#9ca3af'>Bu kampanya mailini almak istemiyorsanız panel ayarlarından devre dışı bırakabilirsiniz.</p>"
        );

        return self::gonder($uye, $baslik, $icerik, 'kampanya_bildirimi', 'kampanyalar', $kampanyaId);
    }

    /**
     * Hoşgeldin maili (yeni üye kayıt)
     */
    public static function hosgeldin($uyeId): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return self::log($uyeId, 'mail', 'hosgeldin', 'atlandi', 'Üye bulunamadı');

        $firmaAdi = self::ayar('firma_adi', 'DN İş Ortağım');
        $siteUrl  = self::ayar('site_url', 'https://crm.ornek.com/');
        $ad       = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: 'Değerli üyemiz';

        $baslik = "Hoş Geldiniz - {$firmaAdi}";

        // ── Neler yapabilirsiniz listesi ──
        $yapabilecekler = ['Yeni siparişlere teklif ver ve iş kazan', 'Sipariş durumlarını gerçek zamanlı takip et', 'Profilini ekleyerek kendini tanıt', 'Destek ekibimizle her an iletişime geç'];
        $liste = '';
        foreach ($yapabilecekler as $y) {
            $liste .= "<tr><td style='padding:11px 0;border-bottom:1px solid #eceee6;font-size:14px;color:#3a4133'><span style='color:#b8b62e;font-weight:700;margin-right:10px'>&rarr;</span>" . e($y) . "</td></tr>";
        }

        // ── Ofis kartları ──
        $ofis = function ($ulke, $sehir, $alt1, $alt2, $alt3, $vurgu = false) {
            $renkSehir = $vurgu ? '#b8b62e' : '#3a4133';
            return "<td style='padding:6px;vertical-align:top;width:33%'>"
                 . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border:1px solid #eceee6;border-radius:12px'>"
                 . "<tr><td style='padding:16px 12px;text-align:center'>"
                 . "<div style='font-size:12px;color:#9aa08e;font-weight:600;letter-spacing:1px'>" . e($ulke) . "</div>"
                 . "<div style='font-size:15px;font-weight:800;color:{$renkSehir};margin:4px 0 8px'>" . e($sehir) . "</div>"
                 . "<div style='font-size:12px;color:#7a8270;line-height:1.6'>" . e($alt1) . "<br>" . e($alt2) . "<br>" . e($alt3) . "</div>"
                 . "</td></tr></table></td>";
        };

        $govde =
            "<p style='margin:0 0 6px;font-size:18px;font-weight:800;color:#1f2419'>Merhaba, " . e($ad) . "! 👋</p>"
          . "<p style='margin:0 0 18px;font-size:15px;font-weight:700;color:#6f7320'>{$firmaAdi} ailesine katıldınız! 🚀</p>"
          . "<p style='margin:0 0 8px'>Sizi aramızda görmekten büyük mutluluk duyuyoruz.</p>"
          . "<p style='margin:0 0 22px'>Artık siparişleri takip edebilir, teklif verebilir ve projelerinizi kolayca yönetebilirsiniz.</p>"

          // 500+ ailesi kutusu
          . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin:0 0 24px'><tr>"
          . "<td align='center' style='background-color:#f7f8f3;border:1px solid #eceee6;border-radius:14px;padding:24px 20px'>"
          . "<div style='font-size:12px;color:#9aa08e;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px'>👥 Aramıza Katılan</div>"
          . "<div style='font-size:44px;font-weight:800;color:#6f7320;line-height:1'>500+</div>"
          . "<div style='font-size:14px;font-weight:700;color:#3a4133;margin:8px 0 6px'>Ailemizin Parçasısınız! 🏆</div>"
          . "<div style='font-size:13px;color:#7a8270;line-height:1.6'>Büyüyen ailemize hoş geldiniz,<br>başarılarla dolu bir yolculuk dileriz.</div>"
          . "</td></tr></table>"

          // Neler yapabilirsiniz
          . "<p style='margin:0 0 10px;font-size:14px;font-weight:700;color:#1f2419'>🚀 Neler Yapabilirsiniz?</p>"
          . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin:0 0 24px'>" . $liste . "</table>"

          // Buton
          . self::button($siteUrl, 'Hemen Başla →')

          // Global ofisler
          . "<p style='margin:24px 0 12px;text-align:center;font-size:13px;font-weight:700;color:#6f7320;letter-spacing:1px'>✦ GLOBAL AĞIMIZ</p>"
          . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin:0 0 8px'><tr>"
          . $ofis('TR', 'İSTANBUL', 'Fulya Mah.', 'Bahçeler Sok. No:9/A', 'Şişli')
          . $ofis('TR', 'MARMARİS', 'Armutalan Mah.', 'Mavikent Sitesi', 'Muğla', true)
          . $ofis('AE', 'DUBAI', 'Meydan Freezone', 'The Meydan Hotel', 'U.A.E.')
          . "</tr></table>";

        $icerik = self::mailWrap("", $govde);

        return self::gonder($uye, $baslik, $icerik, 'hosgeldin');
    }

    /**
     * Yeni üye kaydında ADMIN'e bilgi maili + uygulama içi bildirim.
     * UyeController::store icinde hosgeldin() ile birlikte cagrilir.
     */
    public static function yeniUyeAdminBildirim($uyeId): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye) return false;

        $firmaAdi = self::ayar('firma_adi', 'DN İş Ortağım');
        $adSoyad  = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: ($uye->email ?? 'Yeni üye');
        $panelUrl = rtrim(self::ayar('site_url', 'https://crm.ornek.com/'), '/') . '/admin/uyeler/' . $uye->id . '/detay';

        // 1) Uygulama ici bildirim (admin_bildirimler) — gercek sema
        try {
            if (Schema::hasTable('admin_bildirimler')) {
                DB::table('admin_bildirimler')->insert([
                    'tip'          => 'bayi',   // index.blade tip->ikon: 'bayi' = briefcase mavi; yeni uye icin uygun
                    'baslik'       => 'Yeni Üye Kaydı',
                    'mesaj'        => "{$adSoyad} platforma yeni üye oldu.",
                    'ilgili_id'    => $uye->id,
                    'ilgili_tablo' => 'uyeler',
                    'okundu'       => 0,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('yeniUyeAdminBildirim: uygulama ici bildirim hatasi', ['err' => $e->getMessage()]);
        }

        // 2) Admin'e bilgi maili
        try {
            $adminMail = self::ayar('firma_email', null) ?: self::ayar('admin_email', null);
            if ($adminMail) {
                $govde = "<p style='margin:0 0 18px;font-size:16px;font-weight:700;color:#1f2419'>Merhaba 📋</p>"
                       . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin:0 0 18px'><tr>"
                       . "<td style='background-color:#f7f8f3;border-left:4px solid #b8b62e;border-radius:8px;padding:16px 18px;font-size:14px;color:#3a4133;line-height:1.7'>"
                       . "<strong style='color:#6f7320'>" . e($adSoyad) . "</strong>"
                       . (!empty($uye->email) ? " (<a href='mailto:" . e($uye->email) . "' style='color:#8a8a1f;text-decoration:none'>" . e($uye->email) . "</a>)" : "")
                       . " platforma yeni üye oldu. Profilini incelemek için panele gidebilirsiniz."
                       . "</td></tr></table>"
                       . self::button($panelUrl, 'Yönetime Git →');
                $html = self::mailWrap("", $govde);
                self::sendMail($adminMail, null, null, "🎉 Yeni Üye Kaydı - {$firmaAdi}", $html, 'yeni_uye_admin', null, null);
            }
        } catch (\Throwable $e) {
            Log::warning('yeniUyeAdminBildirim: admin maili hatasi', ['err' => $e->getMessage()]);
        }

        return true;
    }

    // ════════════════════════════════════════════════════════════
    // GENEL YARDIMCILAR — her controller'dan cagrilabilir
    // ════════════════════════════════════════════════════════════

    /**
     * Bir uyeye (musteriye) ortak tasarimli mail gonderir.
     * Ornek: CustomerNotifier::musteriyeMail($uyeId, 'Yeni Faturanız', 'Hesabınıza...', $link, 'Faturayı Gör');
     */
    public static function musteriyeMail($uyeId, string $baslik, string $mesajHtml, ?string $link = null, ?string $butonYazi = null, string $olay = 'genel'): bool
    {
        $uye = self::uyeBul($uyeId);
        if (!$uye || empty($uye->email)) return false;

        $ad = trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: 'Değerli müşterimiz';

        $govde = "<p style='margin:0 0 18px;font-size:16px;font-weight:700;color:#1f2419'>Merhaba " . e($ad) . ",</p>"
               . "<div style='font-size:15px;line-height:1.7;color:#3a4133;margin:0 0 18px'>" . $mesajHtml . "</div>";
        if ($link) {
            $govde .= self::button($link, $butonYazi ?: 'Detayları Gör →');
        }

        $icerik = self::mailWrap("", $govde);
        return self::gonder($uye, $baslik, $icerik, $olay);
    }

    /**
     * crm_customers tablosundaki bir musteriye mail (uye_id yoksa).
     */
    public static function crmMusteriyeMail($crmId, string $baslik, string $mesajHtml, ?string $link = null, ?string $butonYazi = null, string $olay = 'genel'): bool
    {
        try {
            $crm = DB::table('crm_customers')->where('id', $crmId)->first();
        } catch (\Throwable $e) { return false; }
        if (!$crm || empty($crm->email)) return false;

        $ad = $crm->adi ?? 'Değerli müşterimiz';
        $govde = "<p style='margin:0 0 18px;font-size:16px;font-weight:700;color:#1f2419'>Merhaba " . e($ad) . ",</p>"
               . "<div style='font-size:15px;line-height:1.7;color:#3a4133;margin:0 0 18px'>" . $mesajHtml . "</div>";
        if ($link) {
            $govde .= self::button($link, $butonYazi ?: 'Detayları Gör →');
        }
        $icerik = self::mailWrap("", $govde);
        return self::gonderCrm($crm, $baslik, $icerik, $olay);
    }

    /**
     * Admin'lere uygulama ici bildirim ekler (admin_bildirimler).
     * Ornek: CustomerNotifier::adminBildirim('Yeni Destek Talebi', 'X yeni ticket actı', 'destek', 222, 'destek');
     */
    public static function adminBildirim(string $baslik, string $mesaj, string $tip = 'system', $ilgiliId = null, ?string $ilgiliTablo = null): bool
    {
        try {
            if (!Schema::hasTable('admin_bildirimler')) return false;
            DB::table('admin_bildirimler')->insert([
                'tip'          => $tip,
                'baslik'       => $baslik,
                'mesaj'        => $mesaj,
                'ilgili_id'    => $ilgiliId,
                'ilgili_tablo' => $ilgiliTablo,
                'okundu'       => 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('adminBildirim hatasi', ['err' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Admin'e bilgi maili (firma_email / admin_email adresine).
     */
    public static function adminMail(string $baslik, string $mesajHtml, ?string $link = null, ?string $butonYazi = null): bool
    {
        try {
            $adminMail = self::ayar('firma_email', null) ?: self::ayar('admin_email', null);
            if (!$adminMail) return false;
            $govde = "<div style='font-size:15px;line-height:1.7;color:#3a4133;margin:0 0 18px'>" . $mesajHtml . "</div>";
            if ($link) $govde .= self::button($link, $butonYazi ?: 'Yönetime Git →');
            $html = self::mailWrap("", $govde);
            self::sendMail($adminMail, null, null, $baslik, $html, 'admin_bilgi', null, null);
            return true;
        } catch (\Throwable $e) {
            Log::warning('adminMail hatasi', ['err' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Üyeyi DB'den çek (schema-aware)
     */
    protected static function uyeBul($id)
    {
        if (!$id || !is_numeric($id)) return null;
        try {
            return DB::table('uyeler')->where('id', $id)->first();
        } catch (\Throwable $e) {
            Log::warning('CustomerNotifier::uyeBul hata', ['id' => $id, 'err' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * uyeler tablosundan üyeye gönder
     */
    protected static function gonder($uye, string $baslik, string $icerik, string $olay, ?string $ilgiliTablo = null, $ilgiliId = null): bool
    {
        // Email kontrolü
        if (empty($uye->email) || !self::validEmail($uye->email)) {
            return self::log($uye->id ?? null, 'mail', $olay, 'atlandi', 'Geçersiz/boş email', $ilgiliTablo, $ilgiliId);
        }

        // Genel mail bildirimi kapalı mı? (email_bildirim kolonu)
        // NOT: KRİTİK güvenlik bildirimleri (sifre_degisti, hesap_engellendi) bu kontrolü atlar
        $kritikOlaylar = ['sifre_degisti', 'hesap_engellendi', 'hesap_aktif_edildi'];
        if (!in_array($olay, $kritikOlaylar)) {
            if (Schema::hasColumn('uyeler', 'email_bildirim')) {
                $bildirim = (int)($uye->email_bildirim ?? 1);
                if ($bildirim !== 1) {
                    // KVKK: Müşteri genel mail almak istemiyor
                    // Ancak fatura/ödeme gibi ZORUNLU mailleri yine de gönderelim
                    $zorunluOlaylar = ['fatura_kesildi', 'odeme_onaylandi', 'bakiye_yuklendi'];
                    if (!in_array($olay, $zorunluOlaylar)) {
                        return self::log($uye->id ?? null, 'mail', $olay, 'devre_disi', 'Mail bildirimi kapalı', $ilgiliTablo, $ilgiliId);
                    }
                }
            }
        }

        return self::sendMail($uye->email, $uye->id ?? null, null, $baslik, $icerik, $olay, $ilgiliTablo, $ilgiliId);
    }

    /**
     * crm_customers tablosundan müşteriye gönder (uye_id YOK)
     */
    protected static function gonderCrm($crm, string $baslik, string $icerik, string $olay, ?string $ilgiliTablo = null, $ilgiliId = null): bool
    {
        if (empty($crm->email) || !self::validEmail($crm->email)) {
            return self::logRaw(null, $crm->id, $crm->email ?? null, 'mail', $olay, 'atlandi', 'Geçersiz email', $ilgiliTablo, $ilgiliId);
        }
        return self::sendMail($crm->email, null, $crm->id, $baslik, $icerik, $olay, $ilgiliTablo, $ilgiliId);
    }

    /**
     * Gerçek mail gönderme (EmailNotificationService aracılığıyla)
     */
    protected static function sendMail(string $email, ?int $uyeId, ?int $crmId, string $baslik, string $icerik, string $olay, ?string $ilgiliTablo, $ilgiliId): bool
    {
        try {
            if (class_exists(\App\Services\EmailNotificationService::class)) {
                \App\Services\EmailNotificationService::send($email, $baslik, $icerik, true);
            } else {
                // Fallback: Laravel Mail
                \Illuminate\Support\Facades\Mail::html($icerik, function($m) use ($email, $baslik) {
                    $m->to($email)->subject($baslik);
                });
            }

            self::logRaw($uyeId, $crmId, $email, 'mail', $olay, 'gonderildi', null, $ilgiliTablo, $ilgiliId, $baslik, $icerik);
            return true;
        } catch (\Throwable $e) {
            Log::error('CustomerNotifier mail gönderim hatası', [
                'email' => $email, 'olay' => $olay, 'err' => $e->getMessage()
            ]);
            self::logRaw($uyeId, $crmId, $email, 'mail', $olay, 'basarisiz', $e->getMessage(), $ilgiliTablo, $ilgiliId, $baslik);
            return false;
        }
    }

    /**
     * WhatsApp gönder (HOOK - şimdilik placeholder)
     */
    public static function whatsappGonder($uyeIdOrPhone, string $mesaj, string $olay, ?string $ilgiliTablo = null, $ilgiliId = null): bool
    {
        // TODO: WhatsApp Business API entegrasyonu
        // Şimdilik sadece log atıp false dönüyor
        $uyeId = is_numeric($uyeIdOrPhone) ? $uyeIdOrPhone : null;
        $tel = is_numeric($uyeIdOrPhone) ? null : $uyeIdOrPhone;

        self::logRaw($uyeId, null, $tel, 'whatsapp', $olay, 'atlandi', 'WhatsApp henüz aktif değil', $ilgiliTablo, $ilgiliId);
        return false;
    }

    /**
     * Bildirim log - basit (uye_id ile)
     */
    protected static function log($uyeId, string $kanal, string $olay, string $durum, ?string $hata = null, ?string $ilgiliTablo = null, $ilgiliId = null): bool
    {
        return self::logRaw($uyeId, null, null, $kanal, $olay, $durum, $hata, $ilgiliTablo, $ilgiliId);
    }

    /**
     * Bildirim log - tam parametreli
     */
    protected static function logRaw(?int $uyeId, ?int $crmId, ?string $adres, string $kanal, string $olay, string $durum, ?string $hata = null, ?string $ilgiliTablo = null, $ilgiliId = null, ?string $baslik = null, ?string $icerik = null): bool
    {
        if (!Schema::hasTable('musteri_bildirimleri')) return false;

        try {
            DB::table('musteri_bildirimleri')->insert([
                'uye_id'           => $uyeId,
                'crm_customer_id'  => $crmId,
                'gonderilen_adres' => $adres,
                'kanal'            => $kanal,
                'olay'             => $olay,
                'baslik'           => $baslik ? mb_substr($baslik, 0, 255) : null,
                'icerik'           => $icerik,
                'durum'            => $durum,
                'hata_mesaji'      => $hata,
                'ilgili_tablo'     => $ilgiliTablo,
                'ilgili_id'        => $ilgiliId,
                'gonderen_admin_id'=> session('admin_id'),
                'otomatik'         => 1,
                'created_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('CustomerNotifier log yazılamadı', ['err' => $e->getMessage()]);
        }

        return $durum === 'gonderildi';
    }

    /**
     * HTML mail şablonu — modern, marka renkli
     */
    protected static function mailWrap(string $selamlama, string $icerik): string
    {
        $firmaAdi = self::ayar('firma_adi', 'DN İş Ortağım');
        $siteUrl  = self::ayar('site_url', 'https://crm.ornek.com/');
        $firmaTel = self::ayar('firma_telefon', '');
        $firmaMail= self::ayar('firma_email', '');
        $logoUrl  = mail_logo_url();

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419;-webkit-font-smoothing:antialiased">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">

    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>

    <tr><td style="padding:32px 36px 24px;text-align:center;background-color:#ffffff;border-bottom:1px solid #f0f1ec">
      <img src="'.$logoUrl.'" alt="'.e($firmaAdi).'" width="148" style="display:block;margin:0 auto;max-width:148px;height:auto;border:0">
    </td></tr>

    <tr><td style="padding:30px 36px;font-size:15px;line-height:1.7;color:#3a4133">
      <p style="margin:0 0 18px;font-size:16px;font-weight:700;color:#1f2419">'.$selamlama.'</p>
      '.$icerik.'
    </td></tr>

    <tr><td style="padding:22px 36px;background-color:#f7f8f3;border-top:1px solid #eceee6;text-align:center">
      <p style="margin:0 0 8px;font-size:13px;color:#5a6150;font-weight:600">'.e($firmaAdi).'</p>
      <p style="margin:0 0 10px;font-size:12px;color:#9aa08e;line-height:1.6">Bu otomatik bir bildirim e-postasıdır.</p>
      <p style="margin:0;font-size:11px;color:#b3b8a8">© '.date('Y').' '.e($firmaAdi).
        ($firmaTel ? ' &middot; '.e($firmaTel) : '').
        ($firmaMail ? ' &middot; <a href="mailto:'.e($firmaMail).'" style="color:#8a8a1f;text-decoration:none">'.e($firmaMail).'</a>' : '').
        '<br><a href="'.e($siteUrl).'" style="color:#8a8a1f;text-decoration:none">'.e($siteUrl).'</a></p>
    </td></tr>

  </table>
</td></tr>
</table></body></html>';
    }

    /**
     * Bilgi kutusu (key-value tablosu)
     */
    protected static function infoBox(array $data): string
    {
        $rows = '';
        $i = 0;
        $count = count($data);
        foreach ($data as $k => $v) {
            $i++;
            $border = ($i < $count) ? 'border-bottom:1px solid #eceee6;' : '';
            $rows .= '<tr>'
                   . '<td style="padding:12px 16px;background-color:#f7f8f3;color:#7a8270;font-size:13px;width:38%;'.$border.'">' . e($k) . '</td>'
                   . '<td style="padding:12px 16px;color:#3a4133;font-size:14px;font-weight:600;'.$border.'">' . e((string)$v) . '</td>'
                   . '</tr>';
        }
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:16px 0;border:1px solid #eceee6;border-radius:10px;overflow:hidden">' . $rows . '</table>';
    }

    /**
     * Buton (CTA) — mailWrap icindeki $icerik'e eklenir
     */
    protected static function button(string $url, string $label): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:8px 0 20px"><tr><td align="center">'
             . '<a href="' . e($url) . '" style="display:inline-block;background-color:#b8b62e;color:#1f2419;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;box-shadow:0 6px 16px rgba(184,182,46,0.32)">' . e($label) . '</a>'
             . '</td></tr></table>';
    }

    /**
     * Ayar değerini cache'le çek
     */
    protected static $_ayarlarCache = null;
    protected static function ayar(string $key, $default = null)
    {
        if (self::$_ayarlarCache === null) {
            try {
                self::$_ayarlarCache = DB::table('ayarlar')->first();
            } catch (\Throwable $e) {
                self::$_ayarlarCache = (object)[];
            }
        }
        return self::$_ayarlarCache->$key ?? $default;
    }

    /**
     * Email format kontrolü
     */
    protected static function validEmail($email): bool
    {
        return $email && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Ticket durum etiketi
     */
    protected static function ticketDurumLabel($durum): string
    {
        $map = [
            '0' => 'Açık', '1' => 'Yanıtlandı', '2' => 'Kapalı',
            0 => 'Açık', 1 => 'Yanıtlandı', 2 => 'Kapalı',
            'acik' => 'Açık', 'yanitlandi' => 'Yanıtlandı', 'kapali' => 'Kapalı',
        ];
        return $map[$durum] ?? 'Güncellendi';
    }
}