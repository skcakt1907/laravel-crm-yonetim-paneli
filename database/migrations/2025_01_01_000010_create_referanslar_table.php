<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referanslar', function (Blueprint $table) {
            $table->id();
            $table->string('adi');
            $table->string('kisa')->nullable();
            $table->text('aciklama')->nullable();
            $table->string('logo')->nullable();
            $table->string('resim')->nullable();
            $table->string('seo')->nullable()->unique();
            $table->string('link')->nullable();
            $table->integer('sira')->default(0);
            $table->boolean('durum')->default(1);
            $table->timestamps();
            
            $table->index('seo');
            $table->index('durum');
            $table->index('sira');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referanslar');
    }
};
