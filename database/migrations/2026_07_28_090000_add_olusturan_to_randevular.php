<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Randevuyu açan personeli kayda geçirir.
 * Bildirimler (SMS/mail) yalnızca bu kişiye gider — tüm yöneticilere değil.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('randevular')) return;

        Schema::table('randevular', function (Blueprint $table) {
            if (!Schema::hasColumn('randevular', 'olusturan_id')) {
                $table->unsignedBigInteger('olusturan_id')->nullable()->index();
            }
            if (!Schema::hasColumn('randevular', 'olusturan_adi')) {
                $table->string('olusturan_adi', 190)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('randevular')) return;

        Schema::table('randevular', function (Blueprint $table) {
            if (Schema::hasColumn('randevular', 'olusturan_id')) {
                $table->dropColumn('olusturan_id');
            }
            if (Schema::hasColumn('randevular', 'olusturan_adi')) {
                $table->dropColumn('olusturan_adi');
            }
        });
    }
};
