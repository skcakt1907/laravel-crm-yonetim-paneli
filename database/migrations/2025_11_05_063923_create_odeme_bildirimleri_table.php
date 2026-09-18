<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('odeme_bildirimleri')) {
            Schema::create('odeme_bildirimleri', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fatura_id')->nullable();
                $table->string('odeme_yontemi')->nullable();
                $table->decimal('tutar', 15, 2)->default(0);
                $table->string('durum')->default('beklemede');
                $table->text('mesaj')->nullable();
                $table->timestamps();
                
                $table->index('fatura_id');
                $table->index('durum');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('odeme_bildirimleri');
    }
};
