<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SOSYAL MEDYA TAKİP TABLOLARINI InnoDB'YE ÇEVİR + ÖKSÜZ SATIRLARI TEMİZLE
 *
 * NEDEN: İlk migration motoru belirtmiyordu. config/database.php'de
 * 'engine' => null olduğu için karar MySQL'e kalıyor ve bu sunucunun
 * varsayılanı MyISAM. Sonuç: dört tablo da MyISAM oluştu ve migration'da
 * yazılan foreign key'ler SESSİZCE yok sayıldı — MyISAM hata vermez,
 * kısıtı görmezden gelir.
 *
 * Pratikte ne oluyordu: bir hesap (marka/platform) silindiğinde
 * onDelete('cascade') hiçbir şey yapmıyor, o hesabın paylaşımları ve gün
 * notları veritabanında öksüz kalıyordu. Yerelde 10 öksüz satır oluştu.
 *
 * Bu migration önce öksüzleri temizler, sonra motoru çevirir. Sıra önemli:
 * öksüz satır varken InnoDB'ye çevirip FK eklemek "Cannot add foreign key
 * constraint" ile patlar.
 *
 * Yeni kurulumlarda zaten InnoDB oluşacağı için bu migration hiçbir şey
 * yapmadan geçer.
 */
return new class extends Migration
{
    /** tablo => FK kurulacak kolon (hepsi sosyal_medya_hesaplari.id'ye bakar) */
    private const TABLOLAR = [
        'sosyal_medya_planlar'         => 'sm_plan_hesap_fk',
        'sosyal_medya_plan_istisnalar' => 'sm_istisna_hesap_fk',
        'sosyal_medya_paylasimlar'     => 'sm_paylasim_hesap_fk',
        'sosyal_medya_gun_notlari'     => 'sm_gun_notu_hesap_fk',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('sosyal_medya_hesaplari')) {
            return;
        }

        foreach (self::TABLOLAR as $tablo => $fkAdi) {
            if (! Schema::hasTable($tablo)) {
                continue;
            }

            // 1. Öksüz satırları sil — FK kurulabilsin diye
            $silinen = DB::table($tablo)
                ->whereNotIn('hesap_id', fn ($q) => $q->select('id')->from('sosyal_medya_hesaplari'))
                ->delete();

            if ($silinen > 0) {
                DB::table('migrations')->exists();   // no-op; log yerine
            }

            // 2. Motoru çevir (zaten InnoDB ise MySQL bunu ucuz geçer)
            if ($this->motor($tablo) !== 'InnoDB') {
                DB::statement("ALTER TABLE `{$tablo}` ENGINE = InnoDB");
            }

            // 3. FK'yı şimdi kur — MyISAM'de yok sayılmıştı
            if (! $this->fkVarMi($tablo)) {
                DB::statement("ALTER TABLE `{$tablo}`
                    ADD CONSTRAINT `{$fkAdi}` FOREIGN KEY (`hesap_id`)
                    REFERENCES `sosyal_medya_hesaplari` (`id`) ON DELETE CASCADE");
            }
        }
    }

    public function down(): void
    {
        // Motoru geri MyISAM'e çevirmiyoruz — bilinçli. Geri alma yalnızca
        // FK'ları kaldırır; MyISAM'e dönmek veri bütünlüğünü kaybettirir.
        foreach (self::TABLOLAR as $tablo => $fkAdi) {
            if (Schema::hasTable($tablo) && $this->fkVarMi($tablo)) {
                DB::statement("ALTER TABLE `{$tablo}` DROP FOREIGN KEY `{$fkAdi}`");
            }
        }
    }

    private function motor(string $tablo): ?string
    {
        $r = DB::selectOne(
            'SELECT ENGINE AS motor FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$tablo]
        );

        return $r->motor ?? null;
    }

    private function fkVarMi(string $tablo): bool
    {
        $r = DB::selectOne(
            'SELECT COUNT(*) AS adet FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND CONSTRAINT_TYPE = ?', [$tablo, 'FOREIGN KEY']
        );

        return (int) ($r->adet ?? 0) > 0;
    }
};
