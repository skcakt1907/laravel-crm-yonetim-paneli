<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * aylik_odeme_bildirim_log'a benzersiz kisit ekler.
 *
 * NEDEN: Hatirlatma iki ayri yoldan tetiklenebiliyor —
 *   1) zamanlayici  : mail:aylik-odeme-hatirlat (09:30)
 *   2) URL-cron ucu : /cron/aylik-odeme-hatirlatma?key=...
 * Ikisi ayni dakikada calisirsa ikisi de "henuz gonderilmemis" goruyor ve
 * AYNI ANDA IKI MAIL gidiyordu. Kardes tablo odeme_hatirlatma_log'da bu
 * kisit zaten vardi, burada eksikti.
 *
 * Kisit + kodda "once kap sonra gonder" duzeni birlikte calisir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('aylik_odeme_bildirim_log')) {
            return;
        }

        // Kisit eklenmeden once mevcut mukerrerleri temizle,
        // yoksa ALTER TABLE hata verir. Her grubun en eskisi kalir.
        DB::statement("
            DELETE l1 FROM aylik_odeme_bildirim_log l1
            INNER JOIN aylik_odeme_bildirim_log l2
              ON  l1.odeme_id        = l2.odeme_id
              AND l1.tip             = l2.tip
              AND l1.bildirim_tarihi = l2.bildirim_tarihi
              AND l1.id              > l2.id
        ");

        $varMi = collect(DB::select("SHOW INDEX FROM aylik_odeme_bildirim_log"))
            ->contains(fn ($i) => $i->Key_name === 'aylik_odeme_log_benzersiz');

        if (!$varMi) {
            Schema::table('aylik_odeme_bildirim_log', function ($table) {
                $table->unique(['odeme_id', 'tip', 'bildirim_tarihi'], 'aylik_odeme_log_benzersiz');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('aylik_odeme_bildirim_log')) {
            Schema::table('aylik_odeme_bildirim_log', function ($table) {
                $table->dropUnique('aylik_odeme_log_benzersiz');
            });
        }
    }
};
