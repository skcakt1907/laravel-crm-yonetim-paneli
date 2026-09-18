<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paket puan + yorum sistemi.
 * Mevcut genel `yorumlar` tablosu kullanılır (icerik_id = paket/yazilim id).
 *  - puan : 1-5 yıldız (yorumsuz sadece puan da olabilir)
 *  - tip  : yorumun neye ait olduğu ('paket', 'blog' ...) — tablo genel olduğu için ayrım şart
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('yorumlar')) {
            return;
        }
        Schema::table('yorumlar', function (Blueprint $table) {
            if (!Schema::hasColumn('yorumlar', 'puan')) {
                $table->unsignedTinyInteger('puan')->nullable()->after('yorum');
            }
            if (!Schema::hasColumn('yorumlar', 'tip')) {
                $table->string('tip', 20)->default('paket')->after('icerik_id');
            }
        });

        // Sık sorgu: bir paketin onaylı yorumları
        try {
            Schema::table('yorumlar', function (Blueprint $table) {
                $table->index(['tip', 'icerik_id', 'durum'], 'yorumlar_tip_icerik_durum_idx');
            });
        } catch (\Throwable $e) {
            // index zaten varsa geç
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('yorumlar')) {
            return;
        }
        Schema::table('yorumlar', function (Blueprint $table) {
            try { $table->dropIndex('yorumlar_tip_icerik_durum_idx'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('yorumlar', 'puan')) { $table->dropColumn('puan'); }
            if (Schema::hasColumn('yorumlar', 'tip'))  { $table->dropColumn('tip'); }
        });
    }
};
