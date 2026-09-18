<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('domain_satislar')) {
            return;
        }

        Schema::create('domain_satislar', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('uyeid')->nullable()->index();
            $table->string('domain')->index();
            $table->decimal('tutar', 12, 2)->default(0);
            $table->tinyInteger('durum')->default(0)->comment('0=Pasif, 1=Aktif');
            $table->date('baslangic_tarih')->nullable();
            $table->date('bitis_tarih')->nullable();
            $table->string('saglayici', 100)->nullable();
            $table->string('musteri_email')->nullable();
            $table->text('not')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_satislar');
    }
};
