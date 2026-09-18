<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ek Hizmetler (madde 15): paket/ilan detayında "Yanında Satın Alınabilecekler"
 * bölümünde gösterilen ek hizmetler (ör. Web Master 25.000₺, Sosyal Medya Yönetimi).
 * Bunlar paketler listesinde AYRI ilan olarak görünmez; sadece ek hizmet olarak sunulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ek_hizmetler')) {
            return;
        }
        Schema::create('ek_hizmetler', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 190);
            $table->decimal('fiyat', 12, 2)->default(0);
            $table->string('ikon', 60)->nullable();        // emoji veya mdi ikon adı
            $table->text('aciklama')->nullable();
            $table->unsignedInteger('sira')->default(0);
            $table->tinyInteger('durum')->default(1);       // 1=aktif, 0=pasif
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ek_hizmetler');
    }
};
