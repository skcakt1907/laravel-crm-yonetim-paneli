<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yoneticiler', function (Blueprint $table) {
            $table->id();
            $table->string('kullaniciadi')->unique();
            $table->string('sifre');
            $table->string('adi')->nullable();
            $table->string('email')->nullable();
            $table->string('telefon')->nullable();
            $table->tinyInteger('yetki')->default(1)->comment('Yetki seviyesi');
            $table->tinyInteger('rol')->default(2)->comment('1=Patron, 2=Çalışan, 3=Bayi, 4=Müşteri');
            $table->boolean('durum')->default(1);
            $table->timestamp('son_giris')->nullable();
            $table->string('son_ip')->nullable();
            $table->boolean('iki_factor')->default(0);
            $table->string('iki_factor_secret')->nullable();
            $table->timestamps();
            
            $table->index('kullaniciadi');
            $table->index('durum');
            $table->index('rol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yoneticiler');
    }
};
