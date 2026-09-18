<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Müşteri (üye) ↔ yönetici (admin) birebir mesajlaşma.
     * admin_dm_mesajlar mantığının aynısı; konuşma (uye_id, yonetici_id) çifti ile,
     * gonderen alanı yönü belirler.
     */
    public function up(): void
    {
        Schema::create('musteri_dm_mesajlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uye_id');           // konuşmadaki müşteri
            $table->unsignedBigInteger('yonetici_id');      // konuşmadaki yönetici
            $table->enum('gonderen', ['uye', 'admin']);     // mesajı kim gönderdi
            $table->text('mesaj');
            $table->unsignedBigInteger('yanit_id')->nullable();
            $table->boolean('duzenlendi')->default(0);
            $table->boolean('iletildi')->default(0);
            $table->string('dosya', 255)->nullable();
            $table->string('dosya_ad', 255)->nullable();
            $table->string('dosya_tip', 100)->nullable();
            $table->boolean('okundu')->default(0);
            $table->timestamp('okundu_at')->nullable();
            $table->timestamps();

            $table->index(['uye_id', 'yonetici_id']);
            $table->index(['yonetici_id', 'gonderen', 'okundu']);
            $table->index(['uye_id', 'gonderen', 'okundu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('musteri_dm_mesajlar');
    }
};
