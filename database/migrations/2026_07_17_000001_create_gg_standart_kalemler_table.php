<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gelir-Gider sayfası: "Standart Aylık Gelir/Gider" manuel kalemleri.
 * ÖNEMLİ: Bu tablo tamamen bağımsızdır — mevcut fatura/gider hesaplarını,
 * net kâr/zarar toplamlarını ETKİLEMEZ. Sadece manuel kayıt + görüntüleme.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gg_standart_kalemler')) {
            return;
        }
        Schema::create('gg_standart_kalemler', function (Blueprint $table) {
            $table->id();
            $table->string('tip', 10);                 // gelir | gider
            $table->string('baslik', 190);
            $table->decimal('tutar', 15, 2)->default(0);
            $table->string('aciklama', 255)->nullable();
            $table->timestamps();

            $table->index('tip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gg_standart_kalemler');
    }
};
