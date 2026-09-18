<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceviriler', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 100);
            $table->unsignedBigInteger('model_id');
            $table->string('lang', 5);
            $table->string('alan', 50);
            $table->mediumText('deger')->nullable();
            $table->timestamps();

            $table->unique(['model_type', 'model_id', 'lang', 'alan'], 'ceviri_unique');
            $table->index(['model_type', 'model_id'], 'ceviri_model_idx');
            $table->index(['lang', 'alan'], 'ceviri_lang_alan_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceviriler');
    }
};
