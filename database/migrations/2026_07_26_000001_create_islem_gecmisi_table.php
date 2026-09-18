<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşlem Geçmişi + Geri-Al altyapısı (Faz 0).
 * Panelde yapılan (ileride AI asistanının yapacağı) her yazma işlemi buraya kaydedilir.
 * "etkilenen" alanı, etkilenen her satırın ESKİ ve YENİ hâlini JSON olarak tutar;
 * geri-al bu ESKİ hâli geri yazar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('islem_gecmisi')) return;

        Schema::create('islem_gecmisi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kullanici_id')->nullable();      // işlemi yapan admin
            $table->string('kullanici_adi', 120)->default('Sistem');
            $table->string('aksiyon', 50);                               // guncelle|sil|indirim|ekle...
            $table->string('aciklama', 500);                             // insan diliyle özet
            $table->longText('etkilenen');                               // [{tablo,id,eski,yeni}, ...] JSON
            $table->unsignedInteger('etkilenen_adet')->default(0);
            $table->string('kaynak', 20)->default('manuel');            // manuel|ai
            $table->boolean('geri_alinabilir')->default(true);
            $table->boolean('geri_alindi')->default(false);
            $table->unsignedBigInteger('geri_alan_id')->nullable();
            $table->dateTime('geri_alma_tarihi')->nullable();
            $table->dateTime('tarih');

            $table->index('kaynak');
            $table->index('geri_alindi');
            $table->index('tarih');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('islem_gecmisi');
    }
};
