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
        Schema::create('kanban_boards', function (Blueprint $table) {
            $table->id();
            $table->string('adi');
            $table->text('aciklama')->nullable();
            $table->string('renk', 7)->default('#3498db');
            $table->unsignedBigInteger('olusturan_id');
            $table->boolean('durum')->default(1);
            $table->timestamps();
            
            $table->foreign('olusturan_id')->references('id')->on('yoneticiler')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_boards');
    }
};
