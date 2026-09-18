<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Müşteri tablarından kaydedilen satırları CRM müşterisiyle doğrudan ilişkilendir
        // uyeIdBul() null döndüğünde işlemlerin iptal olmaması için
        if (Schema::hasTable('satilanlar') && !Schema::hasColumn('satilanlar', 'crm_musteri_id')) {
            Schema::table('satilanlar', function (Blueprint $table) {
                $table->unsignedBigInteger('crm_musteri_id')->nullable()->after('uyeid');
            });
        }

        if (Schema::hasTable('musteri_efaturalar') && !Schema::hasColumn('musteri_efaturalar', 'crm_musteri_id')) {
            Schema::table('musteri_efaturalar', function (Blueprint $table) {
                $table->unsignedBigInteger('crm_musteri_id')->nullable()->after('uyeid');
            });
        }

        if (Schema::hasTable('musteri_raporlar') && !Schema::hasColumn('musteri_raporlar', 'crm_musteri_id')) {
            Schema::table('musteri_raporlar', function (Blueprint $table) {
                $table->unsignedBigInteger('crm_musteri_id')->nullable()->after('uyeid');
            });
        }
    }

    public function down(): void
    {
        foreach (['satilanlar', 'musteri_efaturalar', 'musteri_raporlar'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'crm_musteri_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('crm_musteri_id');
                });
            }
        }
    }
};
