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
            // Fatura Ayarları
            if (!Schema::hasColumn('ayarlar', 'fatura_firma_adi')) {
                $table->string('fatura_firma_adi', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'fatura_vergi_no')) {
                $table->string('fatura_vergi_no', 50)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'fatura_vergi_dairesi')) {
                $table->string('fatura_vergi_dairesi', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'fatura_adres')) {
                $table->text('fatura_adres')->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'fatura_telefon')) {
                $table->string('fatura_telefon', 50)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'fatura_email')) {
                $table->string('fatura_email', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'fatura_web')) {
                $table->string('fatura_web', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'fatura_otomatik_kes')) {
                $table->boolean('fatura_otomatik_kes')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ayarlar', function (Blueprint $table) {
            // Kolonları silmiyoruz, veri kaybı olmasın
        });
    }
};
