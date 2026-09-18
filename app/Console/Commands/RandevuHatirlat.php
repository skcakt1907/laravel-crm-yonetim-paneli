<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use App\Services\RandevuSms;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Randevu hatırlatma döngüsü (cron: her dakika).
 * 1) 30 dk önce: müşteriye SMS+mail, çalışana mail
 * 2) Randevu saatinde: müşteriye bilgilendirme
 * 3) 40 dk sonra: çalışana "süreç doldu" hatırlatması (SMS)
 * 4) 2 saat sonra: müşteriye değerlendirme linki (SMS+mail)
 */
class RandevuHatirlat extends Command
{
    protected $signature   = 'randevu:hatirlat';
    protected $description = 'Randevu SMS/mail hatırlatmalarını ve değerlendirme davetlerini gönderir';

    public function handle(): int
    {
        if (!Schema::hasTable('randevular')) {
            $this->warn('randevular tablosu yok, çıkılıyor.');
            return self::SUCCESS;
        }

        $simdi = Carbon::now();

        $this->ikiSaatOnceHatirlat($simdi);
        $this->otuzDakikaHatirlat($simdi);
        $this->baslangicBildir($simdi);
        $this->kirkDakikaCalisanHatirlat($simdi);
        $this->degerlendirmeDavet($simdi);

        return self::SUCCESS;
    }

    /** Uygulama içi (zil) bildirim — üye eşleşirse uye_bildirimler'e yazar */
    private function uygulamaIciBildir(?int $crmMusteriId, ?string $email, string $baslik, string $mesaj): void
    {
        if (!Schema::hasTable('uye_bildirimler')) return;
        try {
            $uyeId = null;
            if ($crmMusteriId && DB::table('uyeler')->where('id', $crmMusteriId)->exists()) {
                $uyeId = $crmMusteriId;
            } elseif ($email) {
                $uyeId = DB::table('uyeler')->where('email', $email)->value('id');
            }
            if (!$uyeId) return;
            DB::table('uye_bildirimler')->insert([
                'uye_id'     => $uyeId,
                'tip'        => 'randevu',
                'baslik'     => $baslik,
                'mesaj'      => $mesaj,
                'link'       => '/randevularim',
                'okundu'     => 0,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('randevu:hatirlat uygulama içi bildirim', ['e' => $e->getMessage()]);
        }
    }

    /** Randevuya bağlı müşteri iletişim bilgileri */
    private function iletisim(object $r): array
    {
        $ad = $r->musteri_ad ?: 'Müşterimiz';
        $tel = $r->musteri_tel;
        $email = null;
        if (!empty($r->crm_musteri_id)) {
            try {
                $c = DB::table('crm_customers')->where('id', $r->crm_musteri_id)
                    ->first(['adi', 'telefon', 'gsm', 'email']);
                if ($c) {
                    $ad    = $c->adi ?: $ad;
                    $tel   = $c->gsm ?: ($c->telefon ?: $tel);
                    $email = $c->email ?: null;
                }
            } catch (\Throwable $e) {}
        }
        return [$ad, $tel, $email];
    }

    private function detay(object $r): array
    {
        $bas = Carbon::parse($r->baslangic);
        $lokasyon = null; $calisanAd = null; $calisanEmail = null;
        try {
            if (!empty($r->hizmet_id))  $lokasyon = DB::table('randevu_hizmetler')->where('id', $r->hizmet_id)->value('ad');
            if (!empty($r->calisan_id)) {
                $c = DB::table('randevu_calisanlar')->where('id', $r->calisan_id)->first(['ad', 'email']);
                $calisanAd = $c->ad ?? null; $calisanEmail = $c->email ?? null;
            }
        } catch (\Throwable $e) {}
        return [$bas, $lokasyon, $calisanAd, $calisanEmail];
    }

    /**
     * Randevuyu AÇAN (ilgili personel) telefonu.
     * Önce randevu_calisanlar.telefon, boşsa bağlı yoneticiler.telefon kullanılır.
     * NOT: Personelin hiçbirinde numara yoksa SMS gönderilemez — panelden girilmeli.
     */
    private function calisanTelefon(object $r): ?string
    {
        try {
            if (empty($r->calisan_id)) return null;
            $c = DB::table('randevu_calisanlar')->where('id', $r->calisan_id)->first(['telefon', 'yonetici_id']);
            if (!$c) return null;
            $tel = trim((string) ($c->telefon ?? ''));
            if ($tel === '' && !empty($c->yonetici_id)) {
                $tel = trim((string) (DB::table('yoneticiler')->where('id', $c->yonetici_id)->value('telefon') ?? ''));
            }
            return $tel !== '' ? $tel : null;
        } catch (\Throwable $e) { return null; }
    }

    /**
     * Randevuyu OLUŞTURAN personelin iletişim bilgileri: [telefon, email, ad].
     *
     * Bildirim yalnızca randevuyu açan kişiye gider — tüm yöneticilere DEĞİL.
     *  1) randevular.olusturan_id → yoneticiler tablosundan telefon/e-posta
     *  2) Oluşturan kayıtlı değilse (eski randevular) → atanan çalışana düşülür
     */
    private function personelIletisim(object $r): array
    {
        // 1) Randevuyu açan yönetici
        try {
            $olusturanId = $r->olusturan_id ?? null;
            if ($olusturanId) {
                $y = DB::table('yoneticiler')->where('id', $olusturanId)
                    ->first(['adi', 'telefon', 'email', 'eposta']);
                if ($y) {
                    $tel   = trim((string) ($y->telefon ?? ''));
                    $email = trim((string) ($y->email ?: ($y->eposta ?? '')));
                    return [$tel !== '' ? $tel : null, $email !== '' ? $email : null, $y->adi ?? null];
                }
            }
        } catch (\Throwable $e) {}

        // 2) Eski kayıtlar: atanan çalışan
        try {
            if (!empty($r->calisan_id)) {
                $c = DB::table('randevu_calisanlar')->where('id', $r->calisan_id)
                    ->first(['ad', 'telefon', 'email']);
                if ($c) {
                    $email = trim((string) ($c->email ?? ''));
                    return [$this->calisanTelefon($r), $email !== '' ? $email : null, $c->ad ?? null];
                }
            }
        } catch (\Throwable $e) {}

        return [null, null, null];
    }

    /**
     * Ek SMS kopyası gidecek numaralar — SADECE ayarlarda elle tanımlanmışsa.
     * 'ayarlar.randevu_sms_yonetim' boşsa kimseye kopya gitmez (varsayılan davranış).
     */
    private function yonetimTelefonlari(): array
    {
        try {
            if (Schema::hasTable('ayarlar') && Schema::hasColumn('ayarlar', 'randevu_sms_yonetim')) {
                $ham = trim((string) (DB::table('ayarlar')->value('randevu_sms_yonetim') ?? ''));
                if ($ham !== '') {
                    return array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $ham))));
                }
            }
        } catch (\Throwable $e) {}

        return [];
    }

    /**
     * Bir randevu SMS'ini ilgili kişilere gönderir:
     *  - müşteri  - randevuyu OLUŞTURAN personel  - (varsa) elle tanımlı ek kopya listesi
     * Artık tüm yöneticilere toplu kopya GİTMEZ.
     */
    private function smsDagit(?string $musteriTel, string $musteriMesaj, object $r, string $personelMesaj): bool
    {
        $ok = false;
        if ($musteriTel) {
            $ok = RandevuSms::gonder($musteriTel, $musteriMesaj) || $ok;
        }
        [$personelTel] = $this->personelIletisim($r);
        if ($personelTel && $personelTel !== $musteriTel) {
            RandevuSms::gonder($personelTel, $personelMesaj);
        }
        foreach ($this->yonetimTelefonlari() as $yt) {
            if ($yt === $personelTel || $yt === $musteriTel) continue; // aynı numaraya iki kez gitmesin
            RandevuSms::gonder($yt, $personelMesaj);
        }
        return $ok;
    }

    /**
     * Kurumsal mail şablonu (logo + başlık + bilgi tablosu + opsiyonel buton).
     * Tablo bazlı + inline CSS: Gmail/Outlook/mobil istemcilerde bozulmaz.
     */
    private function mailGovde(string $baslik, array $satirlar, ?string $aciklama = null, ?string $butonUrl = null, ?string $butonMetin = null): string
    {
        $logo = asset('tema/uploads/logo/site-logo.png');

        // Bilgi satırları
        $ic = '';
        foreach ($satirlar as $etiket => $deger) {
            if ($deger === null || $deger === '') continue;
            $ic .= '<tr>'
                 . '<td style="padding:10px 14px;color:#8a8f98;font-size:13px;white-space:nowrap;border-bottom:1px solid #f0f1ea;vertical-align:top">' . e($etiket) . '</td>'
                 . '<td style="padding:10px 14px;color:#1a2332;font-size:14px;font-weight:700;border-bottom:1px solid #f0f1ea">' . e($deger) . '</td>'
                 . '</tr>';
        }

        $aciklamaHtml = $aciklama !== null && $aciklama !== ''
            ? '<tr><td style="padding:0 28px 18px;color:#5b6168;font-size:14px;line-height:1.65;text-align:center">' . e($aciklama) . '</td></tr>'
            : '';

        $butonHtml = ($butonUrl && $butonMetin)
            ? '<tr><td style="padding:6px 28px 26px;text-align:center">'
            . '<a href="' . $butonUrl . '" style="display:inline-block;background:#b8b62e;color:#1f2937;'
            . 'font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;text-decoration:none;'
            . 'padding:13px 38px;border-radius:10px">' . e($butonMetin) . '</a>'
            . '</td></tr>'
            : '';

        return '<!DOCTYPE html><html><body style="margin:0;padding:0;background-color:#f3f4ee">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4ee;padding:28px 12px">'
            . '<tr><td align="center">'

            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" '
            . 'style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;border:1px solid #e7e8de;font-family:Arial,Helvetica,sans-serif">'

            // LOGO
            . '<tr><td align="center" style="padding:30px 28px 8px">'
            . '<img src="' . $logo . '" alt="DN Kreatif — İş Ortağım" width="170" style="display:block;max-width:170px;height:auto">'
            . '</td></tr>'

            // İnce ayraç
            . '<tr><td style="padding:14px 28px 0"><div style="border-top:1px solid #eef0e6;font-size:0;line-height:0">&nbsp;</div></td></tr>'

            // BAŞLIK
            . '<tr><td align="center" style="padding:18px 28px 10px;color:#1a2332;font-size:21px;font-weight:bold">' . e($baslik) . '</td></tr>'

            // AÇIKLAMA (opsiyonel)
            . $aciklamaHtml

            // BİLGİ TABLOSU
            . ($ic !== ''
                ? '<tr><td style="padding:0 28px 22px">'
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
                . 'style="border:1px solid #eef0e6;border-radius:12px;border-collapse:separate;overflow:hidden;background-color:#fbfcf7">'
                . $ic
                . '</table></td></tr>'
                : '')

            // BUTON (opsiyonel)
            . $butonHtml

            // FOOTER
            . '<tr><td align="center" style="padding:18px 28px 26px;border-top:1px solid #eef0e6">'
            . '<div style="color:#1a2332;font-size:13px;font-weight:bold;padding-bottom:4px">DN Kreatif — İş Ortağım</div>'
            . '<div style="color:#9aa0a6;font-size:12px;line-height:1.6">Bu e-posta randevu bilgilendirmesi amacıyla otomatik gönderilmiştir.<br>Lütfen bu e-postayı yanıtlamayınız.</div>'
            . '</td></tr>'

            . '</table>'
            . '</td></tr></table>'
            . '</body></html>';
    }

    /* ── 0) 2 SAAT ÖNCE (müşteri + görevli) ── */
    private function ikiSaatOnceHatirlat(Carbon $simdi): void
    {
        if (!Schema::hasColumn('randevular', 'sms_2saat_at')) return;

        try {
            $liste = DB::table('randevular')
                ->whereNull('sms_2saat_at')
                ->where('durum', '!=', 'iptal')
                // 30 dk penceresiyle çakışmasın: 30 dk'dan uzak, 2 saate kadar
                ->where('baslangic', '>', $simdi->copy()->addMinutes(30))
                ->where('baslangic', '<=', $simdi->copy()->addMinutes(120))
                ->limit(50)->get();
        } catch (\Throwable $e) { Log::error('randevu:hatirlat 2saat sorgu', ['e' => $e->getMessage()]); return; }

        foreach ($liste as $r) {
            [$ad, $tel, $email] = $this->iletisim($r);
            [$bas, $lokasyon, $calisanAd] = $this->detay($r);
            // Personel bildirimi YALNIZCA randevuyu oluşturan kişiye gider
            [, $personelEmail] = $this->personelIletisim($r);

            $sms = 'Sayin ' . Str::limit($ad, 40, '') . ', ' . $bas->format('d.m.Y H:i')
                 . ' tarihli randevunuza yaklasik 2 saat kalmistir.'
                 . ($lokasyon ? ' Lokasyon: ' . Str::limit($lokasyon, 40, '') . '.' : '');

            $smsPersonel = 'Randevu hatirlatma: ' . Str::limit($ad, 30, '') . ' ile '
                 . $bas->format('d.m.Y H:i') . ' randevunuza 2 saat kaldi.'
                 . ($tel ? ' Tel: ' . $tel . '.' : '');

            $this->smsDagit($tel, $sms, $r, $smsPersonel);
            if ($email) {
                try {
                    EmailNotificationService::send($email, '⏰ Randevunuza 2 Saat Kaldı',
                        $this->mailGovde('Randevunuza yaklaşık 2 saat kaldı', [
                            'Müşteri' => $ad, 'Tarih' => $bas->format('d.m.Y'), 'Saat' => $bas->format('H:i'),
                            'Lokasyon' => $lokasyon, 'İlgili Kişi' => $calisanAd,
                        ], 'Sayın ' . $ad . ', randevunuz yaklaşıyor. Görüşme detaylarınızı aşağıda bulabilirsiniz.'));
                } catch (\Throwable $e) {}
            }
            // Görevli çalışana bilgi maili
            if ($personelEmail) {
                try {
                    EmailNotificationService::send($personelEmail, '⏰ Yaklaşan Randevu (2 saat) — ' . $bas->format('H:i'),
                        $this->mailGovde('2 saat sonra randevunuz var', [
                            'Müşteri' => $ad, 'Telefon' => $tel, 'Tarih' => $bas->format('d.m.Y'), 'Saat' => $bas->format('H:i'), 'Lokasyon' => $lokasyon,
                        ], 'Yaklaşan görüşmenizin detayları aşağıdadır.'));
                } catch (\Throwable $e) {}
            }

            $this->uygulamaIciBildir($r->crm_musteri_id ?? null, $email,
                '⏰ Randevunuza 2 saat kaldı',
                $bas->format('d.m.Y H:i') . ' tarihli randevunuz yaklaşıyor' . ($lokasyon ? ' — ' . $lokasyon : '') . '.');

            DB::table('randevular')->where('id', $r->id)->update(['sms_2saat_at' => Carbon::now()]);
            Log::info('randevu:hatirlat 2saat gönderildi', ['randevu' => $r->id]);
        }
    }

    /* ── 1) 30 DK ÖNCE ── */
    private function otuzDakikaHatirlat(Carbon $simdi): void
    {
        try {
            $liste = DB::table('randevular')
                ->whereNull('sms_30dk_at')
                ->where('durum', '!=', 'iptal')
                ->where('baslangic', '>', $simdi)
                ->where('baslangic', '<=', $simdi->copy()->addMinutes(30))
                ->limit(50)->get();
        } catch (\Throwable $e) { Log::error('randevu:hatirlat 30dk sorgu', ['e' => $e->getMessage()]); return; }

        foreach ($liste as $r) {
            [$ad, $tel, $email] = $this->iletisim($r);
            [$bas, $lokasyon, $calisanAd] = $this->detay($r);
            // Personel bildirimi YALNIZCA randevuyu oluşturan kişiye gider
            [, $personelEmail] = $this->personelIletisim($r);

            $sms = 'Sayin ' . Str::limit($ad, 40, '') . ', ' . $bas->format('d.m.Y H:i')
                 . ' tarihli randevunuza 30 dakika kalmistir.'
                 . ($lokasyon ? ' Lokasyon: ' . Str::limit($lokasyon, 40, '') . '.' : '');

            // Personel/yönetim kopyası: kimin randevusu olduğu belirtilir
            $smsPersonel = 'Randevu hatirlatma: ' . Str::limit($ad, 30, '') . ' ile '
                 . $bas->format('d.m.Y H:i') . ' randevunuza 30 dakika kaldi.'
                 . ($tel ? ' Tel: ' . $tel . '.' : '')
                 . ($lokasyon ? ' ' . Str::limit($lokasyon, 30, '') . '.' : '');

            $tamam = $this->smsDagit($tel, $sms, $r, $smsPersonel);
            if ($email) {
                try {
                    EmailNotificationService::send($email, '⏰ Randevunuza 30 Dakika Kaldı',
                        $this->mailGovde('Randevunuza 30 dakika kaldı', [
                            'Müşteri' => $ad, 'Tarih' => $bas->format('d.m.Y'), 'Saat' => $bas->format('H:i'),
                            'Lokasyon' => $lokasyon, 'İlgili Kişi' => $calisanAd,
                        ], 'Sayın ' . $ad . ', randevunuz yaklaşıyor. Görüşme detaylarınızı aşağıda bulabilirsiniz.'));
                    $tamam = true;
                } catch (\Throwable $e) { Log::warning('30dk mail', ['e' => $e->getMessage()]); }
            }
            // Çalışana da bilgi maili
            if ($personelEmail) {
                try {
                    EmailNotificationService::send($personelEmail, '⏰ Yaklaşan Randevu — ' . $bas->format('H:i'),
                        $this->mailGovde('30 dakika sonra randevunuz var', [
                            'Müşteri' => $ad, 'Telefon' => $tel, 'Saat' => $bas->format('H:i'), 'Lokasyon' => $lokasyon,
                        ], 'Yaklaşan görüşmenizin detayları aşağıdadır.'));
                } catch (\Throwable $e) {}
            }

            $this->uygulamaIciBildir($r->crm_musteri_id ?? null, $email,
                '⏰ Randevunuza 30 dakika kaldı',
                $bas->format('d.m.Y H:i') . ' tarihli randevunuz yaklaşıyor' . ($lokasyon ? ' — ' . $lokasyon : '') . '.');

            DB::table('randevular')->where('id', $r->id)->update(['sms_30dk_at' => Carbon::now()]);
            Log::info('randevu:hatirlat 30dk gönderildi', ['randevu' => $r->id, 'ok' => $tamam]);
        }
    }

    /* ── 2) RANDEVU SAATİNDE ── */
    private function baslangicBildir(Carbon $simdi): void
    {
        try {
            $liste = DB::table('randevular')
                ->whereNull('sms_baslangic_at')
                ->where('durum', '!=', 'iptal')
                ->where('baslangic', '<=', $simdi)
                ->where('baslangic', '>', $simdi->copy()->subMinutes(10))
                ->limit(50)->get();
        } catch (\Throwable $e) { Log::error('randevu:hatirlat baslangic sorgu', ['e' => $e->getMessage()]); return; }

        foreach ($liste as $r) {
            [$ad, $tel, $email] = $this->iletisim($r);
            [$bas, $lokasyon, $calisanAd] = $this->detay($r);
            // Personel bildirimi YALNIZCA randevuyu oluşturan kişiye gider
            [, $personelEmail] = $this->personelIletisim($r);

            $sms = 'Sayin ' . Str::limit($ad, 40, '') . ', randevu saatiniz geldi (' . $bas->format('H:i') . ').'
                 . ($calisanAd ? ' Ilgili kisi: ' . Str::limit($calisanAd, 30, '') . '.' : '')
                 . ' Iyi gorusmeler dileriz.';

            $smsPersonel = 'Randevu basliyor: ' . Str::limit($ad, 30, '') . ' (' . $bas->format('H:i') . ').'
                 . ($tel ? ' Tel: ' . $tel . '.' : '');

            $this->smsDagit($tel, $sms, $r, $smsPersonel);
            if ($email) {
                try {
                    EmailNotificationService::send($email, '📅 Randevunuz Başladı',
                        $this->mailGovde('Randevu saatiniz geldi', [
                            'Müşteri' => $ad, 'Saat' => $bas->format('H:i'), 'Lokasyon' => $lokasyon, 'İlgili Kişi' => $calisanAd,
                        ], 'Sayın ' . $ad . ', randevu saatiniz geldi. İyi görüşmeler dileriz.'));
                } catch (\Throwable $e) {}
            }

            // Görevli çalışana bilgi maili (randevu başladı)
            if ($personelEmail) {
                try {
                    EmailNotificationService::send($personelEmail, '📅 Randevu Başladı — ' . $bas->format('H:i'),
                        $this->mailGovde('Randevu saatiniz geldi', [
                            'Müşteri' => $ad, 'Telefon' => $tel, 'Saat' => $bas->format('H:i'), 'Lokasyon' => $lokasyon,
                        ], 'Şu an başlayan görüşmenizin detayları aşağıdadır.'));
                } catch (\Throwable $e) {}
            }

            $this->uygulamaIciBildir($r->crm_musteri_id ?? null, $email,
                '📅 Randevu saatiniz geldi',
                $bas->format('H:i') . ' randevunuz başladı' . ($calisanAd ? ' — ilgili kişi: ' . $calisanAd : '') . '.');

            DB::table('randevular')->where('id', $r->id)->update(['sms_baslangic_at' => Carbon::now()]);
            Log::info('randevu:hatirlat baslangic gönderildi', ['randevu' => $r->id]);
        }
    }

    /* ── 3) 40 DK SONRA: ÇALIŞANA "SÜREÇ DOLDU" ── */
    private function kirkDakikaCalisanHatirlat(Carbon $simdi): void
    {
        if (!Schema::hasColumn('randevular', 'sms_40dk_at')) return;

        try {
            $liste = DB::table('randevular')
                ->whereNull('sms_40dk_at')
                ->where('durum', '!=', 'iptal')
                ->whereNotNull('calisan_id')
                // Başlangıçtan 40 dk geçmiş randevular
                ->where('baslangic', '<=', $simdi->copy()->subMinutes(40))
                // Eski yığını spamlama: yalnızca son 1 günün randevuları
                ->where('baslangic', '>=', $simdi->copy()->subDay())
                ->limit(50)->get();
        } catch (\Throwable $e) {
            Log::error('randevu:hatirlat 40dk sorgu', ['e' => $e->getMessage()]);
            return;
        }

        foreach ($liste as $r) {
            [$ad, $tel, $email] = $this->iletisim($r);
            $bas = Carbon::parse($r->baslangic);

            // Randevuyu oluşturan personelin telefonu (yoksa atanan çalışana düşülür)
            [$calisanTel] = $this->personelIletisim($r);
            if (!$calisanTel) {
                // Telefon yoksa tekrar denememek için yine damgala
                DB::table('randevular')->where('id', $r->id)->update(['sms_40dk_at' => Carbon::now()]);
                continue;
            }

            $sms = 'Hatirlatma: ' . $bas->format('H:i') . ' ' . Str::limit($ad, 30, '')
                 . ' randevunuzun uzerinden 40 dk gecti, surec doldu. Lutfen randevuyu sonlandirin/guncelleyin.';

            $ok = RandevuSms::gonder($calisanTel, $sms);

            DB::table('randevular')->where('id', $r->id)->update(['sms_40dk_at' => Carbon::now()]);
            Log::info('randevu:hatirlat 40dk gönderildi', ['randevu' => $r->id, 'calisan' => $r->calisan_id, 'ok' => $ok]);
        }
    }

    /* ── 4) 2 SAAT SONRA DEĞERLENDİRME ── */
    private function degerlendirmeDavet(Carbon $simdi): void
    {
        if (!Schema::hasTable('randevu_degerlendirmeler')) return;

        try {
            $liste = DB::table('randevular')
                ->whereNull('sms_degerlendirme_at')
                ->where('durum', '!=', 'iptal')
                ->whereRaw('COALESCE(bitis, baslangic) <= ?', [$simdi->copy()->subHours(2)])
                ->where('baslangic', '>=', $simdi->copy()->subDays(3)) // eski yığını spamlama
                ->limit(50)->get();
        } catch (\Throwable $e) { Log::error('randevu:hatirlat degerlendirme sorgu', ['e' => $e->getMessage()]); return; }

        foreach ($liste as $r) {
            [$ad, $tel, $email] = $this->iletisim($r);
            [$bas, $lokasyon, $calisanAd] = $this->detay($r);
            // Personel bildirimi YALNIZCA randevuyu oluşturan kişiye gider
            [, $personelEmail] = $this->personelIletisim($r);

            $token = bin2hex(random_bytes(24));
            try {
                DB::table('randevu_degerlendirmeler')->insert([
                    'randevu_id' => $r->id,
                    'calisan_id' => $r->calisan_id,
                    'musteri_adi' => $ad,
                    'token'      => $token,
                    'created_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('degerlendirme token insert', ['e' => $e->getMessage()]);
                continue;
            }

            $link = route('randevu.degerlendirme', $token);
            $sms = 'Sayin ' . Str::limit($ad, 40, '') . ', bugunki gorusmemizi degerlendirmek ister misiniz? ' . $link;

            if ($tel)   RandevuSms::gonder($tel, $sms);
            if ($email) {
                try {
                    EmailNotificationService::send($email, '⭐ Randevunuzu Değerlendirin',
                        $this->mailGovde('Görüşmemiz nasıldı?', [
                            'Tarih' => $bas->format('d.m.Y H:i'), 'Lokasyon' => $lokasyon, 'İlgili Kişi' => $calisanAd,
                        ], 'Sayın ' . $ad . ', bugünkü görüşmemiz hakkındaki değerli görüşlerinizi almak isteriz. Birkaç saniyenizi ayırarak bizi değerlendirebilirsiniz.', $link, 'Değerlendir →'));
                } catch (\Throwable $e) {}
            }

            // Görevli çalışana bilgi maili (randevu 2 saat önce bitti)
            if ($personelEmail) {
                try {
                    EmailNotificationService::send($personelEmail, '✅ Randevu Tamamlandı — ' . Str::limit($ad, 40, ''),
                        $this->mailGovde('Görüşme tamamlandı', [
                            'Müşteri' => $ad, 'Tarih' => $bas->format('d.m.Y H:i'), 'Lokasyon' => $lokasyon,
                        ], $ad . ' ile görüşmenizin üzerinden 2 saat geçti. Müşteriye değerlendirme daveti gönderildi.'));
                } catch (\Throwable $e) {}
            }

            $this->uygulamaIciBildir($r->crm_musteri_id ?? null, $email,
                '⭐ Randevunuzu değerlendirin',
                'Bugünkü görüşmemiz nasıldı? Birkaç saniyenizi ayırıp puanlayabilirsiniz.');

            DB::table('randevular')->where('id', $r->id)->update(['sms_degerlendirme_at' => Carbon::now()]);
            Log::info('randevu:hatirlat degerlendirme gönderildi', ['randevu' => $r->id]);
        }
    }
}