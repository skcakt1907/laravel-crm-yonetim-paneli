<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BayiBasvuru;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * İş Ortağı (Bayi) Başvuruları — yönetim.
 *
 * Başvuruda şifre alınmaz. ONAY anında:
 *   uyeler + yoneticiler(rol=3) + bayiler kayıtları oluşturulur,
 *   şifre otomatik üretilir ve başvurana e-posta ile gönderilir.
 * Onaylanmayan başvuru hiçbir hesap oluşturmaz (boş hesap birikmez).
 */
class BayiBasvuruController extends Controller
{
    public function index(Request $request)
    {
        $q = BayiBasvuru::query();

        if ($durum = $request->query('durum')) {
            if (in_array($durum, ['beklemede', 'onaylandi', 'reddedildi'], true)) {
                $q->where('durum', $durum);
            }
        }
        if ($ara = trim((string) $request->query('ara'))) {
            $q->where(function ($w) use ($ara) {
                $w->where('firma_adi', 'like', "%{$ara}%")
                  ->orWhere('ad_soyad', 'like', "%{$ara}%")
                  ->orWhere('email', 'like', "%{$ara}%")
                  ->orWhere('telefon', 'like', "%{$ara}%");
            });
        }

        $basvurular = $q->orderByDesc('id')->paginate(20)->withQueryString();
        $bekleyen   = BayiBasvuru::where('durum', 'beklemede')->count();

        return view('admin.bayi-basvurulari.index', compact('basvurular', 'bekleyen'));
    }

    public function goster(int $id)
    {
        $basvuru = BayiBasvuru::findOrFail($id);
        if (!$basvuru->okundu) {
            $basvuru->okundu = 1;
            $basvuru->save();
        }
        return view('admin.bayi-basvurulari.goster', compact('basvuru'));
    }

    /** Başvuruyu onayla → hesapları oluştur + şifreyi mail at */
    public function onayla(Request $request, int $id)
    {
        $basvuru = BayiBasvuru::findOrFail($id);

        if ($basvuru->durum !== 'beklemede') {
            return back()->with('error', 'Bu başvuru daha önce sonuçlandırılmış.');
        }

        $komisyon = (float) $request->input('komisyon_orani', 10);
        if ($komisyon < 0 || $komisyon > 100) {
            $komisyon = 10;
        }

        // Aynı e-posta ile üye/yönetici zaten var mı?
        $mevcutUye = DB::table('uyeler')->where('email', $basvuru->email)->first();
        if (DB::table('yoneticiler')->where('email', $basvuru->email)->exists()) {
            return back()->with('error', 'Bu e-posta ile zaten bir panel hesabı var. Önce mevcut hesabı kontrol edin.');
        }

        $sifre    = Str::password(10, true, true, false); // okunabilir, sembolsüz
        $hash     = Hash::make($sifre);
        $bayiKodu = strtoupper(Str::random(8));

        $parcalar = preg_split('/\s+/', trim($basvuru->ad_soyad), 2);
        $ad       = $parcalar[0] ?? $basvuru->ad_soyad;
        $soyad    = $parcalar[1] ?? '';

        // DİKKAT: uyeler/yoneticiler/bayiler tabloları MyISAM — transaction ÇALIŞMAZ.
        // Bu yüzden hata hâlinde oluşturduğumuz kayıtları ELLE geri alıyoruz,
        // yoksa yarım kalmış hesaplar birikir.
        $uyeId = $yoneticiId = $bayiId = null;
        $uyeYeniOlusturuldu = false;

        try {
            // 1) Üye kaydı (varsa yeniden kullan)
            if ($mevcutUye) {
                $uyeId = $mevcutUye->id;
                DB::table('uyeler')->where('id', $uyeId)->update([
                    'sifre' => $hash, 'bayi' => 1, 'durum' => 1, 'tarih' => Carbon::now(),
                ]);
            } else {
                $uyeId = DB::table('uyeler')->insertGetId([
                    'ad'        => $ad,
                    'soyad'     => $soyad,
                    'email'     => $basvuru->email,
                    'sifre'     => $hash,
                    'telefon'   => $basvuru->telefon,
                    'firmaadi'  => $basvuru->firma_adi,
                    'sehir'     => $basvuru->il,
                    'ilce'      => $basvuru->ilce,
                    'kvkk_onay' => 1,
                    'durum'     => 1,
                    'bayi'      => 1,
                    'ktarih'    => Carbon::now(),
                    'tarih'     => Carbon::now(),
                ]);
                $uyeYeniOlusturuldu = true;
            }

            // 2) Panel hesabı (bayi rolü = 3, aktif)
            $yoneticiId = DB::table('yoneticiler')->insertGetId([
                'kullaniciadi' => 'bayi_' . $uyeId,
                'email'        => $basvuru->email,
                'eposta'       => $basvuru->email,
                'sifre'        => $hash,
                'adi'          => $basvuru->ad_soyad,
                'telefon'      => $basvuru->telefon,
                'rol'          => 3,
                'yetki'        => 1,
                'durum'        => 1,
                'created_at'   => Carbon::now(),
                'updated_at'   => Carbon::now(),
            ]);

            // 3) Bayi kaydı
            $bayiId = DB::table('bayiler')->insertGetId([
                'uye_id'         => $uyeId,
                'yonetici_id'    => $yoneticiId,
                'bayi_kodu'      => $bayiKodu,
                'firma_adi'      => $basvuru->firma_adi,
                'telefon'        => $basvuru->telefon,
                'il'             => $basvuru->il,
                'ilce'           => $basvuru->ilce,
                'komisyon_orani' => $komisyon,
                'durum'          => 1,
                'onay_durumu'    => 1,
                'onay_tarihi'    => Carbon::now(),
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // Elle geri alma (MyISAM'de otomatik rollback yok)
            try {
                if ($bayiId)              DB::table('bayiler')->where('id', $bayiId)->delete();
                if ($yoneticiId)          DB::table('yoneticiler')->where('id', $yoneticiId)->delete();
                if ($uyeYeniOlusturuldu)  DB::table('uyeler')->where('id', $uyeId)->delete();
            } catch (\Throwable $temizlik) {
                Log::error('Bayi onay geri alma hatası', ['basvuru' => $id, 'err' => $temizlik->getMessage()]);
            }

            Log::error('Bayi başvuru onay hatası', ['basvuru' => $id, 'err' => $e->getMessage()]);
            return back()->with('error', 'Onay sırasında hata oluştu, oluşturulan kayıtlar geri alındı: ' . $e->getMessage());
        }

        // Başvuruyu işaretle (korumalı alanlar explicit)
        $basvuru->durum        = 'onaylandi';
        $basvuru->onaylayan_id = session('admin_id');
        $basvuru->onay_tarihi  = Carbon::now();
        $basvuru->uye_id       = $uyeId;
        $basvuru->yonetici_id  = $yoneticiId;
        $basvuru->bayi_id      = $bayiId;
        $basvuru->save();

        // Giriş bilgilerini gönder — mail giderse akış devam etmeli
        $mailGitti = false;
        try {
            $this->girisBilgisiGonder($basvuru, $sifre, $bayiKodu);
            $mailGitti = true;
        } catch (\Throwable $e) {
            Log::warning('Bayi onay maili gönderilemedi', ['basvuru' => $id, 'err' => $e->getMessage()]);
        }

        return redirect()->route('admin.bayi-basvurulari.goster', $id)->with(
            'success',
            $mailGitti
                ? 'Başvuru onaylandı. Giriş bilgileri ' . $basvuru->email . ' adresine gönderildi.'
                : 'Başvuru onaylandı ANCAK e-posta gönderilemedi. Şifreyi elle iletin: ' . $sifre
        );
    }

    /** Başvuruyu reddet — hesap oluşturulmaz */
    public function reddet(Request $request, int $id)
    {
        $basvuru = BayiBasvuru::findOrFail($id);

        if ($basvuru->durum !== 'beklemede') {
            return back()->with('error', 'Bu başvuru daha önce sonuçlandırılmış.');
        }

        $data = $request->validate([
            'red_nedeni' => 'nullable|string|max:500',
        ]);

        $basvuru->durum        = 'reddedildi';
        $basvuru->red_nedeni   = $data['red_nedeni'] ?? null;
        $basvuru->onaylayan_id = session('admin_id');
        $basvuru->onay_tarihi  = Carbon::now();
        $basvuru->save();

        try {
            $this->redBilgisiGonder($basvuru);
        } catch (\Throwable $e) {
            Log::warning('Bayi red maili gönderilemedi', ['basvuru' => $id, 'err' => $e->getMessage()]);
        }

        return back()->with('success', 'Başvuru reddedildi ve başvurana bilgi verildi.');
    }

    public function sil(int $id)
    {
        $basvuru = BayiBasvuru::find($id);
        if ($basvuru) {
            $basvuru->delete();
        }
        return redirect()->route('admin.bayi-basvurulari.index')->with('success', 'Başvuru silindi.');
    }

    /* ───────────── mailler ───────────── */

    private function girisBilgisiGonder(BayiBasvuru $b, string $sifre, string $bayiKodu): void
    {
        $girisUrl = rtrim($this->siteUrl(), '/') . '/admin/giris';

        $govde = $this->mailGovde(
            '🎉 Başvurunuz onaylandı!',
            'Sayın ' . $b->ad_soyad . ', iş ortaklığı başvurunuz onaylandı. Bayi panelinize aşağıdaki bilgilerle giriş yapabilirsiniz. Güvenliğiniz için ilk girişten sonra şifrenizi değiştirmenizi öneririz.',
            [
                'Giriş adresi' => $girisUrl,
                'Kullanıcı adı / E-posta' => $b->email,
                'Geçici şifre' => $sifre,
                'Bayi kodunuz' => $bayiKodu,
            ],
            $girisUrl,
            'Panele Giriş Yap →'
        );

        EmailNotificationService::send($b->email, '🎉 İş Ortaklığı Başvurunuz Onaylandı — Giriş Bilgileriniz', $govde);
    }

    private function redBilgisiGonder(BayiBasvuru $b): void
    {
        $aciklama = 'Sayın ' . $b->ad_soyad . ', iş ortaklığı başvurunuzu değerlendirdik. '
            . 'Maalesef şu aşamada olumlu sonuçlandıramadık. İlginiz için teşekkür ederiz.';

        $satirlar = ['Firma' => $b->firma_adi];
        if ($b->red_nedeni) {
            $satirlar['Not'] = $b->red_nedeni;
        }

        EmailNotificationService::send(
            $b->email,
            'İş Ortaklığı Başvurunuz Hakkında',
            $this->mailGovde('Başvurunuz hakkında', $aciklama, $satirlar)
        );
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
              . '<a href="' . htmlspecialchars($butonUrl) . '" style="display:inline-block;background-color:#b8b62e;color:#1a1a0e;text-decoration:none;padding:14px 40px;border-radius:10px;font-weight:800;font-size:15px">' . htmlspecialchars($butonMetin) . '</a>'
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
