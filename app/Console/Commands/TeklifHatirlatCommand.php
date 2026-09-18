<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * TEKLİF DEĞERLENDİRME HATIRLATMASI (madde 6).
 * Bir müşteriye teklif gönderildikten {--saat} (varsayılan 24) saat sonra hâlâ
 * 'gonderildi' durumundaysa (onaylanmadı/ödenmedi), müşteriye tek seferlik
 * bir "teklifinizi değerlendirin" hatırlatma maili gönderir.
 * hatirlatma_sent_at ile tekrar gönderim engellenir.
 */
class TeklifHatirlatCommand extends Command
{
    protected $signature = 'mail:teklif-hatirlat
        {--dry : Sadece raporla, mail gönderme}
        {--saat=24 : Teklif üzerinden kaç saat geçince hatırlatılsın}';

    protected $description = 'Yanıtlanmayan tekliflere N saat sonra değerlendirme hatırlatması gönderir';

    public function handle(): int
    {
        if (!Schema::hasTable('crm_musteri_teklifleri')
            || !Schema::hasColumn('crm_musteri_teklifleri', 'hatirlatma_sent_at')) {
            $this->error('crm_musteri_teklifleri / hatirlatma_sent_at bulunamadı (migration çalıştı mı?).');
            return 1;
        }

        $dry   = (bool) $this->option('dry');
        $saat  = max(1, (int) $this->option('saat') ?: 24);
        $sinir = Carbon::now()->subHours($saat);

        $teklifler = DB::table('crm_musteri_teklifleri as t')
            ->leftJoin('crm_customers as c', 'c.id', '=', 't.customer_id')
            ->where('t.durum', 'gonderildi')
            ->whereNull('t.hatirlatma_sent_at')
            ->whereNotNull('t.sent_at')
            ->where('t.sent_at', '<=', $sinir)
            ->whereNotNull('c.email')->where('c.email', '!=', '')
            ->select('t.id', 't.paket_adi', 't.tutar', 't.token', 't.odeme_yontemi',
                'c.adi as musteri', 'c.email as email')
            ->get();

        $this->info("{$saat} saat+ yanıtsız teklif (hatırlatılacak): {$teklifler->count()}");

        $ayarlar = DB::table('ayarlar')->first();
        $gonderildi = 0; $hata = 0;

        foreach ($teklifler as $t) {
            if ($dry) {
                $this->line("  [DRY] → {$t->email} | {$t->paket_adi} | ₺" . number_format((float) $t->tutar, 2, ',', '.'));
                $gonderildi++;
                continue;
            }

            try {
                $url  = url('/teklif/' . $t->token);
                $html = $this->mailHtml($t, $url, $ayarlar);
                EmailNotificationService::send($t->email, 'Teklifinizi Değerlendirdiniz mi? — ' . $t->paket_adi, $html, true);

                DB::table('crm_musteri_teklifleri')->where('id', $t->id)
                    ->update(['hatirlatma_sent_at' => now(), 'updated_at' => now()]);

                $gonderildi++;
                $this->info("  ✓ {$t->email} ({$t->paket_adi})");
            } catch (\Throwable $e) {
                $hata++;
                Log::warning('Teklif hatırlatma maili gönderilemedi', ['teklif_id' => $t->id, 'error' => $e->getMessage()]);
                $this->error("  ✗ {$t->email}: " . $e->getMessage());
            }
        }

        $this->info("\n===== ÖZET =====");
        $this->info("✓ Gönderildi : {$gonderildi}");
        $this->info("✗ Hata       : {$hata}");
        $this->info($dry ? '[DRY-RUN — gerçek mail gönderilmedi]' : '[GERÇEK]');

        return 0;
    }

    private function mailHtml($t, string $url, $ayarlar): string
    {
        $firma   = e($ayarlar->firma_adi ?? 'DN İş Ortağım');
        $siteUrl = e($ayarlar->site_url ?? 'https://crm.ornek.com/');
        $logoUrl = mail_logo_url();
        $musteri = e($t->musteri ?: 'Değerli müşterimiz');
        $paket   = e($t->paket_adi);
        $tutar   = number_format((float) $t->tutar, 2, ',', '.');
        $yontem  = ($t->odeme_yontemi === 'online') ? 'Online Ödeme (Kredi Kartı)' : 'Havale / EFT';
        $yil     = date('Y');

        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0e8;padding:32px 16px">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10)">
    <tr><td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:32px 36px 20px;text-align:center;border-bottom:1px solid #f0f1ec">
      <img src="' . $logoUrl . '" alt="' . $firma . '" width="148" style="display:block;margin:0 auto 16px;max-width:148px;height:auto;border:0">
      <div style="font-size:40px;line-height:1">⏰</div>
      <h2 style="margin:8px 0 0;font-size:21px;font-weight:800;color:#1f2419">Teklifiniz Sizi Bekliyor</h2>
    </td></tr>
    <tr><td style="padding:28px 36px;font-size:15px;line-height:1.7;color:#3a4133">
      <p style="margin:0 0 12px">Merhaba <strong style="color:#6f7320">' . $musteri . '</strong>,</p>
      <p style="margin:0 0 20px">Kısa süre önce size ilettiğimiz teklifi değerlendirme fırsatınız oldu mu? Teklifiniz hâlâ geçerli — dilediğiniz an aşağıdaki bağlantıdan tamamlayabilirsiniz.</p>
      <div style="background-color:#f7f8f3;padding:22px;border-radius:14px;margin:0 0 20px;border:1px solid #eceee6;text-align:center">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#9aa08e;font-weight:600">Teklifiniz</div>
        <div style="font-size:20px;font-weight:700;color:#1f2419;margin:8px 0">' . $paket . '</div>
        <div style="font-size:32px;font-weight:800;color:#6f7320">' . $tutar . ' ₺</div>
        <div style="font-size:12px;color:#7a8270;margin-top:8px">Ödeme Yöntemi: ' . $yontem . '</div>
      </div>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0"><tr><td align="center">
        <a href="' . $url . '" style="display:inline-block;background-color:#b8b62e;color:#1f2419;text-decoration:none;padding:15px 42px;border-radius:10px;font-weight:700;font-size:16px;box-shadow:0 6px 16px rgba(184,182,46,0.32)">TEKLİFİ DEĞERLENDİR →</a>
      </td></tr></table>
      <p style="color:#9aa08e;font-size:12px;text-align:center;margin:0">Buton çalışmazsa bu linki kopyalayıp tarayıcınıza yapıştırın:<br><span style="color:#8a8a1f">' . $url . '</span></p>
      <p style="margin:24px 0 0">Sorularınız için buradayız.<br>Saygılarımızla,<br><strong style="color:#6f7320">' . $firma . '</strong></p>
    </td></tr>
    <tr><td style="padding:22px 36px;background-color:#f7f8f3;border-top:1px solid #eceee6;text-align:center">
      <p style="margin:0;font-size:11px;color:#b3b8a8">&copy; ' . $yil . ' ' . $firma . ' &middot; <a href="' . $siteUrl . '" style="color:#8a8a1f;text-decoration:none">' . $siteUrl . '</a></p>
    </td></tr>
  </table>
</td></tr>
</table>
</body></html>';
    }
}
