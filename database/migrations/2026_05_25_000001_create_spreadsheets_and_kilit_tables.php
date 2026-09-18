<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('spreadsheets')) {
            Schema::create('spreadsheets', function (Blueprint $table) {
                $table->id();
                $table->string('ad', 150);
                $table->text('aciklama')->nullable();
                $table->string('ikon', 10)->nullable()->default('📊');
                $table->longText('veri')->nullable();
                $table->unsignedBigInteger('olusturan_id')->nullable();
                $table->json('yetkili_ids')->nullable();
                $table->boolean('herkes_gorur')->default(false);
                $table->boolean('otomatik_kaydet')->default(true);
                $table->unsignedBigInteger('son_kaydeden_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tablolar_kilit_ayar')) {
            Schema::create('tablolar_kilit_ayar', function (Blueprint $table) {
                $table->id();
                $table->string('pin', 255);
                $table->json('yetkili_ids')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('spreadsheets');
        Schema::dropIfExists('tablolar_kilit_ayar');
    }
};
