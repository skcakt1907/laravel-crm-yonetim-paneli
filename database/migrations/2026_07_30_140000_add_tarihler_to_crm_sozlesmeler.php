<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sözleşmelere BAŞLANGIÇ ve BİTİŞ tarihi.
 *
 * Bitişe 1 HAFTA kala otomatik bildirim gider (sozlesme:hatirlat komutu).
 * bitis_bildirim_at damgası, aynı sözleşme için tekrar tekrar mail gitmesini önler.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_sozlesmeler')) return;

        Schema::table('crm_sozlesmeler', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_sozlesmeler', 'baslangic_tarihi')) {
                $table->date('baslangic_tarihi')->nullable()->after('tarih');
            }
            if (!Schema::hasColumn('crm_sozlesmeler', 'bitis_tarihi')) {
                $table->date('bitis_tarihi')->nullable()->after('baslangic_tarihi')->index();
            }
            if (!Schema::hasColumn('crm_sozlesmeler', 'bitis_bildirim_at')) {
                $table->timestamp('bitis_bildirim_at')->nullable()->after('bitis_tarihi');
            }
        });

        // Mevcut kayıtlarda başlangıç = sözleşme tarihi (bitiş bilinmiyor, boş kalır)
        try {
            DB::statement("
                UPDATE crm_sozlesmeler
                   SET baslangic_tarihi = DATE(tarih)
                 WHERE baslangic_tarihi IS NULL AND tarih IS NOT NULL
            ");
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        if (!Schema::hasTable('crm_sozlesmeler')) return;

        Schema::table('crm_sozlesmeler', function (Blueprint $table) {
            foreach (['baslangic_tarihi', 'bitis_tarihi', 'bitis_bildirim_at'] as $k) {
                if (Schema::hasColumn('crm_sozlesmeler', $k)) $table->dropColumn($k);
            }
        });
    }
};
