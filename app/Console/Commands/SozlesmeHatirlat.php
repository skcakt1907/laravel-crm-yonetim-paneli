<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Sözleşme bitiş hatırlatması — bitişe 1 HAFTA kala bildirim gönderir.
 *
 * Alıcılar: sözleşmenin bağlı olduğu müşteri (varsa) + yönetim ekibi.
 * Her sözleşme için yalnızca BİR kez gönderilir (bitis_bildirim_at damgası).
 * Bitiş tarihi değişirse damga sıfırlanır, yeni tarihe göre tekrar gider.
 */
class SozlesmeHatirlat extends Command
{
    protected $signature   = 'sozlesme:hatirlat {--gun=7 : Bitişe kaç gün kala uyarılsın}';
    protected $description = 'Bitişi yaklaşan sözleşmeler için hatırlatma e-postası gönderir';

    /** Bildirim gidecek yöneticiler */
    private const ALICI_KULLANICILAR = ['nurselinan', 'dilanatescom', 'NesimiAtes'];

    public function handle(): int
    {
        if (!Schema::hasTable('crm_sozlesmeler') || !Schema::hasColumn('crm_sozlesmeler', 'bitis_tarihi')) {
            $this->warn('crm_sozlesmeler.bitis_tarihi yok, çıkılıyor.');
            return self::SUCCESS;
        }

        $gun   = max(1, (int) $this->option('gun'));
        $hedef = Carbon::now()->startOfDay()->addDays($gun);

        try {
            $liste = DB::table('crm_sozlesmeler')
                ->whereNull('bitis_bildirim_at')
                ->whereNotNull('bitis_tarihi')
                ->whereIn('durum', ['aktif', 'imzalandi'])
                ->whereDate('bitis_tarihi', '<=', $hedef)
                ->whereDate('bitis_tarihi', '>=', Carbon::now()->startOfDay())
                ->limit(100)->get();
        } catch (\Throwable $e) {
            Log::error('sozlesme:hatirlat sorgu', ['e' => $e->getMessage()]);
            return self::FAILURE;
        }

        if ($liste->isEmpty()) {
            $this->info('Hatırlatılacak sözleşme yok.');
            return self::SUCCESS;
        }

        $yonetim = $this->yonetimEpostalari();
        $sayac = 0;

        foreach ($liste as $s) {
            $kalan = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($s->bitis_tarihi)->startOfDay(), false);
            $musteri = $s->musteri_id
                ? DB::table('crm_customers')->where('id', $s->musteri_id)->first(['adi', 'email'])
                : null;

            $govde = $this->govde($s, $musteri->adi ?? ($s->taraf_adi ?: '—'), (int) $kalan);
            $konu  = '📄 Sözleşme bitiyor (' . $kalan . ' gün) — ' . ($s->baslik ?: $s->sozlesme_no);

            // Müşteriye
            if ($musteri && !empty($musteri->email)) {
                try { EmailNotificationService::send($musteri->email, $konu, $govde); } catch (\Throwable $e) {}
            }
            // Yönetime
            foreach ($yonetim as $mail) {
                try { EmailNotificationService::send($mail, $konu, $govde); } catch (\Throwable $e) {}
            }

            DB::table('crm_sozlesmeler')->where('id', $s->id)
                ->update(['bitis_bildirim_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

            $sayac++;
            Log::info('Sözleşme bitiş hatırlatması gönderildi', ['sozlesme' => $s->id, 'kalan_gun' => $kalan]);
        }

        $this->info($sayac . ' sözleşme için hatırlatma gönderildi.');

        return self::SUCCESS;
    }

    private function yonetimEpostalari(): array
    {
        try {
            return DB::table('yoneticiler')
                ->whereIn('kullaniciadi', self::ALICI_KULLANICILAR)
                ->where('durum', 1)
                ->whereNotNull('email')->where('email', '!=', '')
                ->pluck('email')->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function govde(object $s, string $taraf, int $kalan): string
    {
        $sat = function (string $etiket, ?string $deger) {
            $deger = trim((string) $deger);
            if ($deger === '') return '';
            return '<tr><td style="padding:11px 0;border-bottom:1px solid #f0efe6">'
                . '<div style="font-size:12px;font-weight:800;color:#8a8718;text-transform:uppercase;margin-bottom:3px">' . htmlspecialchars($etiket) . '</div>'
                . '<div style="font-size:14px;color:#2b2b1f;font-weight:700">' . htmlspecialchars($deger) . '</div></td></tr>';
        };

        $satirlar  = $sat('Sözleşme', $s->baslik ?: $s->sozlesme_no);
        $satirlar .= $sat('Sözleşme No', $s->sozlesme_no);
        $satirlar .= $sat('Taraf', $taraf);
        $satirlar .= $sat('Başlangıç', $s->baslangic_tarihi ? Carbon::parse($s->baslangic_tarihi)->format('d.m.Y') : null);
        $satirlar .= $sat('Bitiş', Carbon::parse($s->bitis_tarihi)->format('d.m.Y'));
        $satirlar .= $sat('Kalan süre', $kalan . ' gün');
        $satirlar .= $sat('Tutar', $s->tutar ? number_format((float) $s->tutar, 2, ',', '.') . ' TL' : null);

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:Arial,Helvetica,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0e8;padding:32px 16px"><tr><td align="center">
  <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#fff;border-radius:18px;overflow:hidden">
    <tr><td style="background:#1a2332;padding:26px 36px;text-align:center">
      <img src="https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png" width="150" alt="DN Kreatif" style="display:block;margin:0 auto 12px">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Sözleşme Hatırlatması 📄</div>
    </td></tr>
    <tr><td style="height:5px;background:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:30px 36px 14px">
      <h2 style="margin:0 0 8px;font-size:21px;color:#1a1a0e">Sözleşmenizin bitmesine ' . $kalan . ' gün kaldı</h2>
      <p style="margin:0 0 18px;color:#6b6f63;font-size:14px;line-height:1.7">
        Aşağıdaki sözleşmenin süresi dolmak üzere. Yenileme için görüşmek isterseniz bize ulaşabilirsiniz.
      </p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $satirlar . '</table>
    </td></tr>
    <tr><td style="background:#1a2332;padding:20px 36px;text-align:center">
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . date('Y') . ' DN Kreatif &middot; İş Ortağım</p>
    </td></tr>
  </table>
</td></tr></table></body></html>';
    }
}
