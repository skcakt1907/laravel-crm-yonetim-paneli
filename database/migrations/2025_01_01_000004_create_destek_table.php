<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destek', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uyeid');
            $table->unsignedBigInteger('ustid')->default(0)->comment('0: Ana talep, >0: Cevap');
            $table->string('konu');
            $table->text('mesaj');
            $table->string('kategori')->nullable();
            $table->tinyInteger('durum')->default(0)->comment('0: Açık, 1: Cevaplandı, 2: Çözüldü, 3: Kapalı');
            $table->boolean('okundu')->default(0);
            $table->timestamp('son_cevap')->nullable();
            $table->timestamps();
            
            $table->index('uyeid');
            $table->index('ustid');
            $table->index('durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destek');
    }
};
