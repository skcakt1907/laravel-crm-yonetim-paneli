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
        Schema::create('kanban_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_id');
            $table->string('baslik');
            $table->text('aciklama')->nullable();
            $table->unsignedBigInteger('atanan_id')->nullable();
            $table->date('son_tarih')->nullable();
            $table->enum('oncelik', ['dusuk', 'normal', 'yuksek', 'acil'])->default('normal');
            $table->enum('durum', ['aktif', 'tamamlandi', 'iptal'])->default('aktif');
            $table->integer('sira')->default(0);
            $table->json('etiketler')->nullable();
            $table->timestamps();
            
            $table->foreign('list_id')->references('id')->on('kanban_lists')->onDelete('cascade');
            $table->foreign('atanan_id')->references('id')->on('yoneticiler')->onDelete('set null');
            $table->index('list_id');
            $table->index('sira');
            $table->index('durum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_cards');
    }
};
