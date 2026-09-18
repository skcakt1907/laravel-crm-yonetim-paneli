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
        if (!Schema::hasTable('ebulten_aboneler')) {
            Schema::create('ebulten_aboneler', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique();
            $table->string('ad', 255)->nullable();
            $table->string('soyad', 255)->nullable();
            $table->tinyInteger('durum')->default(1)->comment('1: Aktif, 0: Pasif');
            $table->timestamp('abone_tarihi')->useCurrent();
            $table->timestamps();
            
            $table->index('email');
            $table->index('durum');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebulten_aboneler');
    }
};
