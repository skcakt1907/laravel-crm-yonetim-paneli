<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('randevu_musteriler') && !Schema::hasColumn('randevu_musteriler', 'email')) {
            Schema::table('randevu_musteriler', function (Blueprint $table) {
                $table->string('email')->nullable()->after('telefon');
            });
        }

        if (Schema::hasTable('randevular') && !Schema::hasColumn('randevular', 'musteri_email')) {
            Schema::table('randevular', function (Blueprint $table) {
                $table->string('musteri_email')->nullable()->after('musteri_tel');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('randevular') && Schema::hasColumn('randevular', 'musteri_email')) {
            Schema::table('randevular', function (Blueprint $table) {
                $table->dropColumn('musteri_email');
            });
        }

        if (Schema::hasTable('randevu_musteriler') && Schema::hasColumn('randevu_musteriler', 'email')) {
            Schema::table('randevu_musteriler', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }
};
