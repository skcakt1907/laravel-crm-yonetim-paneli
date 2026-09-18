<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_listeler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');                         // Liste adı: "Restorana Gidenler"
            $table->string('slug')->nullable();
            $table->text('aciklama')->nullable();
            $table->string('renk', 20)->nullable();       // rozet rengi (opsiyonel)
            $table->unsignedInteger('sira')->default(0);
            $table->boolean('durum')->default(true);      // aktif/pasif liste
            $table->json('kurallar')->nullable();         // gelecekteki OTOMATİK kurallar için (scalable)
            $table->unsignedBigInteger('olusturan_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_listeler');
    }
};
