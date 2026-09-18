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
        if (!Schema::hasTable('hosting_paketler')) {
            Schema::create('hosting_paketler', function (Blueprint $table) {
                $table->id();
                $table->string('adi', 255);
                $table->string('disk_alani', 255)->nullable();
                $table->string('trafik', 255)->nullable();
                $table->string('veritabani', 255)->nullable();
                $table->string('email', 255)->nullable();
                $table->decimal('fiyat', 10, 2)->default(0);
                $table->text('aciklama')->nullable();
                $table->boolean('durum')->default(true);
                $table->timestamp('tarih')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosting_paketler');
    }
};

