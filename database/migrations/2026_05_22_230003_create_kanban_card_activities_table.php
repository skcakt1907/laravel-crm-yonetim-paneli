<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_card_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('yazar_id')->nullable();
            $table->string('tip', 60); // e.g. 'kart_guncellendi', 'uye_eklendi', 'yorum_eklendi', 'kopyalandi', 'arsivlendi', 'liste_degisti'
            $table->text('aciklama')->nullable();
            $table->timestamps();
            $table->foreign('card_id')->references('id')->on('kanban_cards')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_activities');
    }
};
