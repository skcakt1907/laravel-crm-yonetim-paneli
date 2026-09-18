<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kanban_card_comments')) {
            Schema::create('kanban_card_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('yazar_id');
            $table->text('yorum');
            $table->timestamps();
            
            $table->foreign('card_id')->references('id')->on('kanban_cards')->onDelete('cascade');
            $table->foreign('yazar_id')->references('id')->on('yoneticiler')->onDelete('cascade');
            $table->index('card_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_comments');
    }
};

