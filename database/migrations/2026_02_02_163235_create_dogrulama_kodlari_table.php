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
        Schema::create('dogrulama_kodlari', function (Blueprint $table) {
            $table->id();
            $table->string('tip')->default('sifre_sifirlama'); // sifre_sifirlama, sifre_degistir
            $table->string('email')->nullable();
            $table->string('telefon')->nullable();
            $table->string('kod', 10);
            $table->integer('uye_id')->nullable();
            $table->boolean('kullanildi')->default(false);
            $table->timestamp('gecerlilik_suresi')->nullable();
            $table->timestamps();
            
            $table->index(['email', 'kod', 'kullanildi']);
            $table->index(['telefon', 'kod', 'kullanildi']);
            $table->index('uye_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dogrulama_kodlari');
    }
};
