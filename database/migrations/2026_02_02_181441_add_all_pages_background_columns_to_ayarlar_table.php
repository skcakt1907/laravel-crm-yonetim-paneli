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
            $sayfalar = ['anasayfa', 'hosting', 'blog', 'iletisim', 'hizmet', 'domain', 'sayfa', 'referanslar', 'firsatlar'];
            
            foreach ($sayfalar as $sayfaAdi) {
                if (!Schema::hasColumn('ayarlar', "{$sayfaAdi}_arkaplan_turu")) {
                    $table->string("{$sayfaAdi}_arkaplan_turu", 20)->nullable()->default('resim')->comment('resim, renk, gradient');
                }
                if (!Schema::hasColumn('ayarlar', "{$sayfaAdi}_arkaplan_renk")) {
                    $table->string("{$sayfaAdi}_arkaplan_renk", 20)->nullable()->default('#141e30');
                }
                if (!Schema::hasColumn('ayarlar', "{$sayfaAdi}_gradient_baslangic")) {
                    $table->string("{$sayfaAdi}_gradient_baslangic", 20)->nullable()->default('#141e30');
                }
                if (!Schema::hasColumn('ayarlar', "{$sayfaAdi}_gradient_bitis")) {
                    $table->string("{$sayfaAdi}_gradient_bitis", 20)->nullable()->default('#243b55');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ayarlar', function (Blueprint $table) {
            $sayfalar = ['anasayfa', 'hosting', 'blog', 'iletisim', 'hizmet', 'domain', 'sayfa', 'referanslar', 'firsatlar'];
            
            foreach ($sayfalar as $sayfaAdi) {
                $table->dropColumn([
                    "{$sayfaAdi}_arkaplan_turu",
                    "{$sayfaAdi}_arkaplan_renk",
                    "{$sayfaAdi}_gradient_baslangic",
                    "{$sayfaAdi}_gradient_bitis",
                ]);
            }
        });
    }
};
