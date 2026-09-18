<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('paytr')) {
            Schema::create('paytr', function (Blueprint $table) {
                $table->id();
                $table->string('merchant_id')->nullable();
                $table->string('merchant_key')->nullable();
                $table->string('merchant_salt')->nullable();
                $table->boolean('test_mode')->default(1);
                $table->boolean('aktif')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paytr');
    }
};
