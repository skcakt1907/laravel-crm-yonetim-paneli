<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gunde bir kez calismasi gereken gorevler icin "bugun calisti" defteri.
 *
 * NEDEN: Gunluk raporlarda hicbir tekrar korumasi yoktu. Zamanlayici iki
 * kez tetiklenirse (ornegin cPanel'de iki ayri "schedule:run" cron girdisi
 * varsa) rapor maili iki kez gidiyordu. withoutOverlapping() yalnizca
 * AYNI ANDA calismayi engeller; arka arkaya calismayi engellemez.
 *
 * (gorev, tarih) benzersiz: ikinci calisma satiri yazamaz ve mail
 * gondermeden atlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gunluk_gorev_log')) {
            return;
        }

        Schema::create('gunluk_gorev_log', function (Blueprint $table) {
            $table->id();
            $table->string('gorev', 80);
            $table->date('tarih');
            $table->timestamp('created_at')->nullable();

            $table->unique(['gorev', 'tarih'], 'gunluk_gorev_benzersiz');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gunluk_gorev_log');
    }
};
