<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_teklifleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uye_id');
            $table->string('baslik')->nullable();
            $table->decimal('toplam_tl', 12, 2)->default(0);
            $table->string('para_birimi')->default('TL'); // Teklifin gösterim para birimi (bilgi amaçlı)
            $table->decimal('toplam_para_birimi_tutar', 12, 2)->default(0); // Seçilen para birimindeki toplam
            $table->string('durum')->default('beklemede'); // beklemede, kabul, reddedildi
            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->index('uye_id');
        });

        Schema::create('paket_teklif_paketleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teklif_id');
            $table->unsignedBigInteger('paket_id');
            $table->decimal('birim_fiyat_tl', 12, 2)->default(0); // O anki TL fiyatı (alış kuruna göre)
            $table->integer('adet')->default(1);
            $table->decimal('satir_toplam_tl', 12, 2)->default(0);

            $table->index(['teklif_id', 'paket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_teklif_paketleri');
        Schema::dropIfExists('paket_teklifleri');
    }
};













