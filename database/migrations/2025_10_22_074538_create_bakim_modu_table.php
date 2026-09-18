<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bakim_modu')) {
            Schema::create('bakim_modu', function (Blueprint $table) {
                $table->id();
                $table->boolean('aktif')->default(0);
                $table->string('baslik')->nullable();
                $table->text('mesaj')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bakim_modu');
    }
};
