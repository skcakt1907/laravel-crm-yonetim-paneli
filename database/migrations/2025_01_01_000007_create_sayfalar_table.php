<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sayfalar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dil_id')->nullable();
            $table->string('adi');
            $table->string('seo')->nullable()->unique();
            $table->text('kisa')->nullable();
            $table->text('aciklama')->nullable();
            $table->longText('icerik')->nullable();
            $table->string('resim')->nullable();
            $table->string('keywords')->nullable();
            $table->text('description')->nullable();
            $table->boolean('anasayfa')->default(0);
            $table->boolean('durum')->default(1);
            $table->integer('sira')->default(0);
            $table->integer('hit')->default(0);
            $table->text('builder_content')->nullable();
            $table->timestamps();
            
            $table->index('dil_id');
            $table->index('seo');
            $table->index('durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sayfalar');
    }
};
