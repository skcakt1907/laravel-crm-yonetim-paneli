<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ŞİFRE KASASI BÖLÜMLENDİRME (4. öncelik).
 *
 * Kasa iki bölüme ayrılıyor:
 *   sosyal_medya → Instagram, Facebook, Gmail, Google Business...
 *   web_sitesi   → WordPress admin, cPanel, FTP, hosting/domain paneli...
 *
 * Mevcut 37 hesabın tamamı sosyal medya / e-posta hesabı olduğu için
 * varsayılan 'sosyal_medya' — eski kayıtlar olduğu yerde kalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sosyal_medya_hesaplari')) return;
        if (Schema::hasColumn('sosyal_medya_hesaplari', 'bolum')) return;

        Schema::table('sosyal_medya_hesaplari', function (Blueprint $table) {
            $table->string('bolum', 20)->default('sosyal_medya')->after('kayit_id')->index();
        });

        // Emniyet: boş kalan varsa sosyal medyaya çek
        DB::table('sosyal_medya_hesaplari')
            ->whereNull('bolum')->orWhere('bolum', '')
            ->update(['bolum' => 'sosyal_medya']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('sosyal_medya_hesaplari')) return;
        if (!Schema::hasColumn('sosyal_medya_hesaplari', 'bolum')) return;

        Schema::table('sosyal_medya_hesaplari', function (Blueprint $table) {
            $table->dropIndex(['bolum']);
            $table->dropColumn('bolum');
        });
    }
};
