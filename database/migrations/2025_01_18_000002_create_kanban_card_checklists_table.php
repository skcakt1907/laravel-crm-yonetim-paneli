<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kanban_card_checklists')) {
            Schema::create('kanban_card_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->string('baslik');
            $table->integer('sira')->default(0);
            $table->timestamps();
            
            $table->foreign('card_id')->references('id')->on('kanban_cards')->onDelete('cascade');
            $table->index('card_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_checklists');
    }
};

