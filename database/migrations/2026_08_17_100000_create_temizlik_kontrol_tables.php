<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEMİZLİK KONTROL (17.08.2026)
 *
 * Üç tablo:
 *  1) temizlik_maddeler  — sabit kontrol listesi (bir kez tanımlanır,
 *     her kontrolde tekrar kullanılır). Örn: "Tuvaletler", "Zemin".
 *  2) temizlik_kontroller — her bir temizlik kontrolü (oluşturma tarihi
 *     burada tutulur).
 *  3) temizlik_kontrol_detay — o kontrolde hangi madde işaretlendi,
 *     NE ZAMAN işaretlendi ve KİM işaretledi.
 *
 * Böylece "temizlik ne zaman yapıldı" (detay.yapilma_tarihi) ve
 * "kontrol ne zaman açıldı" (kontroller.created_at) ayrı ayrı görülür.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('temizlik_maddeler')) {
            Schema::create('temizlik_maddeler', function (Blueprint $table) {
                $table->id();
                $table->string('baslik', 255);
                $table->string('aciklama', 500)->nullable();
                $table->unsignedInteger('sira')->default(0);
                $table->boolean('aktif')->default(1);
                $table->unsignedBigInteger('olusturan_id')->nullable();
                $table->timestamps();
                $table->index(['aktif', 'sira']);
            });
        }

        if (!Schema::hasTable('temizlik_kontroller')) {
            Schema::create('temizlik_kontroller', function (Blueprint $table) {
                $table->id();
                $table->string('baslik', 255)->nullable();   // boşsa tarih gösterilir
                $table->date('kontrol_tarihi');
                $table->text('not')->nullable();
                $table->unsignedBigInteger('olusturan_id')->nullable();
                $table->string('olusturan_adi', 150)->nullable();
                $table->timestamp('tamamlanma_tarihi')->nullable();
                $table->timestamps();
                $table->index('kontrol_tarihi');
            });
        }

        if (!Schema::hasTable('temizlik_kontrol_detay')) {
            Schema::create('temizlik_kontrol_detay', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kontrol_id');
                $table->unsignedBigInteger('madde_id');
                $table->string('madde_baslik', 255);   // madde sonradan silinse de kayıt okunabilsin
                $table->boolean('yapildi')->default(0);
                $table->timestamp('yapilma_tarihi')->nullable();
                $table->unsignedBigInteger('yapan_id')->nullable();
                $table->string('yapan_adi', 150)->nullable();
                $table->timestamps();
                $table->index(['kontrol_id', 'madde_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('temizlik_kontrol_detay');
        Schema::dropIfExists('temizlik_kontroller');
        Schema::dropIfExists('temizlik_maddeler');
    }
};
