<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('webpaketresim')) {
            Schema::create('webpaketresim', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rid')->comment('yazilim_id / paket_id');
                $table->string('resim')->nullable();
                $table->integer('sira')->default(0);
                $table->timestamps();
                
                $table->index('rid');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webpaketresim');
    }
};
