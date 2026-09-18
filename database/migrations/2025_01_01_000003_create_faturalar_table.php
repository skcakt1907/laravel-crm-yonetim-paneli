<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faturalar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uyeid');
            $table->string('baslik')->nullable();
            $table->decimal('tutar', 15, 2)->default(0);
            $table->tinyInteger('durum')->default(0)->comment('0: Bekliyor, 1: Ödendi');
            $table->date('tarih')->nullable();
            $table->date('bitis_tarih')->nullable();
            $table->timestamp('odenen_tarih')->nullable();
            $table->string('hizmet')->nullable();
            $table->text('aciklama')->nullable();
            $table->string('odeme_yontemi')->nullable();
            $table->string('spno')->nullable()->comment('Sipariş No');
            $table->string('mail')->nullable();
            $table->timestamps();
            
            $table->index('uyeid');
            $table->index('durum');
            $table->index('tarih');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faturalar');
    }
};
