<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * SAHTE / TEST CRM MÜŞTERİ KAYITLARINI TEMİZLER.
 *
 * Sistem kurulurken atılmış demo verisi (@example.com, @test.com) ve
 * bayi/sistem testlerinden kalan kayıtlar CRM'de "aktif müşteri" olarak duruyor.
 *
 * GÜVENLİK: Gerçek verisi (görev, not, sözleşme, hizmet, randevu, teklif) olan
 * hiçbir kayıt silinmez — tespit edilse bile ATLANIR ve raporlanır.
 * `uyeler` tablosuna DOKUNULMAZ; sadece CRM kartı silinir.
 *
 * Varsayılan KURU ÇALIŞMA'dır; silmek için --uygula gerekir.
 */
class SahteMusteriTemizle extends Command
{
    protected $signature = 'musteri:sahte-temizle
                            {--uygula : Değişikliği yaz (yoksa sadece rapor)}
                            {--sil : KALICI SİL (varsayılan: pasife çekilir, geri alınabilir)}';

    protected $description = 'Sahte/demo ve test CRM müşteri kayıtlarını pasife çeker (veya siler)';

    /** Pasife çekilen kayıtların notuna düşülen iz — geri alma bunu arar */
    private const IZ = '[SAHTE-TEST-PASIF]';

    /** Sahte kabul edilen e-posta kalıpları */
    private const SAHTE_EPOSTA = ['%@example.com', '%@test.com', '%@ornek.com', '%test%@%'];

    /** Sahte kabul edilen ad kalıpları */
    private const SAHTE_AD = ['%test%', '%deneme%', '%örnek%'];

    /** Veri var mı diye bakılan yerler: [tablo, kolon] */
    private const VERI_YERLERI = [
        ['crm_tasks', 'musteri_id'],
        ['crm_notes', 'musteri_id'],
        ['crm_opportunities', 'musteri_id'],
        ['crm_sozlesmeler', 'musteri_id'],
        ['crm_musteri_teklifleri', 'customer_id'],
        ['satilanlar', 'crm_musteri_id'],
        ['randevular', 'musteri_id'],
        ['randevular', 'crm_musteri_id'],
        ['musteri_krediler', 'musteri_id'],
        ['musteri_borc_takip', 'musteri_id'],
        ['musteri_efaturalar', 'crm_musteri_id'],
        ['sosyal_medya_kayitlari', 'crm_musteri_id'],
        ['aylik_alacaklar', 'musteri_id'],
        ['crm_customer_bakiye_hareketleri', 'musteri_id'],
    ];

    public function handle(): int
    {
        if (!Schema::hasTable('crm_customers')) {
            $this->error('crm_customers tablosu yok.');
            return self::FAILURE;
        }

        $uygula = (bool) $this->option('uygula');
        $sil    = (bool) $this->option('sil');

        $adaylar = DB::table('crm_customers')
            ->whereNull('birlesen_id')
            ->where('durum', '<>', 'pasif')          // zaten pasife çekilmişleri tekrar işleme
            ->where(function ($q) {
                foreach (self::SAHTE_EPOSTA as $k) $q->orWhere('email', 'like', $k);
                foreach (self::SAHTE_AD as $k)     $q->orWhere('adi', 'like', $k);
            })
            ->orderBy('id')
            ->get(['id', 'adi', 'email', 'uye_id', 'durum', 'not_icerik']);

        if ($adaylar->isEmpty()) {
            $this->info('Sahte/test kaydı bulunamadı.');
            return self::SUCCESS;
        }

        $eylem = $sil ? 'SİLİNDİ' : 'pasife alındı';
        $eylemGelecek = $sil ? 'silinecek' : 'pasife alınacak';

        $this->line('');
        if (!$uygula) {
            $this->line('<fg=cyan>KURU ÇALIŞMA — hiçbir şey değişmeyecek</>');
        } elseif ($sil) {
            $this->line('<fg=red>KALICI SİLME MODU — geri alınamaz</>');
        } else {
            $this->line('<fg=yellow>PASİFE ÇEKME MODU — kayıtlar durur, listede görünmez, geri alınabilir</>');
        }
        $this->line(str_repeat('─', 96));
        $this->line(sprintf('  %-5s %-26s %-32s %5s  %s', 'ID', 'AD', 'E-POSTA', 'VERİ', 'KARAR'));
        $this->line(str_repeat('─', 96));

        $silinen = 0; $atlanan = 0;

        foreach ($adaylar as $m) {
            $veri = $this->veriSayisi($m->id);

            if ($veri > 0) {
                $this->line(sprintf(
                    '  #%-4d %-26s %-32s %5d  <fg=yellow>ATLANDI — verisi var</>',
                    $m->id, mb_substr($m->adi, 0, 26), mb_substr((string) $m->email, 0, 32), $veri
                ));
                $atlanan++;
                continue;
            }

            $this->line(sprintf(
                '  #%-4d %-26s %-32s %5d  %s',
                $m->id, mb_substr($m->adi, 0, 26), mb_substr((string) $m->email, 0, 32), $veri,
                $uygula ? ($sil ? '<fg=red>SİLİNDİ</>' : '<fg=yellow>PASİF</>') : $eylemGelecek
            ));

            if ($uygula) {
                try {
                    if ($sil) {
                        // Sadece CRM kartı — uyeler tablosuna DOKUNULMUYOR
                        DB::table('crm_customers')->where('id', $m->id)->delete();
                    } else {
                        // Geri alınabilir: eski durum nota yazılır, kayıt durur
                        $not = trim((string) ($m->not_icerik ?? ''));
                        $iz  = self::IZ . ' ' . now()->format('d.m.Y')
                             . ' — sahte/test kaydı olarak pasife alındı (önceki durum: ' . $m->durum . ')';
                        DB::table('crm_customers')->where('id', $m->id)->update([
                            'durum'      => 'pasif',
                            'not_icerik' => $not === '' ? $iz : $not . "\n" . $iz,
                            'updated_at' => now(),
                        ]);
                    }
                    $silinen++;
                } catch (\Throwable $e) {
                    $this->error('  → işlenemedi: ' . $e->getMessage());
                }
            } else {
                $silinen++;
            }
        }

        $this->line(str_repeat('─', 96));
        $this->line(sprintf(
            '%d aday · %d kayıt %s · %d atlandı (verisi olduğu için)',
            $adaylar->count(), $silinen, $uygula ? $eylem : $eylemGelecek, $atlanan
        ));

        if (!$uygula) {
            $this->line("\n<fg=yellow>Pasife çekmek için:</> php artisan musteri:sahte-temizle --uygula");
            $this->line('<fg=red>Kalıcı silmek için:</> php artisan musteri:sahte-temizle --uygula --sil');
        } elseif ($sil) {
            Log::info('Sahte/test CRM müşteri SİLME', ['silinen' => $silinen, 'atlanan' => $atlanan]);
        } else {
            Log::info('Sahte/test CRM müşteri pasife alma', ['pasif' => $silinen, 'atlanan' => $atlanan]);
            $this->line("\n<fg=green>Geri almak için:</>");
            $this->line("  UPDATE crm_customers SET durum='aktif'");
            $this->line("  WHERE durum='pasif' AND not_icerik LIKE '%" . self::IZ . "%';");
        }

        return self::SUCCESS;
    }

    /** Bu müşteriye bağlı toplam gerçek kayıt sayısı */
    private function veriSayisi(int $id): int
    {
        $toplam = 0;
        foreach (self::VERI_YERLERI as [$tablo, $kolon]) {
            if (!Schema::hasTable($tablo) || !Schema::hasColumn($tablo, $kolon)) continue;
            try {
                $toplam += (int) DB::table($tablo)->where($kolon, $id)->count();
            } catch (\Throwable $e) {
                // okunamıyorsa güvenli taraf: veri var say
                $toplam++;
            }
        }
        return $toplam;
    }
}
