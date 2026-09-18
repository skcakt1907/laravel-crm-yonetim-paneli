<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kanban_board_members')) {
            Schema::create('kanban_board_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('board_id');
            $table->unsignedBigInteger('yonetici_id');
            $table->enum('rol', ['sahip', 'editor', 'izleyici'])->default('editor');
            $table->timestamps();
            
            $table->foreign('board_id')->references('id')->on('kanban_boards')->onDelete('cascade');
            $table->foreign('yonetici_id')->references('id')->on('yoneticiler')->onDelete('cascade');
            $table->unique(['board_id', 'yonetici_id']);
            $table->index('board_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_board_members');
    }
};

