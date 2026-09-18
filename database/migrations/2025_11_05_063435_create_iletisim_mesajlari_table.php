<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('iletisim_mesajlari')) {
            Schema::create('iletisim_mesajlari', function (Blueprint $table) {
                $table->id();
                $table->string('isim');
                $table->string('email');
                $table->string('telefon')->nullable();
                $table->string('konu')->nullable();
                $table->text('mesaj');
                $table->boolean('okundu')->default(0);
                $table->timestamps();
                
                $table->index('okundu');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('iletisim_mesajlari');
    }
};
