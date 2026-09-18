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
        Schema::create('iletisim', function (Blueprint $table) {
            $table->id();
            $table->string('isim', 255);
            $table->string('email', 255);
            $table->string('telefon', 50)->nullable();
            $table->string('konu', 255)->nullable();
            $table->text('mesaj');
            $table->datetime('tarih');
            $table->tinyInteger('durum')->default(0)->comment('0: Okunmadı, 1: Okundu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iletisim');
    }
};
