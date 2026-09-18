<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kanban_checklist_items')) {
            Schema::create('kanban_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checklist_id');
            $table->string('metin');
            $table->boolean('tamamlandi')->default(false);
            $table->unsignedBigInteger('tamamlayan_id')->nullable();
            $table->timestamp('tamamlanma_tarihi')->nullable();
            $table->integer('sira')->default(0);
            $table->timestamps();
            
            $table->foreign('checklist_id')->references('id')->on('kanban_card_checklists')->onDelete('cascade');
            $table->foreign('tamamlayan_id')->references('id')->on('yoneticiler')->onDelete('set null');
            $table->index('checklist_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_checklist_items');
    }
};

