<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('banka_hesaplari')) {
            Schema::create('banka_hesaplari', function (Blueprint $table) {
                $table->id();
                $table->string('banka_adi');
                $table->string('sube_adi')->nullable();
                $table->string('hesap_no')->nullable();
                $table->string('iban')->nullable();
                $table->string('hesap_sahibi');
                $table->boolean('aktif')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banka_hesaplari');
    }
};
