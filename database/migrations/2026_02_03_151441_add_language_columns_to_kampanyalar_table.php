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
        if (Schema::hasTable('kampanyalar')) {
            Schema::table('kampanyalar', function (Blueprint $table) {
                // İngilizce kolonlar
                if (!Schema::hasColumn('kampanyalar', 'baslik_en')) {
                    $table->string('baslik_en', 255)->nullable()->after('baslik');
                }
                if (!Schema::hasColumn('kampanyalar', 'aciklama_en')) {
                    $table->text('aciklama_en')->nullable()->after('aciklama');
                }
                
                // Arapça kolonlar
                if (!Schema::hasColumn('kampanyalar', 'baslik_ar')) {
                    $table->string('baslik_ar', 255)->nullable()->after('baslik_en');
                }
                if (!Schema::hasColumn('kampanyalar', 'aciklama_ar')) {
                    $table->text('aciklama_ar')->nullable()->after('aciklama_en');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('kampanyalar')) {
            Schema::table('kampanyalar', function (Blueprint $table) {
                $table->dropColumn(['baslik_en', 'aciklama_en', 'baslik_ar', 'aciklama_ar']);
            });
        }
    }
};
