<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HizmetBitisHatirlatCommand extends Command
{
    protected $signature = 'mail:hizmet-bitis-hatirlat
        {--dry : Sadece raporla, mail gönderme}
        {--esikler=30,15,7 : Hangi gün eşiklerinde hatırlatma yapılacak (virgülle)}
        {--tipler=1,2 : Hangi satış tipleri (1=hizmet, 2=hosting)}';

    protected $description = 'Bitiş tarihi yaklaşan hizmet ve hosting kayıtları için müşterilere otomatik mail';

    /** KDV oranı (yüzde) */
    private const KDV_ORANI = 20;

    private array $tipAdi = [
        '1' => 'Hizmet',
        '2' => 'Hosting',
    ];

    /** Paket adı => güncel yenileme fiyatı (KDV hariç). hosting_paketler tablosundan doldurulur. */
    private array $paketFiyat = [];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $esikler = array_map('intval', array_filter(explode(',', (string) $this->option('esikler'))));
        $tipler = array_filter(explode(',', (string) $this->option('tipler')));
        if (empty($esikler)) $esikler = [30, 15, 7];
        if (empty($tipler)) $tipler = ['1', '2'];

        // Güncel paket fiyatlarını hosting_paketler tablosundan yükle (paket adı -> net fiyat)
        $this->paketFiyatlariYukle();

        $bugun = Carbon::today();
        $toplamGonderildi = 0;
        $toplamAtlandi = 0;
        $toplamHata = 0;

        foreach ($esikler as $kalan) {
            $hedefTarih = $bugun->copy()->addDays($kalan)->toDateString();

            $kayitlar = DB::table('satilanlar as s')
                ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                ->whereIn('s.tipi', $tipler)
                ->whereDate('s.bitis_tarih', $hedefTarih)
                ->where('s.durum', 1)
                ->whereNotNull('u.email')
                ->where('u.email', '!=', '')
                ->select(
                    's.id', 's.tipi', 's.paket_baslik', 's.hosting_baslik', 's.bitis_tarih', 's.tutar',
                    'u.email', 'u.ad', 'u.soyad', 'u.id as uye_id'
                )
                ->get();

            $this->info("[{$kalan} gün kala] {$kayitlar->count()} kayıt bulundu (bitiş: {$hedefTarih})");

            foreach ($kayitlar as $k) {
                $zaten = DB::table('hizmet_bitis_log')
                    ->where('satilanlar_id', $k->id)
                    ->where('kalan_gun', $kalan)
                    ->where('gonderim_tarihi', $bugun->toDateString())
                    ->exists();

                if ($zaten) { $toplamAtlandi++; continue; }

                $subject = $this->subject($kalan, $k);
                $body    = $this->body($kalan, $k);

                if ($dry) {
                    $baslik = $k->paket_baslik ?: $k->hosting_baslik;
                    $net = $this->guncelFiyat($k);
                    $fiyatStr = $net !== null ? number_format($net, 2, ',', '.') . ' TL net' : 'fiyat yok (gizlendi)';
                    $this->line("  [DRY] → {$k->email} | {$baslik} | {$fiyatStr} | {$subject}");
                    $toplamGonderildi++;
                    continue;
                }

                try {
                    $ok = EmailNotificationService::send($k->email, $subject, $body);
                    DB::table('hizmet_bitis_log')->insert([
                        'satilanlar_id'  => $k->id,
                        'email'          => $k->email,
                        'kalan_gun'      => $kalan,
                        'gonderim_tarihi' => $bugun->toDateString(),
                        'durum'          => $ok ? 'ok' : 'fail',
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                    if ($ok) {
                        $toplamGonderildi++;
                        $this->info("  ✓ {$k->email}");
                    } else {
                        $toplamHata++;
                        $this->error("  ✗ {$k->email}");
                    }
                } catch (\Throwable $e) {
                    $toplamHata++;
                    DB::table('hizmet_bitis_log')->insertOrIgnore([
                        'satilanlar_id'  => $k->id,
                        'email'          => $k->email,
                        'kalan_gun'      => $kalan,
                        'gonderim_tarihi' => $bugun->toDateString(),
                        'durum'          => 'fail',
                        'hata'           => $e->getMessage(),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                    $this->error("  ✗ {$k->email}: " . $e->getMessage());
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

    /**
     * hosting_paketler tablosundan güncel paket fiyatlarını yükler (paket adı -> net fiyat).
     * Eşleşme isim üzerinden (TR normalize) yapılır.
     */
    private function paketFiyatlariYukle(): void
    {
        try {
            $rows = DB::table('hosting_paketler')
                ->where('durum', 1)
                ->select('adi', 'fiyat')
                ->get();
            foreach ($rows as $r) {
                $key = $this->normalize($r->adi);
                if ($key !== '') {
                    $this->paketFiyat[$key] = (float) $r->fiyat;
                }
            }
        } catch (\Throwable $e) {
            // Tablo yoksa fiyat eşleşmesi olmaz; tutar satırı gizlenir.
            $this->paketFiyat = [];
        }
    }

    /** Bir satış kaydı için güncel net yenileme fiyatını döndürür; eşleşme yoksa null. */
    private function guncelFiyat($k): ?float
    {
        $aday = $k->paket_baslik ?: $k->hosting_baslik;
        if (!$aday) return null;
        $key = $this->normalize($aday);
        return $this->paketFiyat[$key] ?? null;
    }

    private function normalize(?string $s): string
    {
        $s = (string) $s;
        $s = mb_strtolower(trim($s), 'UTF-8');
        $tr = ['ı'=>'i','İ'=>'i','ş'=>'s','Ş'=>'s','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c'];
        $s = strtr($s, $tr);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function subject(int $kalan, $k): string
    {
        $tipiStr = $this->tipAdi[$k->tipi] ?? 'Hizmet';
        $baslik = $k->paket_baslik ?: $k->hosting_baslik ?: $tipiStr;
        if ($kalan <= 7) return "⚠️ Acil: {$baslik} — {$kalan} gün içinde sona eriyor!";
        return "{$tipiStr} Yenileme Hatırlatması: {$baslik} — {$kalan} gün kaldı";
    }

    private function body(int $kalan, $k): string
    {
        $ad = trim(($k->ad ?? '') . ' ' . ($k->soyad ?? '')) ?: 'Değerli Müşterimiz';
        $bitis = Carbon::parse($k->bitis_tarih)->format('d.m.Y');
        $baslik = htmlspecialchars($k->paket_baslik ?: $k->hosting_baslik ?: '—');
        $tipiStr = $this->tipAdi[$k->tipi] ?? 'Hizmet';

        // Güncel fiyatı hosting_paketler'den çek; +%20 KDV. Eşleşme yoksa tutar satırı gizlenir.
        $net = $this->guncelFiyat($k);
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
            ? "<p style=\"color:{$uyariRenk};font-weight:700;font-size:16px\">⚠️ ACİL! {$tipiStr} hizmetiniz yalnızca {$kalan} gün içinde sona eriyor. Lütfen hemen yenileyin!</p>"
            : "<p>{$tipiStr} hizmetinizin süresi <strong style=\"color:{$uyariRenk}\">{$kalan} gün</strong> içinde dolacak.</p>";

        return <<<HTML
<div style="font-family:Inter,system-ui,sans-serif;max-width:600px;margin:auto;padding:24px;background:#fafafa">
<div style="background:#fff;border-radius:12px;padding:30px;border-top:4px solid {$uyariRenk}">
<h2 style="margin:0 0 12px;color:#0f172a">Merhaba {$ad},</h2>
{$uyariMesaj}
<table style="width:100%;border-collapse:collapse;margin:18px 0">
<tr><td style="padding:10px;border-bottom:1px solid #eee;color:#64748b">📦 Hizmet</td><td style="padding:10px;border-bottom:1px solid #eee;font-weight:700">{$baslik}</td></tr>
<tr><td style="padding:10px;border-bottom:1px solid #eee;color:#64748b">🏷️ Tür</td><td style="padding:10px;border-bottom:1px solid #eee">{$tipiStr}</td></tr>
{$tutarSatiri}
<tr><td style="padding:10px;border-bottom:1px solid #eee;color:#64748b">📅 Bitiş Tarihi</td><td style="padding:10px;border-bottom:1px solid #eee;font-weight:700;color:{$uyariRenk}">{$bitis}</td></tr>
<tr><td style="padding:10px;color:#64748b">⏳ Kalan Süre</td><td style="padding:10px;font-weight:700;color:{$uyariRenk}">{$kalan} gün</td></tr>
</table>
<p style="color:#475569;font-size:14px">Hizmetinizin kesintisiz devam etmesi için lütfen yenileme talebinde bulunun veya bizimle iletişime geçin.</p>
<p style="color:#94a3b8;font-size:12px;margin-top:24px">Bu otomatik bir hatırlatma e-postasıdır. Zaten yenileme yaptıysanız bu maili dikkate almayınız.</p>
</div>
</div>
HTML;
    }
}