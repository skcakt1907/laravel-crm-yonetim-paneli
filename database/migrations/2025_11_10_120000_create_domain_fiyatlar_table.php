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
        if (!Schema::hasTable('domain_fiyatlar')) {
            Schema::create('domain_fiyatlar', function (Blueprint $table) {
                $table->id();
                $table->string('uzanti', 50)->unique();
                $table->decimal('kayit_fiyat', 10, 2)->default(0);
                $table->decimal('yenileme_fiyat', 10, 2)->default(0);
                $table->decimal('transfer_fiyat', 10, 2)->default(0);
                $table->boolean('durum')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_fiyatlar');
    }
};

