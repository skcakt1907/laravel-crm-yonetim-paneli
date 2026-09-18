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
        Schema::create('calisan_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gonderen_id'); // Gönderen yönetici
            $table->unsignedBigInteger('alici_id')->nullable(); // Alıcı yönetici (null ise herkese)
            $table->string('konu');
            $table->text('mesaj');
            $table->tinyInteger('oncelik')->default(1)->comment('1=Düşük, 2=Normal, 3=Yüksek, 4=Acil');
            $table->tinyInteger('durum')->default(0)->comment('0=Açık, 1=Devam Ediyor, 2=Çözüldü, 3=Kapatıldı');
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->unsignedBigInteger('ust_ticket_id')->nullable(); // Cevap için
            $table->timestamp('okunma_tarihi')->nullable();
            $table->timestamp('cozulme_tarihi')->nullable();
            $table->timestamps();
            
            $table->foreign('gonderen_id')->references('id')->on('yoneticiler')->onDelete('cascade');
            $table->foreign('alici_id')->references('id')->on('yoneticiler')->onDelete('cascade');
            $table->foreign('ust_ticket_id')->references('id')->on('calisan_tickets')->onDelete('cascade');
        });
        
        // Ticket kategorileri
        Schema::create('ticket_kategoriler', function (Blueprint $table) {
            $table->id();
            $table->string('adi');
            $table->string('renk')->default('#007bff');
            $table->string('ikon')->nullable();
            $table->integer('sira')->default(0);
            $table->boolean('durum')->default(1);
            $table->timestamps();
        });
        
        // Ticket bildirimleri
        Schema::create('ticket_bildirimler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('yonetici_id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('mesaj');
            $table->boolean('okundu')->default(0);
            $table->timestamps();
            
            $table->foreign('yonetici_id')->references('id')->on('yoneticiler')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('calisan_tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_bildirimler');
        Schema::dropIfExists('ticket_kategoriler');
        Schema::dropIfExists('calisan_tickets');
    }
};
