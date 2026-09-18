<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use App\Services\RandevuSms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DomainYenilemeHatirlatCommand extends Command
{
    protected $signature = 'mail:domain-yenileme-hatirlat
        {--dry : Sadece raporla, mail gönderme}
        {--esikler=30,15,7,1 : Hangi gün eşiklerinde hatırlatma yapılacak (virgülle)}';

    protected $description = 'Süresi dolmak üzere olan domainler için müşterilere otomatik yenileme hatırlatması (mail + SMS)';

    /** KDV oranı (yüzde) */
    private const KDV_ORANI = 20;

    /** uzanti (nokta dahil, ör: .com.tr) => net yenileme fiyatı. domain_fiyatlar tablosundan doldurulur. */
    private array $uzantiFiyat = [];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $esikler = array_map('intval', array_filter(explode(',', (string) $this->option('esikler'))));
        if (empty($esikler)) $esikler = [30, 15, 7, 1];

        // Güncel domain yenileme fiyatlarını yükle
        $this->uzantiFiyatlariYukle();

        $bugun = Carbon::today();
        $toplamGonderildi = 0;
        $toplamAtlandi = 0;
        $toplamHata = 0;

        foreach ($esikler as $kalan) {
            $hedefTarih = $bugun->copy()->addDays($kalan)->toDateString();

            // Üyelerde telefon sütunu hangisiyse onu kullan (gsm / telefon / tel / cep)
            static $telSutun = null;
            if ($telSutun === null) {
                $uCols = \Illuminate\Support\Facades\Schema::getColumnListing('uyeler');
                foreach (['gsm', 'telefon', 'tel', 'cep'] as $aday) {
                    if (in_array($aday, $uCols, true)) { $telSutun = $aday; break; }
                }
                $telSutun = $telSutun ?: false;
            }
            $telSec = $telSutun ? "u.{$telSutun} as telefon" : DB::raw('NULL as telefon');

            // 1) Online siparişler (ResellerClub)
            $domainler = DB::table('domain_orders as d')
                ->leftJoin('uyeler as u', 'u.id', '=', 'd.user_id')
                ->whereDate('d.expires_at', $hedefTarih)
                ->whereIn('d.status', ['active', 'registered'])
                ->whereNotNull('u.email')
                ->where('u.email', '!=', '')
                ->select(
                    'd.id', 'd.domain', 'd.expires_at', 'd.years',
                    'u.email', 'u.ad', 'u.soyad', 'u.id as uye_id',
                    DB::raw("'order' as kaynak")
                )
                ->addSelect(is_string($telSec) ? DB::raw($telSec) : $telSec)
                ->get();

            // 2) Manuel domainler (satilanlar, tipi=3) — Excel'den aktarılanlar dahil
            if (\Illuminate\Support\Facades\Schema::hasTable('satilanlar')) {
                $sCols = \Illuminate\Support\Facades\Schema::getColumnListing('satilanlar');
                $bitisSutun = in_array('bitis_tarih', $sCols, true) ? 'bitis_tarih'
                            : (in_array('bitis_tarihi', $sCols, true) ? 'bitis_tarihi' : null);
                if ($bitisSutun) {
                    $manuel = DB::table('satilanlar as s')
                        ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                        ->where('s.tipi', '3')
                        ->where('s.durum', 1)
                        ->whereDate('s.' . $bitisSutun, $hedefTarih)
                        ->whereNotNull('u.email')
                        ->where('u.email', '!=', '')
                        ->select(
                            's.id', 's.domain',
                            DB::raw('s.' . $bitisSutun . ' as expires_at'),
                            DB::raw('1 as years'),
                            'u.email', 'u.ad', 'u.soyad', 'u.id as uye_id',
                            DB::raw("'manuel' as kaynak")
                        )
                        ->addSelect(is_string($telSec) ? DB::raw($telSec) : $telSec)
                        ->get();
                    $domainler = $domainler->concat($manuel);
                }
            }

            $this->info("[{$kalan} gün kala] {$domainler->count()} domain bulundu (bitiş: {$hedefTarih})");

            foreach ($domainler as $d) {
                $zaten = DB::table('domain_yenileme_log')
                    ->where('domain_order_id', $d->id)
                    ->where('kaynak', $d->kaynak ?? 'order')
                    ->where('kalan_gun', $kalan)
                    ->where('gonderim_tarihi', $bugun->toDateString())
                    ->exists();

                if ($zaten) { $toplamAtlandi++; continue; }

                $subject = $this->subject($kalan, $d);
                $body    = $this->body($kalan, $d);

                if ($dry) {
                    $net = $this->guncelFiyat($d->domain);
                    $fiyatStr = $net !== null ? number_format($net, 2, ',', '.') . ' TL net' : 'fiyat yok (gizlendi)';
                    $this->line("  [DRY] → {$d->email} | {$d->domain} | {$fiyatStr} | {$subject}");
                    $toplamGonderildi++;
                    continue;
                }

                try {
                    $ok = EmailNotificationService::send($d->email, $subject, $body);

                    // SMS hatırlatma (telefon varsa) — fiyat varsa SMS'e de eklenir
                    if (!empty($d->telefon)) {
                        $bitisStr = Carbon::parse($d->expires_at)->format('d.m.Y');
                        $net = $this->guncelFiyat($d->domain);
                        $fiyatCumle = '';
                        if ($net !== null && $net > 0) {
                            $brut = $net * (1 + self::KDV_ORANI / 100);
                            $fiyatCumle = ' Yenileme bedeli: ' . number_format($net, 2, ',', '.') . ' TL + KDV ('
                                . number_format($brut, 2, ',', '.') . ' TL).';
                        }
                        $smsOk = RandevuSms::gonder($d->telefon,
                            'Sayin ' . trim(($d->ad ?? '') . ' ' . ($d->soyad ?? '')) . ', ' . $d->domain
                            . ' alan adinizin suresi ' . $kalan . ' gun sonra (' . $bitisStr . ') doluyor.'
                            . $fiyatCumle
                            . ' Yenileme icin bizimle iletisime gecebilirsiniz.');
                        if ($smsOk) $this->line("    ↳ SMS gönderildi: {$d->telefon}");
                    }

                    DB::table('domain_yenileme_log')->insert([
                        'domain_order_id' => $d->id,
                        'kaynak'          => $d->kaynak ?? 'order',
                        'email'           => $d->email,
                        'kalan_gun'       => $kalan,
                        'gonderim_tarihi' => $bugun->toDateString(),
                        'durum'           => $ok ? 'ok' : 'fail',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    if ($ok) {
                        $toplamGonderildi++;
                        $this->info("  ✓ {$d->email} ({$d->domain})");
                    } else {
                        $toplamHata++;
                        $this->error("  ✗ {$d->email} ({$d->domain})");
                    }
                } catch (\Throwable $e) {
                    $toplamHata++;
                    DB::table('domain_yenileme_log')->insertOrIgnore([
                        'domain_order_id' => $d->id,
                        'kaynak'          => $d->kaynak ?? 'order',
                        'email'           => $d->email,
                        'kalan_gun'       => $kalan,
                        'gonderim_tarihi' => $bugun->toDateString(),
                        'durum'           => 'fail',
                        'hata'            => $e->getMessage(),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    $this->error("  ✗ {$d->email} ({$d->domain}): " . $e->getMessage());
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

    /** domain_fiyatlar tablosundan uzantı -> net yenileme fiyatı yükler. */
    private function uzantiFiyatlariYukle(): void
    {
        try {
            $rows = DB::table('domain_fiyatlar')
                ->where('durum', 1)
                ->select('uzanti', 'yenileme_fiyat')
                ->get();
            foreach ($rows as $r) {
                $u = $this->normalizeUzanti($r->uzanti);
                if ($u !== '') {
                    $this->uzantiFiyat[$u] = (float) $r->yenileme_fiyat;
                }
            }
        } catch (\Throwable $e) {
            $this->uzantiFiyat = [];
        }
    }

    /** Uzantıyı baştaki nokta ve boşluklardan arındırıp küçük harfe çevirir (com.tr gibi). */
    private function normalizeUzanti(?string $u): string
    {
        $u = mb_strtolower(trim((string) $u), 'UTF-8');
        $u = ltrim($u, '.');
        return $u;
    }

    /**
     * Bir domainin uzantısına göre güncel net yenileme fiyatını döndürür.
     * Çok parçalı uzantılar (com.tr, org.tr) önce denenir, sonra tek parçalı (com).
     */
    private function guncelFiyat(?string $domain): ?float
    {
        $domain = mb_strtolower(trim((string) $domain), 'UTF-8');
        if ($domain === '') return null;

        // Olası path/protokol temizliği
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = explode('/', $domain)[0];

        $parcalar = explode('.', $domain);
        if (count($parcalar) < 2) return null;

        // En uzun uzantıdan başlayarak eşleşme dene: ör. a.b.com.tr -> com.tr, sonra tr
        for ($i = 1; $i < count($parcalar); $i++) {
            $uzanti = implode('.', array_slice($parcalar, $i));
            if (isset($this->uzantiFiyat[$uzanti])) {
                return $this->uzantiFiyat[$uzanti];
            }
        }
        return null;
    }

    private function subject(int $kalan, $d): string
    {
        if ($kalan <= 7) return "⚠️ Acil: {$d->domain} domain'i {$kalan} gün içinde sona eriyor!";
        return "Domain Yenileme Hatırlatması: {$d->domain} — {$kalan} gün kaldı";
    }

    private function body(int $kalan, $d): string
    {
        $ad = trim(($d->ad ?? '') . ' ' . ($d->soyad ?? '')) ?: 'Değerli Müşterimiz';
        $bitis = Carbon::parse($d->expires_at)->format('d.m.Y');
        $domain = htmlspecialchars($d->domain);

        // Güncel domain yenileme fiyatı + %20 KDV. Eşleşme yoksa tutar satırı gizlenir.
        $net = $this->guncelFiyat($d->domain);
        $tutarSatiri = '';
        if ($net !== null && $net > 0) {
            $kdvTutar = $net * self::KDV_ORANI / 100;
            $brut = $net + $kdvTutar;
            $netStr  = number_format($net, 2, ',', '.');
            $brutStr = number_format($brut, 2, ',', '.');
            $tutarSatiri = <<<ROW
<tr><td style="padding:10px;border-bottom:1px solid #eee;color:#64748b">💰 Yenileme Bedeli</td><td style="padding:10px;border-bottom:1px solid #eee;font-weight:700">{$netStr} TL + %20 KDV = <span style="color:#0f172a">{$brutStr} TL</span></td></tr>
ROW;
        }

        $uyariRenk = $kalan <= 7 ? '#dc2626' : ($kalan <= 15 ? '#d97706' : '#2563eb');
        $uyariMesaj = $kalan <= 7
            ? "<p style=\"color:{$uyariRenk};font-weight:700;font-size:16px\">⚠️ ACİL! Domain'iniz yalnızca {$kalan} gün içinde sona eriyor. Lütfen hemen yenileyin!</p>"
            : "<p>Domain'inizin süresi <strong style=\"color:{$uyariRenk}\">{$kalan} gün</strong> içinde dolacak.</p>";

        return <<<HTML
<div style="font-family:Inter,system-ui,sans-serif;max-width:600px;margin:auto;padding:24px;background:#fafafa">
<div style="background:#fff;border-radius:12px;padding:30px;border-top:4px solid {$uyariRenk}">
<h2 style="margin:0 0 12px;color:#0f172a">Merhaba {$ad},</h2>
{$uyariMesaj}
<table style="width:100%;border-collapse:collapse;margin:18px 0">
<tr><td style="padding:10px;border-bottom:1px solid #eee;color:#64748b">🌐 Domain</td><td style="padding:10px;border-bottom:1px solid #eee;font-weight:700;font-size:16px">{$domain}</td></tr>
{$tutarSatiri}
<tr><td style="padding:10px;border-bottom:1px solid #eee;color:#64748b">📅 Son Kullanım Tarihi</td><td style="padding:10px;border-bottom:1px solid #eee;font-weight:700;color:{$uyariRenk}">{$bitis}</td></tr>
<tr><td style="padding:10px;color:#64748b">⏳ Kalan Süre</td><td style="padding:10px;font-weight:700;color:{$uyariRenk}">{$kalan} gün</td></tr>
</table>
<p style="color:#475569;font-size:14px">Domain'inizi zamanında yenilemezseniz, başkaları tarafından alınabilir. Yenileme işleminiz için hesabınıza giriş yapın veya bizimle iletişime geçin.</p>
<div style="margin:24px 0;padding:16px;background:#fef3c7;border-radius:8px;border-left:4px solid #f59e0b">
<p style="margin:0;font-size:13px;color:#92400e"><strong>Not:</strong> Süre dolduktan sonra domain'iniz bir süre daha kurtarılabilir, ancak ek ücret ödemeniz gerekebilir.</p>
</div>
<p style="color:#94a3b8;font-size:12px;margin-top:24px">Bu otomatik bir hatırlatma e-postasıdır. Zaten yenileme yaptıysanız bu maili dikkate almayınız.</p>
</div>
</div>
HTML;
    }
}