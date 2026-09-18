<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Excel'deki domain listesini Domain Takip paneline (satilanlar, tipi=3) aktarır.
 * Müşteri eşleştirme: üye ad+soyad / firma adı / CRM müşteri adı (normalize edilmiş).
 * Tekrar çalıştırılabilir: mevcut domainleri atlar.
 *
 * Önce deneme:  php artisan domain:excel-aktar --dry
 * Gerçek aktarım: php artisan domain:excel-aktar
 */
class DomainExcelAktar extends Command
{
    protected $signature   = 'domain:excel-aktar {--dry : Sadece raporla, kayıt ekleme}';
    protected $description = 'DN Kreatif Domain Takip Excel listesini panele aktarır';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $veri = $this->veri();
        $this->info(count($veri) . ' satır işlenecek' . ($dry ? ' [DENEME MODU]' : ''));

        $cols = Schema::getColumnListing('satilanlar');
        $has  = fn ($c) => in_array($c, $cols, true);

        // ── Müşteri eşleştirme haritaları ──
        $norm = function (?string $s): string {
            $s = trim((string) $s);
            $s = str_replace(['İ', 'I'], ['i', 'ı'], $s);
            $s = mb_strtolower($s, 'UTF-8');
            return preg_replace('/\s+/u', ' ', $s);
        };

        $uyeAd = []; $uyeFirma = []; $crmAd = []; $orijinal = [];
        foreach (DB::table('uyeler')->select('id', 'ad', 'soyad', 'firmaadi')->get() as $u) {
            $oad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
            $k = $norm($oad);
            if ($k !== '' && !isset($uyeAd[$k])) { $uyeAd[$k] = $u->id; $orijinal[$k] = $oad; }
            $f = $norm($u->firmaadi ?? '');
            if ($f !== '' && !isset($uyeFirma[$f])) { $uyeFirma[$f] = $u->id; $orijinal[$f] = trim($u->firmaadi); }
        }
        if (Schema::hasTable('crm_customers')) {
            foreach (DB::table('crm_customers')->select('id', 'adi')->get() as $c) {
                $k = $norm($c->adi ?? '');
                if ($k !== '' && !isset($crmAd[$k])) { $crmAd[$k] = $c->id; $orijinal[$k] = trim($c->adi); }
            }
        }

        // ── Benzer isim (fuzzy) eşleştirici: tüm aday adlar tek havuzda ──
        $havuz = $crmAd + $uyeAd + $uyeFirma; // norm ad => id (öncelik CRM)
        $genel = ['bey', 'hanım', 'hanim', 'otel', 'hotel', 'grup', 'group', 'turizm', 'ltd', 'şti', 'sti'];
        $tokenlar = function (string $normAd) use ($genel): array {
            return array_values(array_diff(array_filter(explode(' ', $normAd)), $genel));
        };
        $havuzToken = [];
        foreach ($havuz as $nk => $hid) $havuzToken[$nk] = $tokenlar($nk);

        $fuzzy = function (string $mk) use ($havuz, $havuzToken, $tokenlar): ?string {
            $tk = $tokenlar($mk);
            if (empty($tk)) return null;
            // Kural A: Excel adındaki tüm (genel olmayan) kelimeler aday adda geçen TEK aday
            $adaylar = [];
            foreach ($havuzToken as $nk => $ht) {
                if (empty(array_diff($tk, $ht))) $adaylar[] = $nk;
                if (count($adaylar) > 1) break;
            }
            if (count($adaylar) === 1) return $adaylar[0];
            // Kural B: son kelime (soyad) tam eşleşen TEK aday
            $soyad = end($tk);
            if ($soyad && count($tk) >= 2) {
                $adaylar = [];
                foreach ($havuzToken as $nk => $ht) {
                    if (in_array($soyad, $ht, true)) $adaylar[] = $nk;
                    if (count($adaylar) > 1) break;
                }
                if (count($adaylar) === 1) return $adaylar[0];
            }
            return null;
        };
        $tahmini = []; // raporlama: Excel adı ≈ bulunan ad

        // Benzer isim eşleşmesi YASAK olanlar (Nurseli Hanım onayı, 04.06.2026:
        // CRM'deki benzer isimler farklı kişiler — bunlar yeni müşteri olarak eklenecek)
        $fuzzyHaric = ['deniz çevik', 'melih eriş', 'nail bey'];

        // ── Mevcut domainler (mükerrer engeli) ──
        $mevcut = DB::table('satilanlar')->where('tipi', '3')->pluck('domain')
            ->map(fn ($d) => mb_strtolower(trim((string) $d)))->flip()->all();
        if (Schema::hasTable('domain_orders')) {
            foreach (DB::table('domain_orders')->pluck('domain') as $d) {
                $mevcut[mb_strtolower(trim((string) $d))] = true;
            }
        }

        $eklendi = 0; $atlandi = 0; $eslesmedi = []; $bagliIdler = [];

        foreach ($veri as [$domain, $musteri, $bitis]) {
            $domain = mb_strtolower(trim($domain));
            if (isset($mevcut[$domain])) { $atlandi++; continue; }

            $mk = $norm($musteri);
            $uyeId = $uyeAd[$mk] ?? $crmAd[$mk] ?? $uyeFirma[$mk] ?? null;
            if (!$uyeId && !in_array($mk, $fuzzyHaric, true)) {
                // Benzer isim dene (tek aday kuralı — güvenli)
                $benzer = $fuzzy($mk);
                if ($benzer !== null) {
                    $uyeId = $havuz[$benzer];
                    $tahmini[$musteri] = ($orijinal[$benzer] ?? $benzer) . ' (#' . $uyeId . ')';
                }
            }
            if (!$uyeId) {
                $eslesmedi[$musteri][] = $domain;
                continue;
            }
            $bagliIdler[$uyeId] = true;

            if ($dry) { $this->line("  [DRY] {$domain} → üye #{$uyeId} ({$musteri}) bitiş {$bitis}"); $eklendi++; $mevcut[$domain] = true; continue; }

            $payload = [
                'uyeid'  => $uyeId,
                'tipi'   => '3',
                'domain' => $domain,
                'durum'  => 1,
            ];
            $baslangic = Carbon::parse($bitis)->subYear()->toDateString();
            if ($has('tutar'))           $payload['tutar']           = 0;
            if ($has('baslangic_tarih')) $payload['baslangic_tarih'] = $baslangic;
            if ($has('bitis_tarih'))     $payload['bitis_tarih']     = $bitis;
            if ($has('tarih'))           $payload['tarih']           = now();
            if ($has('mesaj'))           $payload['mesaj']           = 'Excel aktarımı (Haziran 2026)';
            if ($has('domain_durum'))    $payload['domain_durum']    = 'kayitli';
            if ($has('created_at'))      $payload['created_at']      = now();
            if ($has('updated_at'))      $payload['updated_at']      = now();

            DB::table('satilanlar')->insert($payload);
            $eklendi++;
            $mevcut[$domain] = true;
        }

        $this->info("\n========= ÖZET =========");
        $this->info("✓ Eklendi  : {$eklendi}");
        $this->info("↻ Atlandı  : {$atlandi} (zaten kayıtlı)");

        if (count($tahmini)) {
            $this->info("\n≈ BENZER İSİMLE EŞLENENLER (" . count($tahmini) . ") — kontrol et, yanlış varsa söyle:");
            foreach ($tahmini as $excelAd => $bulunan) {
                $this->line("  ≈ {$excelAd}  →  {$bulunan}");
            }
        }

        $this->info("\n✗ Eşleşmeyen müşteri: " . count($eslesmedi));
        foreach ($eslesmedi as $m => $ds) {
            $this->warn("  • {$m} → " . implode(', ', $ds));
        }
        if (count($eslesmedi)) {
            $this->line("  Bunlar CRM'de yok: CRM > Yeni Müşteri ile (e-posta + telefon girerek) ekleyin, sonra komutu tekrar çalıştırın.");
        }

        // ── İletişim denetimi: domain bağlanan ama mail/tel bilgisi olmayan müşteriler ──
        if (!empty($bagliIdler)) {
            $idler = array_keys($bagliIdler);
            $eksikler = [];
            try {
                foreach (DB::table('crm_customers')->whereIn('id', $idler)
                    ->get(['id', 'adi', 'email', 'telefon', 'gsm']) as $c) {
                    $epostaVar = trim((string) $c->email) !== '';
                    $telVar = trim((string) $c->gsm) !== '' || trim((string) $c->telefon) !== '';
                    if (!$epostaVar || !$telVar) {
                        $eksikler[] = $c->adi . ' (#' . $c->id . ')'
                            . (!$epostaVar ? ' — e-posta YOK' : '')
                            . (!$telVar ? ' — telefon YOK' : '');
                    }
                }
            } catch (\Throwable $e) {}
            if ($eksikler) {
                $this->warn("\n⚠ İLETİŞİM BİLGİSİ EKSİK (hatırlatma gidemez, muhasebe tamamlasın):");
                foreach ($eksikler as $e) $this->warn('  • ' . $e);
            }
        }

        return self::SUCCESS;
    }

    /** [domain, müşteri adı, bitiş tarihi] */
    private function veri(): array
    {
        return [
            // NOT: Gercek musteri listesi bu acik depodan cikarilmistir.
            // Bicim korunmustur; calistirmak icin kendi verinizi girin.
            ['ornek-otel.com', 'Ornek Musteri', '2027-01-15'],
            ['ornek-restoran.com', 'Ornek Musteri', '2027-03-02'],
            ['ornek-hukuk.com.tr', 'Ikinci Musteri', '2026-11-20'],
            ['ornek-insaat.com', 'Ucuncu Musteri', '2027-06-08'],
        ];
    }
}