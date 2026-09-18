<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ayarlar', function (Blueprint $table) {
            // Paketler sayfası header arkaplanı
            if (!Schema::hasColumn('ayarlar', 'paketler_arkaplan_turu')) {
                $table->string('paketler_arkaplan_turu', 20)->nullable()->default('resim')->comment('resim, renk, gradient');
            }
            if (!Schema::hasColumn('ayarlar', 'paketler_arkaplan_renk')) {
                $table->string('paketler_arkaplan_renk', 20)->nullable()->default('#141e30');
            }
            if (!Schema::hasColumn('ayarlar', 'paketler_gradient_baslangic')) {
                $table->string('paketler_gradient_baslangic', 20)->nullable()->default('#141e30');
            }
            if (!Schema::hasColumn('ayarlar', 'paketler_gradient_bitis')) {
                $table->string('paketler_gradient_bitis', 20)->nullable()->default('#243b55');
            }
            
            // Paketler sayfası içerik arkaplanı
            if (!Schema::hasColumn('ayarlar', 'paketler_icerik_arkaplan_turu')) {
                $table->string('paketler_icerik_arkaplan_turu', 20)->nullable()->default('gradient')->comment('renk, gradient');
            }
            if (!Schema::hasColumn('ayarlar', 'paketler_icerik_arkaplan_renk')) {
                $table->string('paketler_icerik_arkaplan_renk', 20)->nullable()->default('#141e30');
            }
            if (!Schema::hasColumn('ayarlar', 'paketler_icerik_gradient_baslangic')) {
                $table->string('paketler_icerik_gradient_baslangic', 20)->nullable()->default('#141e30');
            }
            if (!Schema::hasColumn('ayarlar', 'paketler_icerik_gradient_bitis')) {
                $table->string('paketler_icerik_gradient_bitis', 20)->nullable()->default('#243b55');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ayarlar', function (Blueprint $table) {
            $table->dropColumn([
                'paketler_arkaplan_turu',
                'paketler_arkaplan_renk',
                'paketler_gradient_baslangic',
                'paketler_gradient_bitis',
                'paketler_icerik_arkaplan_turu',
                'paketler_icerik_arkaplan_renk',
                'paketler_icerik_gradient_baslangic',
                'paketler_icerik_gradient_bitis',
            ]);
        });
    }
};
