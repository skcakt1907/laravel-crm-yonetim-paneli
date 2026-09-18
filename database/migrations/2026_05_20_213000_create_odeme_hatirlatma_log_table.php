<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('odeme_hatirlatma_log')) {
            Schema::create('odeme_hatirlatma_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fatura_id')->index();
                $table->string('email', 191);
                $table->unsignedInteger('kalan_gun'); // 7, 3, 1
                $table->date('gonderim_tarihi')->index();
                $table->string('durum', 20)->default('ok'); // ok | fail
                $table->text('hata')->nullable();
                $table->timestamps();
                $table->unique(['fatura_id', 'kalan_gun', 'gonderim_tarihi'], 'unique_send_per_day');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('odeme_hatirlatma_log');
    }
};
