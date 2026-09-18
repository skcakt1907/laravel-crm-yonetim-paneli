<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dil_id')->nullable();
            $table->string('adi');
            $table->string('seo')->nullable()->unique();
            $table->text('aciklama')->nullable();
            $table->longText('icerik')->nullable();
            $table->string('resim')->nullable();
            $table->string('keywords')->nullable();
            $table->text('description')->nullable();
            $table->boolean('durum')->default(1);
            $table->integer('sira')->default(0);
            $table->integer('hit')->default(0);
            $table->date('tarih')->nullable();
            $table->timestamps();
            
            $table->index('dil_id');
            $table->index('seo');
            $table->index('durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog');
    }
};
