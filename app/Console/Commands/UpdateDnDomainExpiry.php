<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateDnDomainExpiry extends Command
{
    protected $signature = 'dn:update-domain-expiry {--dry : Sadece raporla, kayıt etme}';
    protected $description = 'Domain bitiş tarihlerini güncelle (mevcut domainleri UPDATE, eksik olanları INSERT)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $file = storage_path('app/dn_domain_expiry.txt');
        if (!is_file($file)) {
            $this->error("Dosya yok: $file");
            return 1;
        }

        $satirlar = array_filter(array_map('trim', file($file)));
        $guncellenen = $eklenen = $musteriYok = $atlanan = 0;
        $rapor = [];

        foreach ($satirlar as $satir) {
            // No \t Domain \t Müşteri \t Bitiş \t Kalan
            $parts = preg_split('/\t+/', $satir);
            if (count($parts) < 4) continue;

            $no       = trim($parts[0] ?? '');
            $domain   = strtolower(trim($parts[1] ?? ''));
            $musteri  = trim($parts[2] ?? '');
            $bitis    = trim($parts[3] ?? '');

            // www. prefix ve (Kurumsal Mail) gibi ekleri temizle
            $domain = preg_replace('/^www\./', '', $domain);
            $domain = preg_replace('/\s*\(.+?\)\s*$/', '', $domain);
            $domain = trim($domain);
            if (!$domain) continue;

            // Tarih formatla
            try { $bitisTarih = \Carbon\Carbon::parse($bitis)->toDateString(); }
            catch (\Throwable $e) { $bitisTarih = null; }

            // Müşteri ID'sini bul (ad ile case-insensitive)
            $musteriRow = DB::table('crm_customers')
                ->whereRaw('LOWER(adi) = ?', [mb_strtolower($musteri)])
                ->orderBy('id')
                ->first();

            $uyeId = 0;
            if ($musteriRow) {
                $uye = DB::table('uyeler')->whereRaw('LOWER(email) = ?', [strtolower($musteriRow->email ?? '')])->first();
                $uyeId = $uye?->id ?? 0;
            }

            // Mevcut satilanlar kaydı var mı?
            $mevcut = DB::table('satilanlar')
                ->where('tipi', '3')
                ->where('domain', $domain)
                ->orderBy('id')
                ->first();

            if ($mevcut) {
                if (!$dry) {
                    $update = ['bitis_tarih' => $bitisTarih];
                    if ($uyeId && (int) $mevcut->uyeid === 0) {
                        $update['uyeid'] = $uyeId;
                    }
                    DB::table('satilanlar')->where('id', $mevcut->id)->update($update);
                }
                $guncellenen++;
                $rapor[] = "↻ Güncel #{$mevcut->id}: {$domain} → {$bitisTarih}";
            } else {
                if (!$musteriRow) {
                    // Müşteri yoksa otomatik oluştur (domain bazlı placeholder email)
                    $placeholderEmail = 'noemail-' . preg_replace('/[^a-z0-9]/', '', strtolower($musteri)) . '@import.local';
                    if (!$dry) {
                        DB::table('crm_customers')->insert([
                            'adi'        => $musteri,
                            'email'      => $placeholderEmail,
                            'telefon'    => '',
                            'durum'      => 'aktif',
                            'kaynak'     => 'dn-domain-import',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $musteriRow = (object) ['id' => 0, 'email' => $placeholderEmail];
                    $musteriYok++;
                    $rapor[] = "+ Müşteri oluşturuldu: {$musteri}";
                }
                if (!$dry) {
                    DB::table('satilanlar')->insert([
                        'uyeid'           => $uyeId,
                        'tipi'            => '3',
                        'domain'          => $domain,
                        'tutar'           => 0,
                        'baslangic_tarih' => null,
                        'bitis_tarih'     => $bitisTarih,
                        'mesaj'           => 'DN domain takip #' . $no,
                        'durum'           => 1,
                        'tarih'           => now()->toDateTimeString(),
                    ]);
                }
                $eklenen++;
                $rapor[] = "✓ Yeni: {$domain} → {$bitisTarih} ({$musteri})";
            }
        }

        $this->info("\n========= ÖZET =========");
        $this->info("↻ Güncellenen domain : {$guncellenen}");
        $this->info("✓ Yeni eklenen       : {$eklenen}");
        $this->info("✗ Müşteri eşleşmedi  : {$musteriYok}");
        $this->info(($dry ? '[DRY-RUN]' : '[YAZILDI]') . ' Toplam satır: ' . count($satirlar));

        if ($this->getOutput()->isVerbose()) {
            foreach ($rapor as $r) $this->line($r);
        } else {
            $this->info('Detaylı için -v ile çalıştır');
        }
        return 0;
    }
}
