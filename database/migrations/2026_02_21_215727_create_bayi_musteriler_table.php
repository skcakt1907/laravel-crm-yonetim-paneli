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
        Schema::create('bayi_musteriler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->unsignedBigInteger('uye_id');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('bayi_id')->references('id')->on('bayiler')->onDelete('cascade');
            $table->foreign('uye_id')->references('id')->on('uyeler')->onDelete('cascade');
            
            // Unique constraint: bir bayi bir müşteriyi sadece bir kez ekleyebilir
            $table->unique(['bayi_id', 'uye_id']);
            
            // Indexes
            $table->index('bayi_id');
            $table->index('uye_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bayi_musteriler');
    }
};
