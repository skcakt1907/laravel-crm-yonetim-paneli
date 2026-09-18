<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ana tablo: Kullanıcının oluşturduğu özel tabloların listesi
        Schema::create('custom_tables', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 150);                          // Tablo adı (örn: "Aylık Gider 2026")
            $table->text('aciklama')->nullable();               // Açıklama
            $table->string('renk', 20)->default('yellow');      // İkon rengi (yellow, blue, green vb.)
            $table->string('ikon', 10)->default('📊');          // Emoji ikon
            $table->unsignedBigInteger('olusturan_id');         // yoneticiler.id
            $table->json('yetkili_ids')->nullable();            // Hangi admin'ler erişebilir (id array)
            $table->boolean('herkes_gorur')->default(false);    // True ise tüm adminler görür
            $table->integer('sira')->default(0);                // Sıralama
            $table->timestamps();
            
            $table->index('olusturan_id');
        });
        
        // Sütunlar: Her tablonun kendi sütun tanımları
        Schema::create('custom_table_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('table_id');
            $table->string('ad', 100);                          // Sütun adı (örn: "TARİH", "TUTAR")
            $table->enum('tip', [
                'metin',        // Text
                'sayi',         // Number
                'tarih',        // Date
                'para',         // Currency (TL)
                'secim',        // Dropdown
                'evet_hayir',   // Checkbox/Boolean
            ])->default('metin');
            $table->json('secenekler')->nullable();             // Dropdown seçenekleri (tip=secim ise)
            $table->boolean('zorunlu')->default(false);         // Zorunlu mu?
            $table->boolean('toplam_al')->default(false);       // Bu sütunun toplamı görünsün mü? (sadece sayi/para)
            $table->integer('genislik')->default(150);          // Sütun genişliği (px)
            $table->integer('sira')->default(0);                // Sütun sıralaması
            $table->timestamps();
            
            $table->foreign('table_id')->references('id')->on('custom_tables')->onDelete('cascade');
            $table->index('table_id');
        });
        
        // Satırlar: Veri (JSON olarak esnek)
        Schema::create('custom_table_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('table_id');
            $table->json('veri');                               // Sütun adı => değer (örn: {"TARİH": "2026-05-21", "TUTAR": 1000})
            $table->integer('sira')->default(0);
            $table->unsignedBigInteger('olusturan_id')->nullable();
            $table->timestamps();
            
            $table->foreign('table_id')->references('id')->on('custom_tables')->onDelete('cascade');
            $table->index(['table_id', 'sira']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_table_rows');
        Schema::dropIfExists('custom_table_columns');
        Schema::dropIfExists('custom_tables');
    }
};