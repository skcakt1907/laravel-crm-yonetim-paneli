<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kanban_card_members')) {
            Schema::create('kanban_card_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('card_id');
                $table->unsignedBigInteger('yonetici_id');
                $table->timestamps();
                
                $table->foreign('card_id')->references('id')->on('kanban_cards')->onDelete('cascade');
                $table->foreign('yonetici_id')->references('id')->on('yoneticiler')->onDelete('cascade');
                $table->unique(['card_id', 'yonetici_id']);
                $table->index('card_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_members');
    }
};

