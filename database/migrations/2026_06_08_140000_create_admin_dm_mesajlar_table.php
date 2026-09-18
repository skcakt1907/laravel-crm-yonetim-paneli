<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_dm_mesajlar')) {
            Schema::create('admin_dm_mesajlar', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gonderen_id');   // yoneticiler.id
                $table->unsignedBigInteger('alici_id');      // yoneticiler.id
                $table->text('mesaj');
                $table->boolean('okundu')->default(0);
                $table->timestamp('okundu_at')->nullable();
                $table->timestamps();

                // "bana gelen okunmamışlar" hızlı sorgusu
                $table->index(['alici_id', 'okundu']);
                // iki kişi arası konuşma sorgusu
                $table->index(['gonderen_id', 'alici_id']);
                $table->index(['alici_id', 'gonderen_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_dm_mesajlar');
    }
};
