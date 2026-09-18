<?php

namespace App\Http\Controllers;

use App\Models\ArgeAnketi;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Ar-Ge Anketi (Rubito) — ornek.com/arge-anketi'nin İş Ortağım'a native hâli.
 * Typeform tarzı adım-adım form; cevaplar arge_anketleri tablosuna düşer.
 * Yeni cevap gelince yönetim ekibine (Nurseli/Dilan/Nesimi) bilgilendirme maili gider.
 */
class ArgeAnketiController extends Controller
{
    /** Bildirim gidecek yöneticilerin kullanıcı adları (e-posta yoneticiler tablosundan) */
    private const ALICI_KULLANICILAR = ['nurselinan', 'dilanatescom', 'NesimiAtes'];

    public function goster()
    {
        return view('tema.arge-anketi');
    }

    public function kaydet(Request $request)
    {
        $data = $request->validate([
            'ad_soyad'       => 'required|string|max:190',
            'email'          => 'required|email|max:190',
            'telefon'        => 'nullable|string|max:40',
            'dogum_tarihi'   => 'nullable|date',
            'marka_guclu'    => 'required|string|max:5000',
            'marka_gelistir' => 'required|string|max:5000',
            'geri_bildirim'  => 'required|in:evet,hayir',
            'rakipler'       => 'required|string|max:5000',
            'rakip_kampanya' => 'required|string|max:5000',
            'ajans_calisti'  => 'required|in:evet,hayir',
            'ajans_katki'    => 'required|string|max:5000',
            'ajans_beklenti' => 'required|string|max:5000',
            'duydu_mu'       => 'required|in:evet,hayir',
            'butce'          => 'required|string|max:5000',
        ], [], [
            'ad_soyad'       => 'ad soyad',
            'marka_guclu'    => 'markanızın güçlü yönleri',
            'marka_gelistir' => 'geliştirmek istediğiniz yönler',
        ]);

        $data['ip'] = $request->ip();

        // ── Mükerrer gönderim koruması ────────────────────────────────────────
        // Mailler istek içinde senkron gittiği için cevap ~3 sn sürüyor; o sırada
        // kullanıcı tekrar Enter'a basarsa aynı anket 2-3-4 kez kaydediliyordu.
        // İstemci tarafında da kilit var (arge-anketi.blade), bu sunucu tarafı garanti.
        $mukerrer = ArgeAnketi::where('email', $data['email'])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->latest('id')
            ->first();

        if ($mukerrer) {
            Log::info('Ar-Ge anketi mükerrer gönderim engellendi', [
                'email'       => $data['email'],
                'ip'          => $data['ip'],
                'mevcut_id'   => $mukerrer->id,
                'mevcut_saat' => (string) $mukerrer->created_at,
            ]);

            // Kullanıcıya hata gösterme — ilk kayıt zaten alındı, akış normal bitsin
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => true, 'mukerrer' => true]);
            }

            return redirect()->route('arge.anketi')->with('arge_ok', true);
        }

        $anket = ArgeAnketi::create($data);

        // Yönetim ekibine bildirim maili (akışı ASLA bozmaz)
        try {
            $this->yoneticilereBildir($anket);
        } catch (\Throwable $e) {
            Log::warning('Ar-Ge anketi bildirim maili gönderilemedi: ' . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('arge.anketi')->with('arge_ok', true);
    }

    /**
     * Yeni Ar-Ge anketi cevabını yönetim ekibine markalı HTML mail ile bildirir.
     */
    private function yoneticilereBildir(ArgeAnketi $anket): void
    {
        $ayarlar = Schema::hasTable('ayarlar') ? DB::table('ayarlar')->first() : null;

        // 1) Adı geçen yöneticiler (kullaniciadi eşleşmesi)
        $emailler = [];
        if (Schema::hasTable('yoneticiler')) {
            $emailler = DB::table('yoneticiler')
                ->whereIn('kullaniciadi', self::ALICI_KULLANICILAR)
                ->where('durum', 1)
                ->whereNotNull('email')->where('email', '!=', '')
                ->pluck('email')->all();
        }

        // 2) GARANTİ KOPYA: kullaniciadi eşleşmese bile en az kurumsal kutuya düşsün
        if (!empty($ayarlar->firma_email)) {
            $emailler[] = $ayarlar->firma_email;
        }

        // Temizle + tekilleştir + geçerli e-posta filtrele
        $emailler = array_values(array_unique(array_filter(
            array_map('trim', $emailler),
            fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        )));

        if (empty($emailler)) {
            Log::warning('Ar-Ge bildirim maili: geçerli alıcı bulunamadı (yoneticiler eşleşmedi + firma_email boş).', [
                'aranan_kullanicilar' => self::ALICI_KULLANICILAR,
                'anket_id'            => $anket->id,
            ]);
            return;
        }

        $subject = '📋 Yeni Ar-Ge Anketi cevabı — ' . ($anket->ad_soyad ?: 'İsimsiz') . ' (İş Ortağım)';
        $body    = $this->body($anket, $ayarlar);

        Log::info('Ar-Ge bildirim maili gönderiliyor', [
            'alici_sayisi' => count($emailler),
            'aliciler'     => $emailler,
            'anket_id'     => $anket->id,
        ]);

        foreach ($emailler as $email) {
            try {
                $ok = EmailNotificationService::send($email, $subject, $body);
                Log::info('Ar-Ge bildirim maili ' . ($ok ? 'gönderildi →' : 'BAŞARISIZ →') . ' ' . $email);
            } catch (\Throwable $e) {
                Log::warning('Ar-Ge bildirim maili gönderilemedi (' . $email . '): ' . $e->getMessage());
            }
        }
    }

    /**
     * İş Ortağım markalı, e-posta istemcisine dostu (tablo tabanlı) şablon.
     */
    private function body(ArgeAnketi $a, $ayarlar = null): string
    {
        $firma   = htmlspecialchars($ayarlar->firma_adi ?? 'İş Ortağım');
        $siteUrl = htmlspecialchars($ayarlar->site_url ?? 'https://crm.ornek.com/');
        $logoUrl = mail_logo_url();
        $panelUrl = rtrim($siteUrl, '/') . '/admin/arge-anketleri';
        $tel     = htmlspecialchars($ayarlar->firma_telefon ?? '0 (850) 307 95 48');
        $mailAdr = htmlspecialchars($ayarlar->firma_email ?? 'isortagim@ornek.com');
        $yil     = date('Y');

        $evetHayir = fn ($v) => $v === 'evet' ? '<span style="color:#16794a;font-weight:700">Evet</span>' : '<span style="color:#b7791f;font-weight:700">Hayır</span>';
        $sat = function (string $baslik, string $deger) {
            $deger = nl2br(htmlspecialchars($deger));
            return '<tr><td style="padding:12px 0;border-bottom:1px solid #f0efe6">'
                . '<div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">' . $baslik . '</div>'
                . '<div style="font-size:14px;color:#2b2b1f;line-height:1.6">' . ($deger !== '' ? $deger : '—') . '</div></td></tr>';
        };

        $satirlar  = $sat('Ad Soyad', $a->ad_soyad ?? '');
        $satirlar .= $sat('E-posta', $a->email ?? '');
        $satirlar .= $sat('Telefon', $a->telefon ?? '');
        if (!empty($a->dogum_tarihi)) {
            $dt = $a->dogum_tarihi instanceof \DateTimeInterface ? $a->dogum_tarihi->format('d.m.Y') : (string) $a->dogum_tarihi;
            $satirlar .= $sat('Doğum Tarihi', $dt);
        }
        $satirlar .= '<tr><td style="padding:14px 0 6px;font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase">Marka</td></tr>';
        $satirlar .= $sat('Markanın güçlü yönleri', $a->marka_guclu ?? '');
        $satirlar .= $sat('Geliştirmek istediği yönler', $a->marka_gelistir ?? '');
        $satirlar .= '<tr><td style="padding:12px 0;border-bottom:1px solid #f0efe6"><div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;margin-bottom:4px">Geri bildirim alıyor mu?</div>' . $evetHayir($a->geri_bildirim ?? '') . '</td></tr>';
        $satirlar .= $sat('Rakipleri', $a->rakipler ?? '');
        $satirlar .= $sat('Rakip kampanyaları', $a->rakip_kampanya ?? '');
        $satirlar .= '<tr><td style="padding:14px 0 6px;font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase">Ajans</td></tr>';
        $satirlar .= '<tr><td style="padding:12px 0;border-bottom:1px solid #f0efe6"><div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;margin-bottom:4px">Daha önce ajansla çalıştı mı?</div>' . $evetHayir($a->ajans_calisti ?? '') . '</td></tr>';
        $satirlar .= $sat('Ajanstan beklenen katkı', $a->ajans_katki ?? '');
        $satirlar .= $sat('Ajans beklentileri', $a->ajans_beklenti ?? '');
        $satirlar .= '<tr><td style="padding:12px 0;border-bottom:1px solid #f0efe6"><div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;margin-bottom:4px">İş Ortağım\'ı nereden duydu?</div>' . $evetHayir($a->duydu_mu ?? '') . '</td></tr>';
        $satirlar .= $sat('Bütçe', $a->butce ?? '');

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">
    <tr><td style="background-color:#1a2332;padding:26px 36px 22px;text-align:center">
      <img src="' . $logoUrl . '" alt="' . $firma . '" width="150" style="display:block;margin:0 auto 12px;max-width:150px;height:auto;border:0">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Ar-Ge Anketi · Yeni Cevap 📋</div>
    </td></tr>
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:30px 36px 10px">
      <h2 style="margin:0 0 6px;font-size:22px;font-weight:800;color:#1a1a0e">🎯 Yeni Ar-Ge Anketi cevabı</h2>
      <p style="margin:0 0 18px;color:#6b6f63;font-size:14px;line-height:1.6"><strong>' . htmlspecialchars($a->ad_soyad ?? '') . '</strong> Rubito Ar-Ge anketini doldurdu. Detaylar aşağıda; panelden de görebilirsiniz.</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $satirlar . '</table>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 6px"><tr><td align="center">
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
