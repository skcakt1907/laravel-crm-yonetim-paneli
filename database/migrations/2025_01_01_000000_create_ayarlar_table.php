<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayarlar', function (Blueprint $table) {
            $table->id();
            
            // Site Ayarları
            $table->string('site_baslik')->nullable();
            $table->string('site_url')->nullable();
            $table->string('domain_url')->nullable();
            $table->string('site_tema')->nullable();
            $table->text('site_desc')->nullable();
            $table->text('site_keyw')->nullable();
            $table->string('site_dil')->default('tr');
            
            // Firma Bilgileri
            $table->string('firma_adi')->nullable();
            $table->string('firma_telefon')->nullable();
            $table->string('firma_fax')->nullable();
            $table->string('firma_email')->nullable();
            $table->text('firma_adres')->nullable();
            $table->string('copyright')->nullable();
            
            // Logo ve Görseller
            $table->string('firma_logo')->nullable();
            $table->string('firma_footerlogo')->nullable();
            $table->string('favicon')->nullable();
            
            // Sosyal Medya
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('youtube')->nullable();
            $table->string('whatsapp')->nullable();
            
            // Diğer Ayarlar
            $table->text('google_maps')->nullable();
            $table->text('google_analytics')->nullable();
            $table->text('canli_destek')->nullable();
            $table->string('dogrulama_kodu')->nullable();
            $table->string('rcaptha')->nullable();
            
            // Renkler
            $table->string('renk1')->nullable();
            $table->string('renk2')->nullable();
            $table->string('renk3')->nullable();
            
            // Ödeme ve SMS
            $table->string('defaultsms')->nullable();
            $table->string('defaultpayment')->nullable();
            
            // KDV ve Demo
            $table->decimal('kdv', 5, 2)->nullable();
            $table->boolean('demo')->default(0);
            $table->boolean('bakim_modu')->default(0);
            
            // API Ayarları (genel alanlar - detaylar başka tablolarda olabilir)
            $table->text('api_ayarlari')->nullable();
            
            // Mail ve SMS Ayarları
            $table->text('mail_ayarlari')->nullable();
            $table->text('sms_ayarlari')->nullable();
            
            // Ödeme Ayarları
            $table->text('odeme_ayarlari')->nullable();
            
            // İletişim (alternatif alanlar)
            $table->string('adres')->nullable();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->text('maps')->nullable();
            
            // Modül Ayarları (genel)
            $table->text('modul_ayarlari')->nullable();
            
            // Limit Ayarları (genel)
            $table->text('limit_ayarlari')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayarlar');
    }
};
