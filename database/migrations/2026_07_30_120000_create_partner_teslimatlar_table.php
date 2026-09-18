<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DN OFİS PARTNERLİĞİ — teslimat takibi.
 *
 * Partner 10.000 $ giriş bedelini öder; karşılığında CRM sisteminden tabelaya
 * kadar bir paket verilir. Bu tablo "ne verildi, ne verilmedi" takibini tutar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_teslimatlar')) return;

        Schema::create('partner_teslimatlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id')->index();

            $table->string('kalem', 190);                       // MacBook Pro, Tabela, ...
            $table->enum('durum', ['bekliyor', 'hazirlaniyor', 'teslim_edildi', 'iptal'])
                  ->default('bekliyor')->index();

            $table->date('teslim_tarihi')->nullable();
            $table->string('seri_no', 120)->nullable();          // cihazlar için
            $table->text('aciklama')->nullable();
            $table->unsignedBigInteger('teslim_eden_id')->nullable();
            $table->integer('sira')->default(0);

            $table->timestamps();

            $table->unique(['bayi_id', 'kalem'], 'partner_teslimat_benzersiz');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_teslimatlar');
    }
};
