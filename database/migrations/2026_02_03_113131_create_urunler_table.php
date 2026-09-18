<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('urunler', function (Blueprint $table) {
            $table->id();
            $table->string('adi', 255);
            $table->text('aciklama')->nullable();
            $table->decimal('fiyat', 10, 2)->default(0);
            $table->integer('stok')->default(0);
            $table->string('resim', 500)->nullable();
            $table->string('seo', 191)->nullable()->unique();
            $table->tinyInteger('durum')->default(1)->comment('1: Aktif, 0: Pasif');
            $table->timestamp('tarih')->useCurrent();
            $table->timestamps();
            
            $table->index('durum');
            $table->index('seo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urunler');
    }
};
