<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('yazilim_hit')) {
            Schema::create('yazilim_hit', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('yid')->comment('yazilim_id');
                $table->string('ip')->nullable();
                $table->timestamp('tarih')->nullable();
                $table->timestamps();
                
                $table->index('yid');
                $table->index('tarih');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('yazilim_hit');
    }
};
