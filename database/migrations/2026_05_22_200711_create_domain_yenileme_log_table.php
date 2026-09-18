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
        Schema::create('domain_yenileme_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_order_id');
            $table->string('email');
            $table->unsignedTinyInteger('kalan_gun');
            $table->date('gonderim_tarihi');
            $table->string('durum', 10)->default('ok');
            $table->text('hata')->nullable();
            $table->timestamps();

            $table->unique(['domain_order_id', 'kalan_gun', 'gonderim_tarihi'], 'domain_log_unique');
            $table->index('gonderim_tarihi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_yenileme_log');
    }
};
