<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('adresler')) {
            Schema::create('adresler', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('uyeid');
                $table->string('baslik')->nullable();
                $table->string('ad')->nullable();
                $table->string('soyad')->nullable();
                $table->string('telefon')->nullable();
                $table->string('il')->nullable();
                $table->string('ilce')->nullable();
                $table->string('pkodu')->nullable();
                $table->text('adres')->nullable();
                $table->boolean('varsayilan')->default(0);
                $table->timestamps();
                
                $table->index('uyeid');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adresler');
    }
};
