<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * OTOMATİK PROFORMA FATURA — bitişine 5-30 gün kalan hizmetler için.
 *
 * Görev #217/10: "5-30 Gün Kala Proforma Fatura Kessin (Bekleyen Faturalar
 * Kısmına Düşsün)". Oluşan fatura durum=0 (bekliyor) olduğu için doğrudan
 * "Bekleyen Faturalar" ekranında görünür.
 *
 * Mükerrer engeli: her kayıt için faturalar.spno alanına PRF-<tip>-<id>-<yil>
 * damgası yazılır; aynı hizmet için aynı yıl ikinci proforma kesilmez.
 */
class ProformaUret extends Command
{
    protected $signature = 'proforma:uret
                            {--min=5 : En az kaç gün kalmışsa}
                            {--max=30 : En fazla kaç gün kalmışsa}
                            {--kuru-calisma : Sadece göster, fatura kesme}';

    protected $description = 'Bitişine 5-30 gün kalan hizmetler için proforma fatura oluşturur';

    /** tipi => okunabilir ad */
    private const TIPLER = [
        '3' => 'Alan Adı',
        '2' => 'Hosting',
        '1' => 'Hizmet',
    ];

    /**
     * Yenileme fiyatını bulur.
     * Domain ise uzantısına göre domain_fiyatlar.yenileme_fiyat kullanılır
     * (ör. "ornek.com.tr" → ".com.tr"). Hosting/hizmette fiyat tablosu yok.
     */
    private function yenilemeFiyati(string $tipi, ?string $domain): float
    {
        if ($tipi !== '3' || empty($domain) || !Schema::hasTable('domain_fiyatlar')) {
            return 0.0;
        }

        $domain = mb_strtolower(trim($domain));
        $parcalar = explode('.', $domain);
        if (count($parcalar) < 2) return 0.0;

        // Önce uzun uzantı (.com.tr), bulunamazsa kısa (.com)
        $adaylar = [];
        if (count($parcalar) >= 3) {
            $adaylar[] = '.' . implode('.', array_slice($parcalar, -2));
        }
        $adaylar[] = '.' . end($parcalar);

        foreach ($adaylar as $uzanti) {
            try {
                $fiyat = DB::table('domain_fiyatlar')
                    ->where('uzanti', $uzanti)->where('durum', 1)
                    ->value('yenileme_fiyat');
                if ($fiyat && (float) $fiyat > 0) {
                    return (float) $fiyat;
                }
            } catch (\Throwable $e) {}
        }

        return 0.0;
    }

    public function handle(): int
    {
        if (!Schema::hasTable('satilanlar') || !Schema::hasTable('faturalar')) {
            $this->warn('Gerekli tablolar yok, çıkılıyor.');
            return self::SUCCESS;
        }

        $min = max(0, (int) $this->option('min'));
        $max = max($min, (int) $this->option('max'));
        $kuru = (bool) $this->option('kuru-calisma');

        $bas = Carbon::today()->addDays($min)->toDateString();
        $son = Carbon::today()->addDays($max)->toDateString();

        try {
            // DİKKAT: uyeler ile INNER JOIN şart. Bekleyen Faturalar ekranı da
            // uyeler'e inner join yapıyor; üye kaydı olmayan (yetim) hizmete
            // proforma kesilirse fatura oluşur ama ekranda GÖRÜNMEZ.
            $liste = DB::table('satilanlar')
                ->join('uyeler', 'satilanlar.uyeid', '=', 'uyeler.id')
                ->whereTarihBetween('satilanlar.bitis_tarih', $bas, $son)
                ->whereNotNull('satilanlar.uyeid')->where('satilanlar.uyeid', '>', 0)
                ->select(
                    'satilanlar.id', 'satilanlar.uyeid', 'satilanlar.tipi', 'satilanlar.tutar',
                    'satilanlar.domain', 'satilanlar.paket_adi', 'satilanlar.hosting_baslik',
                    'satilanlar.bitis_tarih', 'uyeler.email', 'uyeler.ad', 'uyeler.soyad'
                )
                ->orderBy('satilanlar.bitis_tarih')
                ->limit(300)->get();
        } catch (\Throwable $e) {
            Log::error('proforma:uret sorgu', ['e' => $e->getMessage()]);
            return self::FAILURE;
        }

        if ($liste->isEmpty()) {
            $this->info("Belirtilen aralıkta ({$min}-{$max} gün) bitecek hizmet yok.");
            return self::SUCCESS;
        }

        $kesilen = 0;
        $atlanan = 0;

        foreach ($liste as $h) {
            $tipAd = self::TIPLER[(string) $h->tipi] ?? 'Hizmet';
            $yil   = Carbon::parse($h->bitis_tarih)->year;
            $damga = 'PRF-' . $h->tipi . '-' . $h->id . '-' . $yil;

            // Aynı hizmet + aynı yıl için proforma zaten var mı?
            if (DB::table('faturalar')->where('spno', $damga)->exists()) {
                $atlanan++;
                continue;
            }

            $ad = trim(($h->ad ?? '') . ' ' . ($h->soyad ?? '')) ?: 'Müşteri';
            $konu = $h->domain ?: ($h->paket_adi ?: ($h->hosting_baslik ?: $tipAd));
            $kalan = (int) Carbon::today()->diffInDays(Carbon::parse($h->bitis_tarih), false);

            // Satış tutarı 0 ise (221 domain kaydında öyle) yenileme fiyatından hesapla
            $tutar = (float) ($h->tutar ?: 0);
            if ($tutar <= 0) {
                $tutar = $this->yenilemeFiyati((string) $h->tipi, $h->domain);
            }

            // Fiyatı bulunamayanı atla — 0 TL proforma kesmek işe yaramaz
            if ($tutar <= 0) {
                $atlanan++;
                if ($kuru) {
                    $this->line(sprintf('  [atlandi] %-26s fiyat bulunamadi', mb_substr($konu, 0, 26)));
                }
                continue;
            }

            if ($kuru) {
                $this->line(sprintf('  [kuru] %-28s %-10s %s gün  %s TL  → %s',
                    mb_substr($konu, 0, 28), $tipAd, $kalan, number_format($tutar, 2), $ad));
                continue;
            }

            try {
                $faturaNo = 'PRF-' . date('Y') . '-' . str_pad((string) $h->id, 5, '0', STR_PAD_LEFT);

                DB::table('faturalar')->insert([
                    'fatura_no'     => $faturaNo,
                    'uyeid'         => $h->uyeid,
                    'mail'          => $h->email,
                    'baslik'        => $tipAd . ' yenileme — ' . $konu,
                    'aciklama'      => $tipAd . ' hizmetinizin bitiş tarihi '
                                       . Carbon::parse($h->bitis_tarih)->format('d.m.Y')
                                       . '. Yenileme için hazırlanan proforma faturadır.',
                    'tutar'         => $tutar,
                    'toplam'        => $tutar,
                    'durum'         => 0,                 // bekliyor → Bekleyen Faturalar'da görünür
                    'tip'           => 'proforma',
                    'spno'          => $damga,            // mükerrer engeli
                    'hizmet'        => $h->id,
                    'tarih'         => now(),
                    'bitis_tarih'   => $h->bitis_tarih,   // son ödeme tarihi = hizmet bitişi
                    'odeme_yontemi' => 'havale',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $kesilen++;
                $this->line(sprintf('  ✓ %-28s %-10s %2s gün  %10s TL  %s',
                    mb_substr($konu, 0, 28), $tipAd, $kalan, number_format($tutar, 2), $faturaNo));
            } catch (\Throwable $e) {
                Log::warning('Proforma kesilemedi', ['satilan' => $h->id, 'hata' => $e->getMessage()]);
            }
        }

        Log::info('Proforma üretimi tamamlandı', [
            'aralik' => $min . '-' . $max . ' gün', 'kesilen' => $kesilen, 'atlanan' => $atlanan,
        ]);

        $this->info(($kuru ? '[KURU ÇALIŞMA] ' : '') . $kesilen . ' proforma kesildi, '
            . $atlanan . ' tanesi zaten vardı (toplam ' . $liste->count() . ' hizmet incelendi).');

        return self::SUCCESS;
    }
}
