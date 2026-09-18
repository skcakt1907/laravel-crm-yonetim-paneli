<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MÜKERRER MÜŞTERİ BİRLEŞTİRME — iz alanı.
 *
 * Birleştirmede kaybeden kayıt SİLİNMEZ; `birlesen_id` ile hangi karta
 * birleştiği yazılır ve `durum='birlesti'` yapılır. Böylece:
 *   - geri dönülebilir (UPDATE ile birlesen_id=NULL yeter)
 *   - eski id ile gelen bir link/rapor hâlâ hangi karta gideceğini bulur
 *   - hiçbir veri kaybolmaz
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_customers')) return;
        if (Schema::hasColumn('crm_customers', 'birlesen_id')) return;

        Schema::table('crm_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('birlesen_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('crm_customers')) return;
        if (!Schema::hasColumn('crm_customers', 'birlesen_id')) return;

        Schema::table('crm_customers', function (Blueprint $table) {
            $table->dropIndex(['birlesen_id']);
            $table->dropColumn('birlesen_id');
        });
    }
};
