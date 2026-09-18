<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bayi satışına müşteri bilgisi + komisyon feragati (indirim) alanları.
 *
 * Bayi kendi komisyonundan feragat ederse, feragat ettiği oran kadar
 * müşteriye indirim yansır. Bizim marjımız DEĞİŞMEZ:
 *   liste 10.000 · bayi %15 yerine %8 uyguladı
 *   → müşteri indirimi %7 = 700  → müşteri 9.300 öder
 *   → bayi komisyonu %8 = 800    → bize kalan 8.500 (tam komisyonda da 8.500)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bayi_satislar')) return;

        Schema::table('bayi_satislar', function (Blueprint $table) {
            if (!Schema::hasColumn('bayi_satislar', 'musteri_adi')) {
                $table->string('musteri_adi', 190)->nullable()->after('fatura_id');
            }
            if (!Schema::hasColumn('bayi_satislar', 'musteri_email')) {
                $table->string('musteri_email', 190)->nullable()->after('musteri_adi');
            }
            if (!Schema::hasColumn('bayi_satislar', 'paket_id')) {
                $table->unsignedBigInteger('paket_id')->nullable()->after('musteri_email');
            }
            if (!Schema::hasColumn('bayi_satislar', 'paket_adi')) {
                $table->string('paket_adi', 190)->nullable()->after('paket_id');
            }
            // Komisyon feragati → müşteri indirimi
            if (!Schema::hasColumn('bayi_satislar', 'indirim_orani')) {
                $table->decimal('indirim_orani', 5, 2)->default(0)->after('komisyon_tutari');
            }
            if (!Schema::hasColumn('bayi_satislar', 'indirim_tutari')) {
                $table->decimal('indirim_tutari', 15, 2)->default(0)->after('indirim_orani');
            }
            if (!Schema::hasColumn('bayi_satislar', 'net_tutar')) {
                $table->decimal('net_tutar', 15, 2)->default(0)->after('indirim_tutari');
            }
        });

        // Eski kayıtlarda net tutar = satış tutarı
        try {
            DB::statement('UPDATE bayi_satislar SET net_tutar = satis_tutari WHERE net_tutar = 0 OR net_tutar IS NULL');
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        if (!Schema::hasTable('bayi_satislar')) return;

        Schema::table('bayi_satislar', function (Blueprint $table) {
            foreach (['musteri_adi','musteri_email','paket_id','paket_adi','indirim_orani','indirim_tutari','net_tutar'] as $k) {
                if (Schema::hasColumn('bayi_satislar', $k)) $table->dropColumn($k);
            }
        });
    }
};
