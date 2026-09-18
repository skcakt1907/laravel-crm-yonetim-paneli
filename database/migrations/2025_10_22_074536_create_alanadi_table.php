<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('alanadi')) {
            Schema::create('alanadi', function (Blueprint $table) {
                $table->id();
                $table->text('uzanti')->nullable()->comment('JSON formatında uzantı listesi');
                $table->text('kayit')->nullable()->comment('JSON formatında kayıt fiyatları');
                $table->text('yenileme')->nullable()->comment('JSON formatında yenileme fiyatları');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alanadi');
    }
};
