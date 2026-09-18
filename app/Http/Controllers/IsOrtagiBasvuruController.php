<?php

namespace App\Http\Controllers;

use App\Models\BayiBasvuru;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Halka açık İŞ ORTAĞI (BAYİ) BAŞVURUSU.
 *
 * Üyelik/giriş gerektirmez. Başvuruda şifre ALINMAZ; hesap yalnızca admin
 * onayında oluşturulur (bkz. Admin\BayiBasvuruController@onayla) ve şifre
 * otomatik üretilip başvurana e-posta ile gönderilir.
 *
 * Giriş yapmış üyeler için ayrı bir akış zaten var: BayiBasvuruController.
 */
class IsOrtagiBasvuruController extends Controller
{
    /**
     * Yeni başvuru bildirimi gidecek ROLLER (roller.id).
     * 1 = Patron, 5 = Muhasebe, 21 = Yazılımcı.
     * Sabit kullanıcı adı yerine rol kullanılıyor: ekibe yeni biri katılınca
     * kod düzenlemeye gerek kalmıyor.
     */
    private const ALICI_ROLLER = [1, 5, 21];

    public function goster()
    {
        return view('tema.is-ortagi-basvuru');
    }

    public function kaydet(Request $request)
    {
        // Bal küpü (honeypot): bot doldurursa sessizce başarılı gibi dön.
        if (trim((string) $request->input('website')) !== '') {
            return redirect()->route('isortagi.basvuru')->with('basvuru_ok', true);
        }

        $data = $request->validate([
            'firma_adi' => 'required|string|max:190',
            'ad_soyad'  => 'required|string|max:190',
            'email'     => 'required|email|max:190',
            'telefon'   => 'required|string|max:40',
            'il'        => 'required|string|max:100',
            'ilce'      => 'nullable|string|max:100',
            'faaliyet'  => 'nullable|string|max:190',
            'mesaj'     => 'nullable|string|max:2000',
            'kvkk'      => 'accepted',
        ], [
            'kvkk.accepted' => 'Devam edebilmek için sözleşmeleri onaylamanız gerekir.',
        ], [
            'firma_adi' => 'firma / işletme adı',
            'ad_soyad'  => 'yetkili ad soyad',
            'il'        => 'şehir',
        ]);

        // Aynı e-posta ile bekleyen başvuru varsa tekrar oluşturma
        $bekleyen = BayiBasvuru::where('email', $data['email'])
            ->where('durum', 'beklemede')->first();
        if ($bekleyen) {
            return redirect()->route('isortagi.basvuru')
                ->with('zaten_var', true);
        }

        $basvuru = new BayiBasvuru();
        $basvuru->fill([
            'firma_adi' => $data['firma_adi'],
            'ad_soyad'  => $data['ad_soyad'],
            'email'     => $data['email'],
            'telefon'   => $data['telefon'],
            'il'        => $data['il'],
            'ilce'      => $data['ilce'] ?? null,
            'faaliyet'  => $data['faaliyet'] ?? null,
            'mesaj'     => $data['mesaj'] ?? null,
            'kvkk'      => true,
            'ip'        => $request->ip(),
        ]);
        $basvuru->durum = 'beklemede';   // korumalı alan: explicit
        $basvuru->save();

        // Bildirimler akışı bozmasın
        try {
            $this->adminBildirimEkle($basvuru);
        } catch (\Throwable $e) {
            Log::warning('Bayi başvuru zil bildirimi: ' . $e->getMessage());
        }
        try {
            $this->yoneticilereBildir($basvuru);
        } catch (\Throwable $e) {
            Log::warning('Bayi başvuru bildirim maili: ' . $e->getMessage());
        }
        try {
            $this->basvuranaBilgi($basvuru);
        } catch (\Throwable $e) {
            Log::warning('Bayi başvuru teşekkür maili: ' . $e->getMessage());
        }

        return redirect()->route('isortagi.basvuru')->with('basvuru_ok', true);
    }

    /* ───────────── bildirimler ───────────── */

    /**
     * Bildirim alıcıları: rolü ALICI_ROLLER içinde olan AKTİF yöneticiler.
     * yoneticiler tablosunda hem email hem eposta kolonu var; bazı kayıtlarda
     * email boş, eposta dolu. İkisini de deniyoruz (önce email, sonra eposta).
     */
    private function bildirimAlicilari()
    {
        if (!Schema::hasTable('yoneticiler')) {
            return collect();
        }

        return DB::table('yoneticiler')
            ->whereIn('rol', self::ALICI_ROLLER)
            ->where('durum', 1)
            ->get()
            ->map(function ($y) {
                $mail = trim((string) ($y->email ?? ''));
                if ($mail === '') {
                    $mail = trim((string) ($y->eposta ?? ''));
                }
                return (object) [
                    'id'   => $y->id,
                    'adi'  => $y->adi ?? '',
                    'mail' => $mail,
                ];
            })
            ->filter(function ($y) {
                return $y->mail !== '' && filter_var($y->mail, FILTER_VALIDATE_EMAIL);
            })
            ->unique('mail')
            ->values();
    }

    /** Panel içi çan (zil) bildirimi — admin_bildirimler tablosuna kayıt */
    private function adminBildirimEkle(BayiBasvuru $b): void
    {
        if (!Schema::hasTable('admin_bildirimler')) return;

        $satir = [
            'tip'    => 'bayi_basvuru',
            'baslik' => 'Yeni iş ortağı başvurusu',
            'mesaj'  => $b->firma_adi . ' (' . $b->ad_soyad . ') iş ortağı olmak için başvurdu.',
            'okundu' => 0,
        ];

        // Şemaya göre isteğe bağlı kolonlar (kolon yoksa atlanır, patlamaz)
        $opsiyonel = [
            'ilgili_id'    => $b->id,
            'ilgili_tablo' => 'bayi_basvurulari',
            'link'         => '/admin/bayi-basvurulari/' . $b->id,
            'url'          => '/admin/bayi-basvurulari/' . $b->id,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
        foreach ($opsiyonel as $kolon => $deger) {
            if (Schema::hasColumn('admin_bildirimler', $kolon)) {
                $satir[$kolon] = $deger;
            }
        }

        // yonetici_id kolonu varsa kişi bazlı satır, yoksa tek genel satır
        if (Schema::hasColumn('admin_bildirimler', 'yonetici_id')) {
            $alicilar = $this->bildirimAlicilari();
            if ($alicilar->isEmpty()) {
                DB::table('admin_bildirimler')->insert($satir + ['yonetici_id' => null]);
                return;
            }
            $toplu = [];
            foreach ($alicilar as $a) {
                $toplu[] = $satir + ['yonetici_id' => $a->id];
            }
            DB::table('admin_bildirimler')->insert($toplu);
        } else {
            DB::table('admin_bildirimler')->insert($satir);
        }
    }

    private function yoneticilereBildir(BayiBasvuru $b): void
    {
        $alicilar = $this->bildirimAlicilari()->pluck('mail');

        if ($alicilar->isEmpty()) {
            Log::warning('Bayi başvuru bildirimi: e-postası tanımlı aktif yönetici bulunamadı.');
            return;
        }

        $panel = rtrim($this->siteUrl(), '/') . '/admin/bayi-basvurulari/' . $b->id;
        $govde = $this->mailGovde(
            '🤝 Yeni iş ortağı başvurusu',
            $b->firma_adi . ' (' . $b->ad_soyad . ') iş ortağı olmak için başvurdu.',
            [
                'Firma'    => $b->firma_adi,
                'Yetkili'  => $b->ad_soyad,
                'E-posta'  => $b->email,
                'Telefon'  => $b->telefon,
                'Şehir'    => trim($b->il . ' ' . ($b->ilce ?? '')),
                'Faaliyet' => $b->faaliyet,
                'Mesaj'    => $b->mesaj,
            ],
            $panel,
            'Başvuruyu Panelde Aç →'
        );

        foreach ($alicilar as $email) {
            EmailNotificationService::send($email, '🤝 Yeni İş Ortağı Başvurusu — ' . $b->firma_adi, $govde);
        }
    }

    private function basvuranaBilgi(BayiBasvuru $b): void
    {
        $govde = $this->mailGovde(
            'Başvurunuzu aldık',
            'Sayın ' . $b->ad_soyad . ', iş ortaklığı başvurunuz bize ulaştı. Ekibimiz en kısa sürede değerlendirip size dönüş yapacaktır.',
            [
                'Firma'   => $b->firma_adi,
                'Yetkili' => $b->ad_soyad,
                'Telefon' => $b->telefon,
                'Şehir'   => trim($b->il . ' ' . ($b->ilce ?? '')),
            ]
        );

        EmailNotificationService::send($b->email, 'İş Ortaklığı Başvurunuz Alındı', $govde);
    }

    private function siteUrl(): string
    {
        try {
            $a = Schema::hasTable('ayarlar') ? DB::table('ayarlar')->first() : null;
            return $a->site_url ?? 'https://crm.ornek.com/';
        } catch (\Throwable $e) {
            return 'https://crm.ornek.com/';
        }
    }

    /** Kurumsal mail şablonu (tablo bazlı + inline CSS — tüm istemcilerde bozulmaz) */
    private function mailGovde(string $baslik, string $aciklama, array $satirlar, ?string $butonUrl = null, ?string $butonMetin = null): string
    {
        $logo = 'https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png';

        $ic = '';
        foreach ($satirlar as $etiket => $deger) {
            $deger = trim((string) $deger);
            if ($deger === '') continue;
            $ic .= '<tr><td style="padding:12px 0;border-bottom:1px solid #f0efe6">'
                . '<div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">' . htmlspecialchars($etiket) . '</div>'
                . '<div style="font-size:14px;color:#2b2b1f;line-height:1.6">' . nl2br(htmlspecialchars($deger)) . '</div></td></tr>';
        }

        $buton = ($butonUrl && $butonMetin)
            ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0 6px"><tr><td align="center">'
              . '<a href="' . htmlspecialchars($butonUrl) . '" style="display:inline-block;background-color:#1a2332;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:10px;font-weight:700;font-size:15px">' . htmlspecialchars($butonMetin) . '</a>'
              . '</td></tr></table>'
            : '';

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px"><tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden">
    <tr><td style="background-color:#1a2332;padding:26px 36px 22px;text-align:center">
      <img src="' . $logo . '" alt="DN Kreatif" width="150" style="display:block;margin:0 auto 12px;max-width:150px;height:auto;border:0">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">İş Ortağı Programı 🤝</div>
    </td></tr>
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:30px 36px 14px">
      <h2 style="margin:0 0 6px;font-size:22px;font-weight:800;color:#1a1a0e">' . htmlspecialchars($baslik) . '</h2>
      <p style="margin:0 0 18px;color:#6b6f63;font-size:14px;line-height:1.6">' . htmlspecialchars($aciklama) . '</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $ic . '</table>
      ' . $buton . '
    </td></tr>
    <tr><td style="background-color:#1a2332;padding:20px 36px;text-align:center">
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . date('Y') . ' DN Kreatif · İş Ortağım &middot; Otomatik bildirim</p>
    </td></tr>
  </table>
</td></tr></table></body></html>';
    }
}