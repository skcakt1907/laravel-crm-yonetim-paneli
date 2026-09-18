<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_dm_mesajlar', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_dm_mesajlar', 'dosya')) {
                $table->string('dosya')->nullable()->after('mesaj');       // depolanan yol
            }
            if (!Schema::hasColumn('admin_dm_mesajlar', 'dosya_ad')) {
                $table->string('dosya_ad')->nullable()->after('dosya');    // orijinal dosya adı
            }
            if (!Schema::hasColumn('admin_dm_mesajlar', 'dosya_tip')) {
                $table->string('dosya_tip', 100)->nullable()->after('dosya_ad'); // mime tipi
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_dm_mesajlar', function (Blueprint $table) {
            foreach (['dosya', 'dosya_ad', 'dosya_tip'] as $c) {
                if (Schema::hasColumn('admin_dm_mesajlar', $c)) $table->dropColumn($c);
            }
        });
    }
};
