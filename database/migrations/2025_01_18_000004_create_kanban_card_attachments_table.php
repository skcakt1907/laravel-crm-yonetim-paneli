<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kanban_card_attachments')) {
            Schema::create('kanban_card_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('yukleyen_id');
            $table->string('dosya_adi');
            $table->string('dosya_yolu');
            $table->string('dosya_tipi')->nullable();
            $table->integer('dosya_boyutu')->nullable();
            $table->text('aciklama')->nullable();
            $table->timestamps();
            
            $table->foreign('card_id')->references('id')->on('kanban_cards')->onDelete('cascade');
            $table->foreign('yukleyen_id')->references('id')->on('yoneticiler')->onDelete('cascade');
            $table->index('card_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_attachments');
    }
};

