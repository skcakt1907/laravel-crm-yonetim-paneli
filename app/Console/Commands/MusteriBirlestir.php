<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * MÜKERRER CRM MÜŞTERİLERİNİ BİRLEŞTİRİR.
 *
 * Aynı `uye_id`'ye bağlı birden fazla CRM kaydı var (33 grup / 67 kayıt).
 * Örn: uye 81 → "Yeliz YILMAZ" + "Yeliz Hanım" + "Yeliz Yılmaz".
 *
 * KAZANAN KAYIT nasıl seçilir (sırayla):
 *   1) Bağlı veri sayısı en fazla olan (sözleşme, görev, not, teklif...)
 *   2) Dolu alan sayısı en fazla olan (vergi no, telefon, adres...)
 *   3) En eski (küçük id) — asıl kayıt kabul edilir
 *
 * NE YAPILIR:
 *   - Kaybeden kayıtların TÜM bağlı verisi kazanana taşınır (18 tablo)
 *   - Kazananın BOŞ alanları kaybedenlerden doldurulur (veri kaybı olmasın)
 *   - Kaybeden kayıt SİLİNMEZ: durum='birlesti', birlesen_id=<kazanan> yazılır
 *
 * Varsayılan KURU ÇALIŞMA'dır; yazmak için --uygula gerekir.
 */
class MusteriBirlestir extends Command
{
    protected $signature = 'musteri:birlestir
                            {--uygula : Gerçekten yaz (yoksa sadece rapor)}
                            {--uye= : Sadece bu uye_id grubunu işle}';

    protected $description = 'Aynı üyeye bağlı mükerrer CRM müşteri kayıtlarını birleştirir';

    /** [tablo, kolon] — crm_customers.id'ye işaret eden her yer */
    private const BAGLAR = [
        ['aylik_alacaklar', 'musteri_id'],
        ['crm_customer_bakiye_hareketleri', 'musteri_id'],
        ['crm_customer_liste', 'customer_id'],
        ['crm_musteri_teklifleri', 'customer_id'],
        ['crm_notes', 'musteri_id'],
        ['crm_opportunities', 'musteri_id'],
        ['crm_sozlesmeler', 'musteri_id'],
        ['crm_tasks', 'musteri_id'],
        ['musteri_borc_takip', 'musteri_id'],
        ['musteri_efaturalar', 'crm_musteri_id'],
        ['musteri_krediler', 'musteri_id'],
        ['musteri_raporlar', 'crm_musteri_id'],
        ['randevular', 'musteri_id'],
        ['randevular', 'crm_musteri_id'],
        ['resellerclub_contacts', 'customer_id'],
        ['resellerclub_customers', 'customer_id'],
        ['satilanlar', 'crm_musteri_id'],
        ['sosyal_medya_kayitlari', 'crm_musteri_id'],
    ];

    /** Kazananın boşsa doldurulacak alanları */
    private const DOLDURULACAK = [
        'email', 'telefon', 'gsm', 'unvan', 'firma_tipi', 'web_sitesi', 'sektor',
        'kaynak', 'adres', 'il', 'ilce', 'mahalle', 'vergi_dairesi', 'vergi_no',
        'tc_kimlik', 'dogum_tarihi', 'kategori', 'profil_foto', 'not_icerik',
    ];

    public function handle(): int
    {
        $uygula = (bool) $this->option('uygula');

        if (!Schema::hasTable('crm_customers')) {
            $this->error('crm_customers tablosu yok.');
            return self::FAILURE;
        }
        if (!Schema::hasColumn('crm_customers', 'birlesen_id')) {
            $this->error('birlesen_id kolonu yok — önce migration çalıştırılmalı.');
            return self::FAILURE;
        }

        $gruplar = DB::table('crm_customers')
            ->select('uye_id', DB::raw('COUNT(*) as adet'))
            ->whereNotNull('uye_id')->where('uye_id', '>', 0)
            ->when($this->option('uye'), fn ($q) => $q->where('uye_id', (int) $this->option('uye')))
            ->where(fn ($q) => $q->whereNull('birlesen_id'))
            ->groupBy('uye_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('adet', 'uye_id');

        if ($gruplar->isEmpty()) {
            $this->info('Birleştirilecek mükerrer kayıt yok.');
            return self::SUCCESS;
        }

        $this->line('');
        $this->line($uygula ? '<fg=yellow>UYGULAMA MODU — kayıtlar yazılacak</>' : '<fg=cyan>KURU ÇALIŞMA — hiçbir şey yazılmayacak</>');
        $this->line(str_repeat('─', 78));

        $toplamTasinan = 0;
        $toplamKapanan = 0;

        foreach ($gruplar as $uyeId => $adet) {
            $kayitlar = DB::table('crm_customers')
                ->where('uye_id', $uyeId)->whereNull('birlesen_id')
                ->get();

            // Her kayda puan ver
            $puanli = $kayitlar->map(function ($k) {
                $k->_veri = $this->bagliVeriSayisi($k->id);
                $k->_dolu = $this->doluAlanSayisi($k);
                return $k;
            })->sortBy([
                fn ($a, $b) => $b->_veri <=> $a->_veri,
                fn ($a, $b) => $b->_dolu <=> $a->_dolu,
                fn ($a, $b) => $a->id <=> $b->id,
            ])->values();

            $kazanan    = $puanli->first();
            $kaybedenler = $puanli->slice(1);

            $this->line(sprintf(
                "\n<fg=green>uye #%d</> — %d kayıt  →  KALACAK: <fg=green>#%d %s</> (veri:%d alan:%d)",
                $uyeId, $adet, $kazanan->id, mb_substr($kazanan->adi, 0, 28), $kazanan->_veri, $kazanan->_dolu
            ));

            foreach ($kaybedenler as $kayip) {
                $tasinacak = $this->bagliVeriSayisi($kayip->id);
                $this->line(sprintf(
                    "            birleşecek: #%-5d %-28s (veri:%d alan:%d)",
                    $kayip->id, mb_substr($kayip->adi, 0, 28), $tasinacak, $kayip->_dolu
                ));

                if ($uygula) {
                    $tasinan = $this->veriyiTasi($kayip->id, $kazanan->id);
                    $this->bosAlanlariDoldur($kazanan, $kayip);
                    DB::table('crm_customers')->where('id', $kayip->id)->update([
                        'birlesen_id' => $kazanan->id,
                        'durum'       => 'birlesti',
                        'updated_at'  => now(),
                    ]);
                    $toplamTasinan += $tasinan;
                }
                $toplamKapanan++;
            }
        }

        $this->line("\n" . str_repeat('─', 78));
        $this->line(sprintf(
            '%d grup · %d kayıt birleş%s%s',
            $gruplar->count(),
            $toplamKapanan,
            $uygula ? 'ti' : 'ecek',
            $uygula ? " · {$toplamTasinan} bağlı kayıt taşındı" : ''
        ));

        if (!$uygula) {
            $this->line("\n<fg=yellow>Uygulamak için:</> php artisan musteri:birlestir --uygula");
        } else {
            Log::info('CRM müşteri birleştirme', [
                'grup' => $gruplar->count(), 'kapanan' => $toplamKapanan, 'tasinan' => $toplamTasinan,
            ]);
            $this->line("\n<fg=green>Geri almak için:</> UPDATE crm_customers SET birlesen_id=NULL, durum='aktif' WHERE durum='birlesti';");
            $this->line('<fg=yellow>NOT:</> taşınan bağlı kayıtlar geri gitmez — gerekirse yedekten dönülür.');
        }

        return self::SUCCESS;
    }

    /** Bu müşteriye bağlı toplam kayıt sayısı */
    private function bagliVeriSayisi(int $musteriId): int
    {
        $toplam = 0;
        foreach (self::BAGLAR as [$tablo, $kolon]) {
            if (!Schema::hasTable($tablo) || !Schema::hasColumn($tablo, $kolon)) continue;
            try {
                $toplam += (int) DB::table($tablo)->where($kolon, $musteriId)->count();
            } catch (\Throwable $e) {
                // tablo okunamıyorsa atla
            }
        }
        return $toplam;
    }

    /** Dolu (boş olmayan) alan sayısı — hangi kartın daha eksiksiz olduğunu ölçer */
    private function doluAlanSayisi(object $k): int
    {
        $sayac = 0;
        foreach (self::DOLDURULACAK as $alan) {
            if (!property_exists($k, $alan)) continue;
            if (trim((string) $k->$alan) !== '') $sayac++;
        }
        return $sayac;
    }

    /** Kaybedenin bağlı verisini kazanana taşır, taşınan satır sayısını döndürür */
    private function veriyiTasi(int $kaynak, int $hedef): int
    {
        $tasinan = 0;
        foreach (self::BAGLAR as [$tablo, $kolon]) {
            if (!Schema::hasTable($tablo) || !Schema::hasColumn($tablo, $kolon)) continue;

            /*
             * BENZERSİZ İNDEKS ÇAKIŞMASI (31.07.2026)
             *
             * crm_customer_liste'de (customer_id, liste_id) benzersiz. Kazanan zaten
             * aynı listedeyse taşıma "Duplicate entry" ile patlıyordu; eskiden hata
             * yutulup satır kaynakta kalıyor, birleşen kayda bağlı yetim satır oluyordu.
             * Artık: taşınabilenler taşınır, taşınamayan (kazananda zaten var olan)
             * satır gereksiz olduğu için silinir.
             */
            $benzersizCakisir = $this->benzersizCakismaVar($tablo, $kolon);

            try {
                if ($benzersizCakisir) {
                    $tasinan += DB::table($tablo)->where($kolon, $kaynak)
                        ->when(true, fn ($q) => $q)   // okunabilirlik
                        ->getConnection()
                        ->affectingStatement(
                            "UPDATE IGNORE `{$tablo}` SET `{$kolon}` = ? WHERE `{$kolon}` = ?",
                            [$hedef, $kaynak]
                        );

                    // Taşınamayanlar: kazananda aynısı zaten var → sil
                    DB::table($tablo)->where($kolon, $kaynak)->delete();
                } else {
                    $tasinan += DB::table($tablo)->where($kolon, $kaynak)->update([$kolon => $hedef]);
                }
            } catch (\Throwable $e) {
                Log::warning('Birleştirmede taşıma hatası', [
                    'tablo' => $tablo, 'kolon' => $kolon, 'kaynak' => $kaynak, 'hata' => $e->getMessage(),
                ]);
            }
        }
        return $tasinan;
    }

    /** Bu kolon, benzersiz bir indeksin parçası mı (taşırken çakışabilir mi)? */
    private function benzersizCakismaVar(string $tablo, string $kolon): bool
    {
        try {
            return DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $tablo)
                ->where('COLUMN_NAME', $kolon)
                ->where('NON_UNIQUE', 0)
                ->where('INDEX_NAME', '<>', 'PRIMARY')
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Kazananın BOŞ alanlarını kaybedenden doldurur (dolu alana dokunmaz) */
    private function bosAlanlariDoldur(object $kazanan, object $kayip): void
    {
        $guncel = [];
        foreach (self::DOLDURULACAK as $alan) {
            if (!property_exists($kazanan, $alan)) continue;
            $mevcut = trim((string) ($kazanan->$alan ?? ''));
            $aday   = trim((string) ($kayip->$alan ?? ''));
            if ($mevcut === '' && $aday !== '') {
                $guncel[$alan]   = $kayip->$alan;
                $kazanan->$alan  = $kayip->$alan;   // sonraki kaybeden için güncel kalsın
            }
        }
        if ($guncel) {
            $guncel['updated_at'] = now();
            DB::table('crm_customers')->where('id', $kazanan->id)->update($guncel);
        }
    }
}
