<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportDnDomainCustomers extends Command
{
    protected $signature = 'dn:import-domain-customers {--dry : Sadece raporla, kayıt etme}';
    protected $description = 'Eski DN Kreatif Domain Takip listesini crm_customers + satilanlar (domain) tablolarına aktarır';

    private array $publicMail = [
        'gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com', 'live.com',
        'icloud.com', 'mynet.com', 'superonline.com', 'none.com', 'yahoo.com.tr',
        'hotmail.com.tr', 'gmail.com.tr', 'gnail.com',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $file = storage_path('app/dn_domain_list.txt');
        if (!is_file($file)) {
            $this->error("Liste dosyası bulunamadı: $file");
            return 1;
        }

        $satirlar = array_filter(array_map('trim', file($file)));
        $eklenenMusteri = $eklenenDomain = $atlanan = 0;
        $rapor = [];

        foreach ($satirlar as $satir) {
            // No \t Müşteri \t Telefon \t Email
            $parts = preg_split('/\t+/', $satir);
            if (count($parts) < 2) continue;

            $no       = trim($parts[0] ?? '');
            $ad       = trim($parts[1] ?? '');
            $telefon  = trim($parts[2] ?? '');
            $email    = strtolower(trim($parts[3] ?? ''));

            if (!$ad) continue;

            // Email yoksa rastgele placeholder (unique constraint için) — domain yok
            $emailDb = $email ?: 'no-email-' . Str::random(6) . '@import.local';

            // Müşteri zaten var mı (email match)
            $existing = DB::table('crm_customers')->where('email', $emailDb)->first();

            if ($existing) {
                $customerId = $existing->id;
                $rapor[] = "→ Mevcut müşteri #{$customerId}: {$ad} ({$email})";
            } else {
                if (!$dry) {
                    $customerId = DB::table('crm_customers')->insertGetId([
                        'adi'        => $ad,
                        'email'      => $emailDb,
                        'telefon'    => $telefon,
                        'durum'      => 'aktif',
                        'kaynak'     => 'dn-import',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $customerId = 0;
                }
                $eklenenMusteri++;
                $rapor[] = "✓ Yeni müşteri #{$customerId}: {$ad} ({$email})";
            }

            // Email'den domain çıkar
            if ($email && str_contains($email, '@')) {
                $emailDomain = strtolower(trim(substr($email, strpos($email, '@') + 1)));
                if ($emailDomain && !in_array($emailDomain, $this->publicMail, true)) {
                    // uyeler tablosunda eşleşen üye var mı?
                    $uye = DB::table('uyeler')->whereRaw('LOWER(email) = ?', [$email])->first();
                    $uyeId = $uye?->id ?? 0;

                    // Aynı domain zaten satilanlar'da var mı?
                    $varMi = DB::table('satilanlar')
                        ->where('domain', $emailDomain)
                        ->where('tipi', '3')
                        ->exists();

                    if (!$varMi) {
                        if (!$dry) {
                            DB::table('satilanlar')->insert([
                                'uyeid'           => $uyeId,
                                'tipi'            => '3',
                                'domain'          => $emailDomain,
                                'tutar'           => 0,
                                'baslangic_tarih' => null,
                                'bitis_tarih'     => null,
                                'mesaj'           => 'DN Kreatif eski liste #' . $no,
                                'durum'           => 1,
                                'tarih'           => now()->toDateTimeString(),
                            ]);
                        }
                        $eklenenDomain++;
                        $rapor[] = "  + Domain: {$emailDomain} (uye_id={$uyeId})";
                    } else {
                        $atlanan++;
                        $rapor[] = "  ↻ Domain zaten var: {$emailDomain}";
                    }
                }
            }
        }

        $this->info("\n========= ÖZET =========");
        $this->info("Yeni müşteri  : {$eklenenMusteri}");
        $this->info("Yeni domain   : {$eklenenDomain}");
        $this->info("Atlanan (dupl): {$atlanan}");
        $this->info(($dry ? '[DRY-RUN]' : '[YAZILDI]') . ' Toplam satır: ' . count($satirlar));

        if ($this->getOutput()->isVerbose()) {
            foreach ($rapor as $r) $this->line($r);
        } else {
            $this->info('Detaylı rapor için -v ile çalıştır');
        }

        return 0;
    }
}
