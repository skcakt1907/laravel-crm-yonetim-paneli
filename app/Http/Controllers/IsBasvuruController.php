<?php

namespace App\Http\Controllers;

use App\Models\IsBasvuru;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * İş Başvurusu (Rubito) — ornek.com/is-basvurusu'nun İş Ortağım'a native hâli.
 * Typeform tarzı adım-adım form; başvurular is_basvurulari tablosuna düşer.
 * Yeni başvuru gelince yönetim ekibine (Nurseli/Dilan/Nesimi) bilgilendirme maili gider.
 */
class IsBasvuruController extends Controller
{
    /** Bildirim gidecek yöneticilerin kullanıcı adları (yoneticiler tablosundan) */
    private const ALICI_KULLANICILAR = ['nurselinan', 'dilanatescom', 'NesimiAtes'];

    /** CV için izinli mime tipleri (script yükleme sertleştirmesi) */
    private const CV_MIME = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function goster()
    {
        return view('tema.is-basvurusu');
    }

    public function kaydet(Request $request)
    {
        $data = $request->validate([
            'ad_soyad'       => 'required|string|max:190',
            'email'          => 'required|email|max:190',
            'telefon'        => 'required|string|max:40',
            'calisma_durumu' => 'nullable|string|max:60',
            'egitim_duzeyi'  => 'nullable|string|max:60',
            'pozisyon'       => 'required|string|max:80',
            'deneyim_yili'   => 'nullable|integer|min:0|max:70',
            'lokasyon'       => 'required|string|max:40',
            'dil'            => 'required|string|max:40',
            'maas_beklenti'  => 'required|string|max:190',
            'ek_bilgi'       => 'required|string|max:5000',
            'cv'             => 'required|file|max:8192|mimetypes:' . implode(',', self::CV_MIME),
        ], [], [
            'ad_soyad' => 'ad soyad',
            'cv'       => 'CV / özgeçmiş',
        ]);

        // CV'yi güvenli kaydet: uzantı client'tan DEĞİL guessExtension'dan; whitelist zaten validate'te
        $cvYol = null;
        if ($request->hasFile('cv')) {
            $dosya = $request->file('cv');
            $uzanti = $dosya->guessExtension() ?: 'bin';
            $klasor = public_path('uploads/basvuru_cv');
            if (!is_dir($klasor)) {
                @mkdir($klasor, 0755, true);
            }
            $ad = date('Ymd_His') . '_' . Str::random(8) . '.' . $uzanti;
            $dosya->move($klasor, $ad);
            $cvYol = 'uploads/basvuru_cv/' . $ad;
        }

        $basvuru = IsBasvuru::create([
            'ad_soyad'       => $data['ad_soyad'],
            'email'          => $data['email'],
            'telefon'        => $data['telefon'],
            'calisma_durumu' => $data['calisma_durumu'] ?? null,
            'egitim_duzeyi'  => $data['egitim_duzeyi'] ?? null,
            'pozisyon'       => $data['pozisyon'],
            'deneyim_yili'   => (int) ($data['deneyim_yili'] ?? 0),
            'lokasyon'       => $data['lokasyon'],
            'dil'            => $data['dil'],
            'maas_beklenti'  => $data['maas_beklenti'],
            'cv_dosya'       => $cvYol,
            'ek_bilgi'       => $data['ek_bilgi'],
            'ip'             => $request->ip(),
        ]);

        try {
            $this->yoneticilereBildir($basvuru);
        } catch (\Throwable $e) {
            Log::warning('İş başvurusu bildirim maili gönderilemedi: ' . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('is.basvurusu')->with('basvuru_ok', true);
    }

    private function yoneticilereBildir(IsBasvuru $b): void
    {
        if (!Schema::hasTable('yoneticiler')) {
            return;
        }

        $alicilar = DB::table('yoneticiler')
            ->whereIn('kullaniciadi', self::ALICI_KULLANICILAR)
            ->where('durum', 1)
            ->whereNotNull('email')->where('email', '!=', '')
            ->pluck('email');

        if ($alicilar->isEmpty()) {
            return;
        }

        $ayarlar = Schema::hasTable('ayarlar') ? DB::table('ayarlar')->first() : null;
        $subject = '💼 Yeni İş Başvurusu — ' . ($b->ad_soyad ?: 'İsimsiz') . ' · ' . ($b->pozisyon ?: '') . ' (İş Ortağım)';
        $body    = $this->body($b, $ayarlar);

        foreach ($alicilar as $email) {
            EmailNotificationService::send($email, $subject, $body);
        }
    }

    private function body(IsBasvuru $b, $ayarlar = null): string
    {
        $firma    = htmlspecialchars($ayarlar->firma_adi ?? 'İş Ortağım');
        $siteUrl  = htmlspecialchars($ayarlar->site_url ?? 'https://crm.ornek.com/');
        $logoUrl  = mail_logo_url();
        $panelUrl = rtrim($siteUrl, '/') . '/admin/is-basvurulari';
        $cvUrl    = $b->cv_dosya ? rtrim($siteUrl, '/') . '/' . ltrim($b->cv_dosya, '/') : null;
        $tel      = htmlspecialchars($ayarlar->firma_telefon ?? '0 (850) 307 95 48');
        $mailAdr  = htmlspecialchars($ayarlar->firma_email ?? 'isortagim@ornek.com');
        $yil      = date('Y');

        $sat = function (string $baslik, ?string $deger) {
            $deger = nl2br(htmlspecialchars((string) $deger));
            return '<tr><td style="padding:12px 0;border-bottom:1px solid #f0efe6">'
                . '<div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">' . $baslik . '</div>'
                . '<div style="font-size:14px;color:#2b2b1f;line-height:1.6">' . ($deger !== '' ? $deger : '—') . '</div></td></tr>';
        };

        $satirlar  = $sat('Ad Soyad', $b->ad_soyad);
        $satirlar .= $sat('E-posta', $b->email);
        $satirlar .= $sat('Telefon', $b->telefon);
        $satirlar .= '<tr><td style="padding:14px 0 6px;font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase">Başvuru</td></tr>';
        $satirlar .= $sat('Pozisyon', $b->pozisyon);
        $satirlar .= $sat('Çalışma durumu', $b->calisma_durumu);
        $satirlar .= $sat('Eğitim düzeyi', $b->egitim_duzeyi);
        $satirlar .= $sat('Deneyim (yıl)', (string) $b->deneyim_yili);
        $satirlar .= $sat('Lokasyon', $b->lokasyon);
        $satirlar .= $sat('Dil yetkinliği', $b->dil);
        $satirlar .= $sat('Beklenen maaş', $b->maas_beklenti);
        $satirlar .= $sat('Ek bilgi', $b->ek_bilgi);

        $cvSatir = $cvUrl
            ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 4px"><tr><td align="center">
                 <a href="' . htmlspecialchars($cvUrl) . '" style="display:inline-block;background-color:#b8b62e;color:#1a1a0e;text-decoration:none;padding:12px 30px;border-radius:10px;font-weight:800;font-size:14px">📄 CV / Özgeçmişi İndir</a>
               </td></tr></table>'
            : '';

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">
    <tr><td style="background-color:#1a2332;padding:26px 36px 22px;text-align:center">
      <img src="' . $logoUrl . '" alt="' . $firma . '" width="150" style="display:block;margin:0 auto 12px;max-width:150px;height:auto;border:0">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">İş Başvurusu · Yeni Aday 💼</div>
    </td></tr>
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:30px 36px 10px">
      <h2 style="margin:0 0 6px;font-size:22px;font-weight:800;color:#1a1a0e">💼 Yeni iş başvurusu</h2>
      <p style="margin:0 0 18px;color:#6b6f63;font-size:14px;line-height:1.6"><strong>' . htmlspecialchars($b->ad_soyad) . '</strong>, <strong>' . htmlspecialchars((string) $b->pozisyon) . '</strong> için başvurdu. Detaylar aşağıda; CV ekli.</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $satirlar . '</table>
      ' . $cvSatir . '
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:12px 0 6px"><tr><td align="center">
        <a href="' . $panelUrl . '" style="display:inline-block;background-color:#1a2332;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:10px;font-weight:700;font-size:15px">Panelde Aç →</a>
      </td></tr></table>
    </td></tr>
    <tr><td style="background-color:#1a2332;padding:20px 36px;text-align:center">
      <p style="margin:0 0 4px;color:#c7cbd6;font-size:13px">📞 ' . $tel . ' &nbsp;·&nbsp; ✉️ ' . $mailAdr . '</p>
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . $yil . ' ' . $firma . ' &middot; <a href="' . $siteUrl . '" style="color:#b8b62e;text-decoration:none">' . $siteUrl . '</a> &middot; Otomatik bildirim</p>
    </td></tr>
  </table>
</td></tr>
</table>
</body></html>';
    }
}
