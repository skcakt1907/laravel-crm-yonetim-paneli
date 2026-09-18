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
        // Çalışan Tickets Tablosu
        if (!Schema::hasTable('calisan_tickets')) {
            Schema::create('calisan_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('baslik');
            $table->text('mesaj');
            $table->enum('oncelik', ['dusuk', 'normal', 'yuksek', 'acil'])->default('normal');
            $table->tinyInteger('durum')->default(0)->comment('0:Bekliyor, 1:Çözüldü, 2:İptal');
            $table->unsignedBigInteger('olusturan_id');
            $table->string('olusturan_adi');
            $table->timestamp('tarih')->nullable();
            $table->timestamp('son_cevap_tarih')->nullable();
            });
        }

        // Çalışan Ticket Cevapları Tablosu
        if (!Schema::hasTable('calisan_ticket_cevaplar')) {
            Schema::create('calisan_ticket_cevaplar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->text('mesaj');
            $table->unsignedBigInteger('yazan_id');
            $table->string('yazan_adi');
            $table->timestamp('tarih')->nullable();
            
            $table->foreign('ticket_id')->references('id')->on('calisan_tickets')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calisan_ticket_cevaplar');
        Schema::dropIfExists('calisan_tickets');
    }
};
