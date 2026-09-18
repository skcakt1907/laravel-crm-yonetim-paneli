<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * CRM KARTI OLMAYAN ÜYELERE KART AÇAR.
 *
 * Panelde artık tek müşteri listesi var: CRM → Müşteriler ("Üyeler" menüsü kaldırıldı).
 * Ama `uyeler` tablosunda olup CRM'de karşılığı olmayan kişiler vardı (30 kişi);
 * menü gizlenince bunlar hiçbir yerde görünmez olurdu. Bazılarının faturası bile var.
 *
 * EŞLEŞTİRME: önce crm_customers.uye_id, sonra e-posta (büyük/küçük harf duyarsız).
 * İkisi de tutmuyorsa yeni kart açılır ve `uye_id` bağı kurulur.
 *
 * Varsayılan KURU ÇALIŞMA'dır; yazmak için --uygula gerekir.
 */
class UyedenCrmKartiAc extends Command
{
    protected $signature = 'uye:crm-karti-ac
                            {--uygula : Gerçekten yaz (yoksa sadece rapor)}
                            {--hepsi : Personel ve test hesapları da dâhil edilsin}';

    protected $description = 'CRM kartı olmayan üyeler için CRM müşteri kartı açar';

    /** Kendi ekibimizin e-posta uzantıları — bunlar müşteri değil */
    private const PERSONEL_ALAN = ['ornek.com', 'nesimiates.com'];

    /** Test/deneme hesabı işaretleri */
    private const TEST_IZ = ['ornek.com', 'example.com', 'test@', 'deneme', 'mobiltest'];

    /**
     * Üye gerçek müşteri mi, personel mi, test mi?
     * @return string 'musteri' | 'personel' | 'test'
     */
    private function tur(object $u): string
    {
        $email = mb_strtolower(trim((string) $u->email));
        $ad    = mb_strtolower(trim(($u->ad ?? '') . ' ' . ($u->soyad ?? '')));

        foreach (self::TEST_IZ as $iz) {
            if (str_contains($email, $iz) || str_contains($ad, $iz)) return 'test';
        }
        foreach (self::PERSONEL_ALAN as $alan) {
            if (str_ends_with($email, '@' . $alan)) return 'personel';
        }

        return 'musteri';
    }

    public function handle(): int
    {
        if (!Schema::hasTable('uyeler') || !Schema::hasTable('crm_customers')) {
            $this->error('uyeler veya crm_customers tablosu yok.');
            return self::FAILURE;
        }

        $uygula   = (bool) $this->option('uygula');
        $kolonlar = Schema::getColumnListing('crm_customers');
        $var      = fn ($k) => in_array($k, $kolonlar, true);

        /*
         * ADIM 0 — KOPUK BAĞLARI ONAR
         *
         * Bazı CRM kayıtlarının e-postası bir üyeyle birebir eşleşiyor ama
         * `uye_id` kolonu boş kalmış. Bu yüzden müşteri kartında faturalar/
         * hizmetler görünmüyor ve mükerrer kart açılma riski var.
         * Kart açmadan ÖNCE bu bağlar kurulur — yeni kayıt oluşturmaz,
         * sadece boş alanı doldurur.
         */
        if ($var('uye_id')) {
            $bagsizlar = DB::table('crm_customers as c')
                ->join('uyeler as u', DB::raw('LOWER(TRIM(c.email))'), '=', DB::raw('LOWER(TRIM(u.email))'))
                ->whereNull('c.birlesen_id')
                ->where(fn ($q) => $q->whereNull('c.uye_id')->orWhere('c.uye_id', 0))
                ->whereRaw("COALESCE(c.email,'') <> ''")
                ->get(['c.id as crm_id', 'c.adi', 'c.email', 'u.id as uye_id']);

            if ($bagsizlar->isNotEmpty()) {
                $this->line("\n<fg=cyan>ADIM 0 — kopuk bağlar</> ({$bagsizlar->count()} kayıt)");
                foreach ($bagsizlar as $b) {
                    $this->line(sprintf(
                        '  CRM #%-5d %-28s ↔ üye #%-5d  %s',
                        $b->crm_id, mb_substr($b->adi, 0, 28), $b->uye_id,
                        $uygula ? '<fg=green>bağlandı</>' : 'bağlanacak'
                    ));
                    if ($uygula) {
                        DB::table('crm_customers')->where('id', $b->crm_id)
                            ->update(['uye_id' => $b->uye_id, 'updated_at' => now()]);
                    }
                }
            }
        }

        // CRM'de karşılığı olmayan üyeler
        $eksikler = DB::table('uyeler as u')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('crm_customers as c')
                  ->where(function ($w) {
                      $w->whereColumn('c.uye_id', 'u.id')
                        ->orWhereRaw("c.email <> '' AND LOWER(c.email) = LOWER(u.email)");
                  });
            })
            ->orderBy('u.id')
            ->get(['u.id', 'u.ad', 'u.soyad', 'u.email', 'u.telefon', 'u.firmaadi', 'u.durum']);

        if ($eksikler->isEmpty()) {
            $this->info('CRM kartı olmayan üye yok — hepsi eşleşmiş.');
            return self::SUCCESS;
        }

        $hepsi = (bool) $this->option('hepsi');

        $this->line('');
        $this->line($uygula ? '<fg=yellow>UYGULAMA MODU — kartlar açılacak</>' : '<fg=cyan>KURU ÇALIŞMA — hiçbir şey yazılmayacak</>');
        if (!$hepsi) {
            $this->line('<fg=cyan>Personel (@ornek.com) ve test hesapları ATLANIYOR — dâhil etmek için --hepsi</>');
        }
        $this->line(str_repeat('─', 100));
        $this->line(sprintf('  %-5s %-28s %-32s %6s %6s  %s', 'ÜYE', 'AD', 'E-POSTA', 'FATURA', 'HİZMET', 'DURUM'));
        $this->line(str_repeat('─', 100));

        $acilan = 0;
        $atlanan = ['personel' => 0, 'test' => 0];

        foreach ($eksikler as $u) {
            $ad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
            if ($ad === '') $ad = trim((string) $u->firmaadi) ?: ('Üye #' . $u->id);

            $fatura = Schema::hasTable('faturalar')
                ? DB::table('faturalar')->where('uyeid', $u->id)->count() : 0;
            $hizmet = Schema::hasTable('satilanlar')
                ? DB::table('satilanlar')->where('uyeid', $u->id)->count() : 0;

            $tur = $this->tur($u);
            $atla = !$hepsi && $tur !== 'musteri';

            $etiket = match (true) {
                $atla && $tur === 'personel' => '<fg=yellow>ATLANDI (personel)</>',
                $atla && $tur === 'test'     => '<fg=yellow>ATLANDI (test)</>',
                ($fatura + $hizmet) > 0      => '<fg=green>müşteri</>',
                default                      => 'potansiyel',
            };

            $this->line(sprintf(
                '  #%-4d %-28s %-32s %6d %6d  %s',
                $u->id, mb_substr($ad, 0, 28), mb_substr((string) $u->email, 0, 32), $fatura, $hizmet, $etiket
            ));

            if ($atla) { $atlanan[$tur]++; continue; }
            if (!$uygula) { $acilan++; continue; }

            $veri = ['adi' => $ad];
            if ($var('uye_id'))     $veri['uye_id']     = $u->id;
            if ($var('email'))      $veri['email']      = $u->email;
            if ($var('telefon'))    $veri['telefon']    = $u->telefon;
            if ($var('unvan'))      $veri['unvan']      = $u->firmaadi ?: null;
            if ($var('kaynak'))     $veri['kaynak']     = 'Üyeden Aktarım';
            // Faturası/hizmeti olan gerçek müşteridir; yoksa potansiyel sayılır
            if ($var('durum'))      $veri['durum']      = ($fatura + $hizmet) > 0 ? 'aktif' : 'potansiyel';
            if ($var('created_at')) $veri['created_at'] = now();
            if ($var('updated_at')) $veri['updated_at'] = now();

            try {
                DB::table('crm_customers')->insert($veri);
                $acilan++;
            } catch (\Throwable $e) {
                $this->error('  → açılamadı: ' . $e->getMessage());
                Log::warning('Üyeden CRM kartı açılamadı', ['uye' => $u->id, 'hata' => $e->getMessage()]);
            }
        }

        $this->line(str_repeat('─', 100));
        $this->line(sprintf(
            '%d üye incelendi · %d kart %s · %d personel + %d test atlandı',
            $eksikler->count(), $acilan, $uygula ? 'açıldı' : 'açılacak',
            $atlanan['personel'], $atlanan['test']
        ));

        if (!$uygula) {
            $this->line("\n<fg=yellow>Uygulamak için:</> php artisan uye:crm-karti-ac --uygula");
        } else {
            Log::info('Üyelerden CRM kartı açıldı', ['adet' => $acilan]);
            $this->line("\n<fg=green>Geri almak için:</> DELETE FROM crm_customers WHERE kaynak = 'Üyeden Aktarım';");
        }

        return self::SUCCESS;
    }
}
