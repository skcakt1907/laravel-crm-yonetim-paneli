<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * İÇ EKİP bildirimi: süresine 7 gün (veya daha az) kalan domainlerin ÖZETİNİ
 * yönetim ekibine (Nurseli İnan, Dilan Ateş, Nesimi Ateş) mail atar.
 * NOT: Müşteriye giden yenileme maili AYRI command'dır (mail:domain-yenileme-hatirlat).
 */
class DomainAdminUyariCommand extends Command
{
    protected $signature = 'mail:domain-admin-uyari
        {--dry : Sadece raporla, mail gönderme}
        {--esikler=30,15,7,1 : Tam kaç gün kala uyarı verilsin (virgülle)}';

    protected $description = 'Süresine tam 30/15/7/1 gün kalan domainlerin özetini yönetim ekibine mail atar';

    /** Bildirim gidecek yöneticilerin kullanıcı adları (e-posta yoneticiler tablosundan çekilir) */
    private const ALICI_KULLANICILAR = ['nurselinan', 'dilanatescom', 'NesimiAtes'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $esikler = array_map('intval', array_filter(explode(',', (string) $this->option('esikler'))));
        if (empty($esikler)) $esikler = [30, 15, 7, 1];

        $bugun         = Carbon::today();
        $hedefTarihler = array_map(fn ($g) => $bugun->copy()->addDays($g)->toDateString(), $esikler);
        $esikStr       = implode('/', $esikler);

        $hepsi = collect();

        // 1) Online domainler (domain_orders) — tam eşik günlerinde bitenler
        if (Schema::hasTable('domain_orders')) {
            $hepsi = $hepsi->concat(
                DB::table('domain_orders as d')
                    ->leftJoin('uyeler as u', 'u.id', '=', 'd.user_id')
                    ->whereNotNull('d.expires_at')
                    ->whereIn(DB::raw('DATE(d.expires_at)'), $hedefTarihler)
                    ->whereIn('d.status', ['active', 'registered'])
                    ->select(
                        'd.domain',
                        DB::raw('d.expires_at as bitis'),
                        DB::raw("TRIM(CONCAT(COALESCE(u.ad,''),' ',COALESCE(u.soyad,''))) as musteri"),
                        DB::raw('u.email as musteri_email'),
                        DB::raw("'Online' as kaynak")
                    )->get()
            );
        }

        // 2) Manuel domainler (satilanlar, tipi=3)
        if (Schema::hasTable('satilanlar')) {
            $cols = Schema::getColumnListing('satilanlar');
            $bitisSut = in_array('bitis_tarih', $cols, true) ? 'bitis_tarih'
                      : (in_array('bitis_tarihi', $cols, true) ? 'bitis_tarihi' : null);
            if ($bitisSut) {
                $hepsi = $hepsi->concat(
                    DB::table('satilanlar as s')
                        ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                        ->where('s.tipi', '3')->where('s.durum', 1)
                        ->whereNotNull("s.$bitisSut")
                        ->whereIn(DB::raw("DATE(s.$bitisSut)"), $hedefTarihler)
                        ->select(
                            's.domain',
                            DB::raw("s.$bitisSut as bitis"),
                            DB::raw("COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.ad,''),' ',COALESCE(u.soyad,''))),''), u.firmaadi, '—') as musteri"),
                            DB::raw('u.email as musteri_email'),
                            DB::raw("'Manuel' as kaynak")
                        )->get()
                );
            }
        }

        // Kalan gün + sırala (en acil önce)
        $hepsi = $hepsi->map(function ($d) use ($bugun) {
            $d->kalan = (int) $bugun->diffInDays(Carbon::parse($d->bitis)->startOfDay(), false);
            return $d;
        })->sortBy('kalan')->values();

        $this->info("Tam {$esikStr} gün kala bitecek domain: {$hepsi->count()}");

        if ($hepsi->isEmpty()) {
            $this->info('Bildirilecek domain yok, mail gönderilmedi.');
            return 0;
        }

        // Alıcılar (yoneticiler tablosundan)
        $alicilar = DB::table('yoneticiler')
            ->whereIn('kullaniciadi', self::ALICI_KULLANICILAR)
            ->where('durum', 1)
            ->whereNotNull('email')->where('email', '!=', '')
            ->pluck('email', 'adi');

        if ($alicilar->isEmpty()) {
            $this->error('Alıcı bulunamadı (yoneticiler tablosunda nurselinan/dilanatescom/NesimiAtes yok).');
            return 1;
        }

        $ayarlar = Schema::hasTable('ayarlar') ? DB::table('ayarlar')->first() : null;

        $subject = "⚠️ {$hepsi->count()} domain yenileme uyarısı ({$esikStr} gün kala) — İş Ortağım";
        $body    = $this->body($hepsi, $esikStr, $ayarlar);

        foreach ($alicilar as $ad => $email) {
            if ($dry) {
                $this->line("  [DRY] → {$email} ({$ad})");
                continue;
            }
            try {
                EmailNotificationService::send($email, $subject, $body);
                $this->info("  ✓ {$email} ({$ad})");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$email}: " . $e->getMessage());
            }
        }

        return 0;
    }

    /**
     * İş Ortağım markalı, e-posta istemcisine dostu (tablo tabanlı) şablon.
     * dnkreatif rezervasyon maili stili: logo başlık + kart + renkli rozetler + koyu footer.
     */
    private function body($domainler, string $esikStr, $ayarlar = null): string
    {
        $bugun    = Carbon::today();
        $tarihStr = $bugun->format('d.m.Y');
        $adet     = count($domainler);

        $firma   = htmlspecialchars($ayarlar->firma_adi ?? 'İş Ortağım');
        $siteUrl = htmlspecialchars($ayarlar->site_url ?? 'https://crm.ornek.com/');
        $logoUrl = mail_logo_url();
        $panelUrl = rtrim($siteUrl, '/') . '/admin/crm/domains';
        $tel     = htmlspecialchars($ayarlar->firma_telefon ?? '0 (850) 307 95 48');
        $mailAdr = htmlspecialchars($ayarlar->firma_email ?? 'isortagim@ornek.com');
        $yil     = date('Y');

        // Domain satırları — resimdeki detay-satırı stili
        $satirlar = '';
        foreach ($domainler as $d) {
            $bitis = Carbon::parse($d->bitis)->format('d.m.Y (l)');
            $bitis = str_replace(
                ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
                ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'],
                $bitis
            );
            $acil  = $d->kalan <= 3;
            $rozetBg = $acil ? '#fdecea' : '#fff6e6';
            $rozetRenk = $acil ? '#c0392b' : '#b7791f';
            $mus   = htmlspecialchars($d->musteri ?: '—');
            $mail  = htmlspecialchars($d->musteri_email ?: '—');
            $dom   = htmlspecialchars($d->domain);
            $kaynak = htmlspecialchars($d->kaynak);

            $satirlar .= <<<ROW
<tr><td style="padding:16px 20px;border:1px solid #ececec;border-radius:12px;background:#fbfbf7">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td style="font-size:16px;font-weight:800;color:#1a1a0e">{$dom}</td>
      <td align="right"><span style="display:inline-block;background:{$rozetBg};color:{$rozetRenk};font-size:12.5px;font-weight:800;padding:5px 12px;border-radius:999px;white-space:nowrap">⏳ {$d->kalan} gün kaldı</span></td>
    </tr>
    <tr><td colspan="2" style="padding-top:10px;font-size:13px;color:#6b6f63;line-height:1.7">
      <strong style="color:#3a3a2e">Müşteri:</strong> {$mus} &middot; <span style="color:#8a8676">{$mail}</span><br>
      <strong style="color:#3a3a2e">Bitiş:</strong> {$bitis} &nbsp;·&nbsp; <strong style="color:#3a3a2e">Kaynak:</strong> {$kaynak}
    </td></tr>
  </table>
</td></tr>
<tr><td style="height:12px;font-size:0;line-height:0">&nbsp;</td></tr>
ROW;
        }

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">

    <!-- Koyu başlık + logo (resimdeki gibi) -->
    <tr><td style="background-color:#1a2332;padding:26px 36px 22px;text-align:center">
      <img src="' . $logoUrl . '" alt="' . $firma . '" width="150" style="display:block;margin:0 auto 12px;max-width:150px;height:auto;border:0">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Domain &amp; Hosting Takip · İç Bildirim ⚠️</div>
    </td></tr>
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>

    <!-- Gövde -->
    <tr><td style="padding:30px 36px 10px">
      <h2 style="margin:0 0 6px;font-size:22px;font-weight:800;color:#1a1a0e">🔔 Domain Yenileme Uyarısı</h2>
      <p style="margin:0 0 4px;color:#6b6f63;font-size:14px;line-height:1.6">Süresine tam <strong style="color:#8a8718">' . $esikStr . ' gün</strong> kalan <strong>' . $adet . '</strong> domain var. Müşterilerle iletişime geçip yenileme sürecini başlatın.</p>
      <p style="margin:0 0 18px;color:#a29e90;font-size:12.5px">Bildirim tarihi: ' . $tarihStr . '</p>

      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $satirlar . '</table>

      <!-- CTA -->
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0 6px"><tr><td align="center">
        <a href="' . $panelUrl . '" style="display:inline-block;background-color:#1a2332;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:10px;font-weight:700;font-size:15px">Domain &amp; Hosting Takip →</a>
      </td></tr></table>
    </td></tr>

    <!-- Koyu footer + iletişim (resimdeki gibi) -->
    <tr><td style="background-color:#1a2332;padding:20px 36px;text-align:center">
      <p style="margin:0 0 4px;color:#c7cbd6;font-size:13px">📞 ' . $tel . ' &nbsp;·&nbsp; ✉️ ' . $mailAdr . '</p>
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . $yil . ' ' . $firma . ' &middot; <a href="' . $siteUrl . '" style="color:#b8b62e;text-decoration:none">' . $siteUrl . '</a> &middot; Otomatik iç bildirim</p>
    </td></tr>

  </table>
</td></tr>
</table>
</body></html>';
    }
}
