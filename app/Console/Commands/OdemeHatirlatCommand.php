<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OdemeHatirlatCommand extends Command
{
    protected $signature = 'mail:odeme-hatirlat
        {--dry : Sadece raporla, mail gönderme}
        {--esikler=7,3,1 : Hangi gün eşiklerinde hatırlatma yapılacak (virgülle)}';

    protected $description = 'Vade tarihi yaklaşan bekleyen faturalar için müşterilere otomatik mail';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $esikler = array_map('intval', array_filter(explode(',', (string) $this->option('esikler'))));
        if (empty($esikler)) $esikler = [7, 3, 1];

        $bugun = Carbon::today();
        $toplamGonderildi = 0;
        $toplamAtlandi = 0;
        $toplamHata = 0;

        foreach ($esikler as $kalan) {
            $hedefTarih = $bugun->copy()->addDays($kalan)->toDateString();

            // Bekleyen faturalar (durum=0) + bitis_tarih hedef tarihte
            $faturalar = DB::table('faturalar as f')
                ->leftJoin('uyeler as u', 'u.id', '=', 'f.uyeid')
                ->where('f.durum', 0)
                ->where(function ($q) use ($hedefTarih) {
                    $q->where('f.bitis_tarih', $hedefTarih)
                      ->orWhereRaw("DATE(f.bitis_tarih) = ?", [$hedefTarih]);
                })
                ->whereNotNull('u.email')
                ->where('u.email', '!=', '')
                ->select('f.id', 'f.fatura_no', 'f.baslik', 'f.tutar', 'f.bitis_tarih',
                         'u.email', 'u.ad', 'u.soyad', 'u.id as uye_id')
                ->get();

            $this->info("[{$kalan} gün kala] {$faturalar->count()} fatura bulundu (vade: {$hedefTarih})");

            foreach ($faturalar as $f) {
                $subject = $this->subject($kalan, $f);
                $body    = $this->body($kalan, $f);

                if ($dry) {
                    // Kuru calismada yer kapmadan yalnizca zaten gonderilmis mi diye bak
                    $zaten = DB::table('odeme_hatirlatma_log')
                        ->where('fatura_id', $f->id)
                        ->where('kalan_gun', $kalan)
                        ->where('gonderim_tarihi', $bugun->toDateString())
                        ->exists();
                    if ($zaten) { $toplamAtlandi++; continue; }
                    $this->line("  [DRY] → {$f->email} | {$subject}");
                    $toplamGonderildi++;
                    continue;
                }

                // ── MUKERRER MAIL KORUMASI ─────────────────────────────
                // ONCE LOG SATIRINI YAZ, SONRA MAILI GONDER.
                //
                // Eskiden once mail gonderilip sonra log yaziliyordu. Iki
                // tetik ayni anda calisirsa (zamanlayici 09:00 + panelde
                // duran URL-cron ucu ayni dakikada) ikisi de "gonderilmemis"
                // goruyor ve MUSTERIYE AYNI ANDA IKI MAIL gidiyordu.
                // unique_send_per_day kisiti yalnizca ikinci LOG kaydini
                // engelliyordu; mail coktan gitmis oluyordu -- bu yuzden
                // logda mukerrer kayit gorunmuyor ama sikayet geliyordu.
                //
                // Simdi yeri once kapiyoruz: ikinci calisma insertOrIgnore'dan
                // 0 alir ve mail GONDERMEDEN atlar.
                $kapildi = DB::table('odeme_hatirlatma_log')->insertOrIgnore([
                    'fatura_id'       => $f->id,
                    'email'           => $f->email,
                    'kalan_gun'       => $kalan,
                    'gonderim_tarihi' => $bugun->toDateString(),
                    'durum'           => 'gonderiliyor',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                if (!$kapildi) { $toplamAtlandi++; continue; }

                $logKosulu = fn () => DB::table('odeme_hatirlatma_log')
                    ->where('fatura_id', $f->id)
                    ->where('kalan_gun', $kalan)
                    ->where('gonderim_tarihi', $bugun->toDateString());

                try {
                    $ok = EmailNotificationService::send($f->email, $subject, $body);
                    $logKosulu()->update([
                        'durum'      => $ok ? 'ok' : 'fail',
                        'updated_at' => now(),
                    ]);
                    if ($ok) {
                        $toplamGonderildi++;
                        $this->info("  ✓ {$f->email} (#{$f->id})");
                    } else {
                        $toplamHata++;
                        $this->error("  ✗ {$f->email} (#{$f->id})");
                    }
                } catch (\Throwable $e) {
                    $toplamHata++;
                    // Satir yukarida zaten kapildi; insert degil UPDATE gerekiyor
                    // (insertOrIgnore burada sessizce hicbir sey yapmazdi ve
                    // hata mesaji kaybolurdu).
                    $logKosulu()->update([
                        'durum'      => 'fail',
                        'hata'       => $e->getMessage(),
                        'updated_at' => now(),
                    ]);
                    $this->error("  ✗ {$f->email}: " . $e->getMessage());
                }
            }
        }

        $this->info("\n========= ÖZET =========");
        $this->info("✓ Gönderildi : {$toplamGonderildi}");
        $this->info("↻ Atlandı     : {$toplamAtlandi} (bugün zaten gönderilmiş)");
        $this->info("✗ Hata        : {$toplamHata}");
        $this->info(($dry ? '[DRY-RUN]' : '[GERÇEK]'));

        return 0;
    }

    private function subject(int $kalan, $f): string
    {
        if ($kalan == 1) return "Son 1 gün — Fatura ödemesi #{$f->fatura_no}";
        return "Ödeme hatırlatması ({$kalan} gün kaldı) — Fatura #{$f->fatura_no}";
    }

    private function body(int $kalan, $f): string
    {
        $ad      = trim(($f->ad ?? '') . ' ' . ($f->soyad ?? '')) ?: 'Değerli Müşterimiz';
        $vade    = Carbon::parse($f->bitis_tarih)->format('d.m.Y');
        $tutar   = number_format((float) ($f->tutar ?? 0), 2, ',', '.');
        $baslik  = htmlspecialchars($f->baslik ?? '—');
        $faturaNo = htmlspecialchars((string) ($f->fatura_no ?? $f->id));

        $vurgu = $kalan == 1
            ? '<p style="color:#dc2626;font-weight:700">⚠️ Son 1 gün! Lütfen ödemeyi bugün veya yarın gerçekleştirin.</p>'
            : "<p>Faturanızın vadesi <strong>{$kalan} gün</strong> içinde doluyor.</p>";

        return <<<HTML
<div style="font-family:Inter,system-ui,sans-serif;max-width:600px;margin:auto;padding:24px;background:#fafafa">
<div style="background:#fff;border-radius:12px;padding:30px;border-top:4px solid #b8b62e">
<h2 style="margin:0 0 12px;color:#0f172a">Merhaba {$ad},</h2>
{$vurgu}
<table style="width:100%;border-collapse:collapse;margin:18px 0">
<tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Fatura No</strong></td><td style="padding:8px;border-bottom:1px solid #eee">#{$faturaNo}</td></tr>
<tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Başlık</strong></td><td style="padding:8px;border-bottom:1px solid #eee">{$baslik}</td></tr>
<tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Tutar</strong></td><td style="padding:8px;border-bottom:1px solid #eee;font-weight:700;color:#b8b62e">₺{$tutar}</td></tr>
<tr><td style="padding:8px"><strong>Son Ödeme Tarihi</strong></td><td style="padding:8px;color:#dc2626;font-weight:700">{$vade}</td></tr>
</table>
<p style="color:#475569;font-size:14px">Sorularınız için bizimle iletişime geçebilirsiniz.</p>
<p style="color:#94a3b8;font-size:12px;margin-top:24px">Bu otomatik bir hatırlatma e-postasıdır.</p>
</div>
</div>
HTML;
    }
}
