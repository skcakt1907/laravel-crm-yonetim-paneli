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
        Schema::create('hizmet_bitis_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satilanlar_id');
            $table->string('email');
            $table->unsignedTinyInteger('kalan_gun');
            $table->date('gonderim_tarihi');
            $table->string('durum', 10)->default('ok');
            $table->text('hata')->nullable();
            $table->timestamps();

            $table->unique(['satilanlar_id', 'kalan_gun', 'gonderim_tarihi'], 'hizmet_log_unique');
            $table->index('gonderim_tarihi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hizmet_bitis_log');
    }
};
