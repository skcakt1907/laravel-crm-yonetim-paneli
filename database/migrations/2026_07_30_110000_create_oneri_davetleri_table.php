<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ÖNERİ DAVETLERİ — "A kişisi B kişisini önerdi" bağının ilk adımı.
 *
 * B henüz sistemde YOKKEN öneri bağı kurulamaz (kimliği yok). Bu tablo
 * daveti e-posta üzerinden bekletir; B kayıt olup onaylanınca eşleşme
 * otomatik kurulur ve oneriler tablosuna geçer.
 *
 * Böylece "A kayıtlı değilse ikisini de kaydediyoruz" akışı da mümkün olur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('oneri_davetleri')) return;

        Schema::create('oneri_davetleri', function (Blueprint $table) {
            $table->id();

            // Daveti gönderen
            $table->enum('oneren_tip', ['bayi', 'uye'])->default('bayi');
            $table->unsignedBigInteger('oneren_id');

            // Davet edilen (henüz kaydı olmayabilir)
            $table->string('email', 190)->index();
            $table->string('ad_soyad', 190)->nullable();
            $table->text('mesaj')->nullable();

            // Kurulacak öneri türü
            $table->enum('tip', ['bayi_onerisi', 'musteri_referansi'])->default('bayi_onerisi');

            $table->enum('durum', ['beklemede', 'kullanildi', 'iptal'])->default('beklemede')->index();
            $table->unsignedBigInteger('olusan_oneri_id')->nullable();
            $table->timestamp('eslesme_tarihi')->nullable();

            $table->timestamps();

            // Aynı kişiye aynı türde bekleyen tek davet
            $table->index(['email', 'tip', 'durum'], 'oneri_davet_arama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oneri_davetleri');
    }
};
