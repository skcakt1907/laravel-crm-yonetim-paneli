<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KAZANÇ DEFTERİ — her komisyon hareketi tek tek buraya yazılır.
 *
 * Üç tür kazanç:
 *   bayi_komisyonu     → Bayinin kendi satışından (%15 internet / %30 partner)
 *   oner_kazan         → Önerdiği bayinin komisyonunun %50'si
 *   musteri_referansi  → Bağlanan müşterinin alımının %10'u
 *
 * Mükerrer ödemeyi ENGELLEYEN kural: aynı satıştan aynı kişiye aynı türde
 * ikinci kez kazanç yazılamaz (benzersiz indeks).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kazanclar')) return;

        Schema::create('kazanclar', function (Blueprint $table) {
            $table->id();

            // Kazanan (DN Coin üye bakiyesine yattığı için üye zorunlu)
            $table->unsignedBigInteger('kazanan_uye_id')->index();
            $table->unsignedBigInteger('kazanan_bayi_id')->nullable()->index();

            $table->enum('tip', ['bayi_komisyonu', 'oner_kazan', 'musteri_referansi'])->index();
            $table->unsignedBigInteger('oneri_id')->nullable()->index();

            // Kazancı doğuran satışı yapan bayi (öner-kazan'da: önerilen bayi)
            $table->unsignedBigInteger('satis_bayi_id')->nullable();

            // Kaynak kayıt (hangi satış/fatura)
            $table->string('kaynak_tip', 40);
            $table->string('kaynak_id', 40);

            $table->decimal('satis_tutari', 15, 2)->default(0);
            $table->decimal('oran', 5, 2)->default(0);
            $table->decimal('tutar', 15, 2)->default(0);

            // DN Coin'e işlendi mi (MyISAM'de transaction yok — bu bayrakla takip edilir)
            $table->boolean('dnbank_islendi')->default(0);

            $table->enum('durum', ['beklemede', 'odendi', 'iptal'])->default('odendi')->index();
            $table->string('aciklama', 255)->nullable();
            $table->timestamps();

            // Aynı satıştan aynı kişiye aynı türde İKİNCİ KEZ kazanç yazılamaz
            $table->unique(['kaynak_tip', 'kaynak_id', 'kazanan_uye_id', 'tip'], 'kazanclar_mukerrer_engel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kazanclar');
    }
};
