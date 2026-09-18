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
        Schema::create('bakiye_gecmisi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uye_id');
            $table->string('tip', 50)->default('yukleme'); // yukleme, harcama, iade
            $table->decimal('tutar', 10, 2)->default(0);
            $table->decimal('bakiye_once', 10, 2)->default(0);
            $table->decimal('bakiye_sonra', 10, 2)->default(0);
            $table->text('aciklama')->nullable();
            $table->string('referans', 100)->nullable(); // Fatura ID, siparis ID vs.
            $table->dateTime('tarih');
            $table->timestamps();
            
            $table->index('uye_id');
            $table->index('tarih');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bakiye_gecmisi');
    }
};
