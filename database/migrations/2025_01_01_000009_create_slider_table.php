<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slider', function (Blueprint $table) {
            $table->id();
            $table->string('adi');
            $table->text('aciklama')->nullable();
            $table->string('resim')->nullable();
            $table->string('link')->nullable();
            $table->integer('sira')->default(0);
            $table->boolean('durum')->default(1);
            $table->timestamps();
            
            $table->index('durum');
            $table->index('sira');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slider');
    }
};
