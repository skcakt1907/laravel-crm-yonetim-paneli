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
        Schema::create('kanban_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('board_id');
            $table->string('adi');
            $table->text('aciklama')->nullable();
            $table->string('renk', 7)->nullable();
            $table->integer('sira')->default(0);
            $table->boolean('durum')->default(1);
            $table->timestamps();
            
            $table->foreign('board_id')->references('id')->on('kanban_boards')->onDelete('cascade');
            $table->index('board_id');
            $table->index('sira');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_lists');
    }
};
