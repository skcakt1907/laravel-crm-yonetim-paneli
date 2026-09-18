<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DOMAIN / HOSTING MALİYET ALANI (5. öncelik — "kar hesaplanamıyor").
 *
 * İki katmanlı maliyet:
 *   1) domain_fiyatlar.maliyet_fiyat → uzantı bazlı VARSAYILAN alış fiyatı
 *      (.com = 350 TL gibi). Bir kez girilince 793 domainin tamamı için
 *      kâr hesaplanabilir hâle gelir.
 *   2) satilanlar.maliyet → o kayda ÖZEL alış fiyatı. Doluysa varsayılanı ezer
 *      (pazarlıkla farklı alınan, promosyonlu vb. kayıtlar için).
 *
 * Kâr = satış tutarı − maliyet.  Maliyeti bilinmeyen kayıtlar raporda
 * "maliyeti girilmemiş" olarak AYRI gösterilir; kâra 0 maliyetle dâhil edilmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Uzantı bazlı varsayılan maliyet
        if (Schema::hasTable('domain_fiyatlar') && !Schema::hasColumn('domain_fiyatlar', 'maliyet_fiyat')) {
            Schema::table('domain_fiyatlar', function (Blueprint $table) {
                $table->decimal('maliyet_fiyat', 10, 2)->nullable()->after('transfer_fiyat');
            });
        }

        // 2) Kayda özel maliyet
        //    DİKKAT: satilanlar tablosunda bozuk tarih değerleri olabilir; tabloyu
        //    yeniden kuran ALTER strict modda patlar. Sadece bu oturumda gevşetiliyor.
        if (Schema::hasTable('satilanlar') && !Schema::hasColumn('satilanlar', 'maliyet')) {
            $eskiMod = DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m ?? '';

            try {
                DB::statement("SET SESSION sql_mode = ''");

                Schema::table('satilanlar', function (Blueprint $table) {
                    $table->decimal('maliyet', 15, 2)->nullable()->after('tutar');
                });
            } finally {
                DB::statement('SET SESSION sql_mode = ?', [$eskiMod]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('domain_fiyatlar') && Schema::hasColumn('domain_fiyatlar', 'maliyet_fiyat')) {
            Schema::table('domain_fiyatlar', function (Blueprint $table) {
                $table->dropColumn('maliyet_fiyat');
            });
        }

        if (Schema::hasTable('satilanlar') && Schema::hasColumn('satilanlar', 'maliyet')) {
            $eskiMod = DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m ?? '';
            try {
                DB::statement("SET SESSION sql_mode = ''");
                Schema::table('satilanlar', function (Blueprint $table) {
                    $table->dropColumn('maliyet');
                });
            } finally {
                DB::statement('SET SESSION sql_mode = ?', [$eskiMod]);
            }
        }
    }
};
