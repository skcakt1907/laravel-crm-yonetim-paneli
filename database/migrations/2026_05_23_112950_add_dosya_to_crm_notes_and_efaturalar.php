<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_notes', 'dosya')) {
                $table->string('dosya')->nullable()->after('icerik');
            }
        });
        Schema::table('musteri_efaturalar', function (Blueprint $table) {
            if (!Schema::hasColumn('musteri_efaturalar', 'dosya')) {
                $table->string('dosya')->nullable()->after('icerik');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_notes', function (Blueprint $table) {
            if (Schema::hasColumn('crm_notes', 'dosya')) {
                $table->dropColumn('dosya');
            }
        });
        Schema::table('musteri_efaturalar', function (Blueprint $table) {
            if (Schema::hasColumn('musteri_efaturalar', 'dosya')) {
                $table->dropColumn('dosya');
            }
        });
    }
};
