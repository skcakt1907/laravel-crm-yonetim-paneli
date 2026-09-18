<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uye_bildirimler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uye_id')->nullable()->index();
            $table->string('baslik');
            $table->text('mesaj');
            $table->enum('tip', ['info', 'success', 'warning', 'danger'])->default('info');
            $table->string('link')->nullable();
            $table->string('ikon')->nullable();
            $table->boolean('okundu')->default(false);
            $table->timestamp('okundu_tarih')->nullable();
            $table->unsignedBigInteger('gonderen_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uye_bildirimler');
    }
};
