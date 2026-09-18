<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Halka açık iş ortağı (bayi) başvuruları.
 * Başvuruda ŞİFRE ALINMAZ; hesap ancak admin onayında oluşturulur ve
 * şifre otomatik üretilip başvurana e-posta ile gönderilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bayi_basvurulari')) return;

        Schema::create('bayi_basvurulari', function (Blueprint $table) {
            $table->id();

            // Başvuru bilgileri
            $table->string('firma_adi', 190);
            $table->string('ad_soyad', 190);
            $table->string('email', 190)->index();
            $table->string('telefon', 40);
            $table->string('il', 100)->nullable();
            $table->string('ilce', 100)->nullable();
            $table->string('faaliyet', 190)->nullable();   // ne iş yapıyor
            $table->text('mesaj')->nullable();
            $table->boolean('kvkk')->default(0);
            $table->string('ip', 45)->nullable();

            // Değerlendirme
            $table->enum('durum', ['beklemede', 'onaylandi', 'reddedildi'])->default('beklemede')->index();
            $table->string('red_nedeni', 500)->nullable();
            $table->unsignedBigInteger('onaylayan_id')->nullable();
            $table->timestamp('onay_tarihi')->nullable();
            $table->boolean('okundu')->default(0);

            // Onay sonrası oluşturulan kayıtlar
            $table->unsignedBigInteger('uye_id')->nullable();
            $table->unsignedBigInteger('yonetici_id')->nullable();
            $table->unsignedBigInteger('bayi_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bayi_basvurulari');
    }
};
