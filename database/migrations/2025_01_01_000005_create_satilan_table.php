<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satilan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uyeid');
            $table->tinyInteger('tipi')->comment('0: Domain, 1: Hosting, 2: Yazılım/Paket');
            $table->string('adi')->nullable();
            $table->text('aciklama')->nullable();
            $table->decimal('fiyat', 15, 2)->default(0);
            $table->date('baslangic_tarihi')->nullable();
            $table->date('bitis_tarihi')->nullable();
            $table->tinyInteger('durum')->default(1);
            $table->timestamps();
            
            $table->index('uyeid');
            $table->index('tipi');
            $table->index('durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satilan');
    }
};
