<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AYLIK BİLDİRİMLİ ALACAKLAR (görev #217/6).
 *
 * "Aylık Bildirimli Ödemeler"in alacak tarafı: bizim TAHSİL EDECEĞİMİZ
 * periyodik tutarlar (aylık bakım, sosyal medya yönetimi, kira geliri vb.).
 * Ödemeler tablosuyla aynı yapıda; ek olarak müşteri bağı vardır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aylik_alacaklar')) return;

        Schema::create('aylik_alacaklar', function (Blueprint $table) {
            $table->id();

            $table->string('baslik', 191);

            // Alacağın müşterisi (opsiyonel — genel alacak da olabilir)
            $table->unsignedBigInteger('musteri_id')->nullable()->index();
            $table->string('musteri_adi', 191)->nullable();   // müşteri kaydı yoksa elle

            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('kategori_diger', 150)->nullable();

            $table->decimal('tutar', 15, 2)->default(0);
            $table->string('para_birimi', 5)->default('TL');
            $table->smallInteger('periyot_ay')->default(1);       // 1/3/6/12
            $table->date('son_tahsil_tarihi')->nullable()->index();

            $table->enum('durum', ['bekliyor', 'tahsil_edildi'])->default('bekliyor')->index();
            $table->string('tahsil_yontemi', 50)->nullable();
            $table->date('son_tahsil_edildi_tarihi')->nullable();

            $table->text('aciklama')->nullable();
            $table->boolean('aktif')->default(1);
            $table->unsignedBigInteger('olusturan_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aylik_alacaklar');
    }
};
