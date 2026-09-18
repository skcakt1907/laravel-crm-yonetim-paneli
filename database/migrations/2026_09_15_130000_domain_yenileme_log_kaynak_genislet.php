<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * domain_yenileme_log.kaynak KOLONUNU GENİŞLET — 500 hatası düzeltmesi.
 *
 * SORUN: Kolon varchar(10). İki komut bu tabloya yazıyor:
 *
 *   DomainYenilemeHatirlatCommand -> 'order' / 'manuel' / 'metunic' /
 *       'hosting'  (en fazla 7 karakter, sığıyor)
 *
 *   DomainSesliAramaCommand -> 'sesli:' öneki ekliyor:
 *       'sesli:order'   11 karakter
 *       'sesli:manuel'  12
 *       'sesli:metunic' 13
 *       'sesli:hosting' 13
 *
 * Hepsi 10'u aşıyor. MySQL strict modda bunu kesmiyor, hata fırlatıyor:
 *   SQLSTATE[22001] 1406 Data too long for column 'kaynak'
 *
 * Sonuç: sesli arama komutu ilk kayıtta patlıyor, zamanlayıcı 500 veriyor.
 * Komut hiçbir zaman log yazamamış; aynı gün tekrar arama kontrolü de
 * (aynı kolona bakıyor) bu yüzden hiç eşleşmiyordu.
 *
 * ÇÖZÜM: Kolonu 30 karaktere çıkar. Öneki kısaltmak yerine kolonu
 * genişletmek tercih edildi; 'sesli:' öneki log'da sesli aramayı mail
 * hatırlatmasından ayırıyor, o ayrım korunmalı.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('domain_yenileme_log')) {
            return;
        }

        // Kolon zaten genişse tekrar çalıştırmaya gerek yok
        if ($this->kaynakUzunlugu() >= 30) {
            return;
        }

        /*
         * Ham SQL: bu tabloda kaynak kolonu uzerinde index/FK yok, ama
         * ->change() bazi MariaDB surumlerinde kolon varsayilanini
         * dusuruyor. Varsayilan 'order' korunmali.
         */
        DB::statement("ALTER TABLE domain_yenileme_log
                       MODIFY kaynak VARCHAR(30) NOT NULL DEFAULT 'order'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('domain_yenileme_log')) {
            return;
        }

        /*
         * Geri alirken once tasan kayitlari temizlemek gerekir, yoksa
         * ayni hata bu sefer ALTER sirasinda patlar.
         */
        DB::table('domain_yenileme_log')->whereRaw('LENGTH(kaynak) > 10')->delete();

        DB::statement("ALTER TABLE domain_yenileme_log
                       MODIFY kaynak VARCHAR(10) NOT NULL DEFAULT 'order'");
    }

    private function kaynakUzunlugu(): int
    {
        $kolon = DB::selectOne(
            "SELECT CHARACTER_MAXIMUM_LENGTH AS uzunluk
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'domain_yenileme_log'
               AND COLUMN_NAME = 'kaynak'"
        );

        return (int) ($kolon->uzunluk ?? 0);
    }
};
