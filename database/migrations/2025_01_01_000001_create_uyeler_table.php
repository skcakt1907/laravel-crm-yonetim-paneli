<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uyeler', function (Blueprint $table) {
            $table->id();
            
            // Temel Bilgiler
            $table->string('ad')->nullable();
            $table->string('soyad')->nullable();
            $table->string('email')->unique();
            $table->string('kullanici_adi')->unique()->nullable();
            $table->string('sifre');
            $table->string('telefon')->nullable();
            $table->string('tc', 11)->nullable();
            $table->date('dtarih')->nullable(); // Doğum tarihi
            $table->string('cinsiyet')->nullable();
            
            // Firma Bilgileri
            $table->string('firmaadi')->nullable();
            $table->string('vergino')->nullable();
            $table->string('vergidairesi')->nullable();
            
            // Adres Bilgileri
            $table->string('il')->nullable();
            $table->string('ilce')->nullable();
            $table->string('pkodu')->nullable(); // Posta kodu
            $table->text('adres')->nullable();
            
            // Hesap Bilgileri
            $table->decimal('bakiye', 15, 2)->default(0);
            $table->tinyInteger('durum')->default(1); // 1: Aktif, 0: Pasif
            $table->tinyInteger('utipi')->default(0); // 0: Bireysel, 1: Kurumsal
            $table->tinyInteger('bayi')->default(0); // 0: Normal, 1: Bayi
            
            // Bildirim Ayarları
            $table->boolean('email_bildirim')->default(1);
            $table->boolean('sms_bildirim')->default(0);
            
            // Diğer
            $table->string('nereden_duydunuz')->nullable();
            $table->text('notlar')->nullable();
            $table->text('profil')->nullable(); // Profil resmi yolu
            
            // Sistem Bilgileri
            $table->string('ip')->nullable();
            $table->timestamp('tarih')->nullable(); // Kayıt tarihi
            $table->timestamp('ktarih')->nullable(); // Kayıt tarihi (alternatif)
            $table->timestamp('son_giris')->nullable();
            
            // Laravel Auth
            $table->rememberToken();
            
            // Indexler
            $table->index('email');
            $table->index('kullanici_adi');
            $table->index('durum');
            $table->index('bayi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uyeler');
    }
};
