<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Şifre Kasası kayıtlarına Aktif/Pasif durumu.
 *
 * Pasif kayıtlar listede solgun görünür ve varsayılan olarak gizlenebilir;
 * müşteriyle çalışılmıyor olsa da giriş bilgileri arşivde kalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sosyal_medya_kayitlari')) return;

        Schema::table('sosyal_medya_kayitlari', function (Blueprint $table) {
            if (!Schema::hasColumn('sosyal_medya_kayitlari', 'durum')) {
                $table->boolean('durum')->default(1)->after('genel_not')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sosyal_medya_kayitlari')) return;

        Schema::table('sosyal_medya_kayitlari', function (Blueprint $table) {
            if (Schema::hasColumn('sosyal_medya_kayitlari', 'durum')) {
                $table->dropColumn('durum');
            }
        });
    }
};
