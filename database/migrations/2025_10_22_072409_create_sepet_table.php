<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sepet')) {
            Schema::create('sepet', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('uyeid');
                $table->unsignedBigInteger('paket_id')->nullable();
                $table->string('paket_tipi')->nullable();
                $table->decimal('fiyat', 15, 2)->default(0);
                $table->integer('adet')->default(1);
                $table->text('ozellikler')->nullable();
                $table->timestamps();
                
                $table->index('uyeid');
                $table->index('paket_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sepet');
    }
};
