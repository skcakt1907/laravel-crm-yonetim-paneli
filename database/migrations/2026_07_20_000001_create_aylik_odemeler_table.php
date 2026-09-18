<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aylık Bildirimli Ödemeler — periyodik (1/3/6/12 ay) ödeme takibi.
 * Harcamalar/giderler'den BAĞIMSIZ; mevcut muhasebeyi etkilemez.
 * Vadesi yaklaşınca patron+muhasebe rollerine mail + uygulama içi bildirim gider.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('aylik_odemeler')) {
            Schema::create('aylik_odemeler', function (Blueprint $t) {
                $t->id();
                $t->string('baslik');
                $t->unsignedBigInteger('kategori_id')->nullable()->index();
                $t->string('kategori_diger', 150)->nullable();
                $t->decimal('tutar', 15, 2)->default(0);
                $t->string('para_birimi', 5)->default('TRY');
                $t->unsignedSmallInteger('periyot_ay')->default(1); // 1 / 3 / 6 / 12 ...
                $t->date('son_odeme_tarihi')->index();
                $t->enum('durum', ['bekliyor', 'odendi'])->default('bekliyor')->index();
                $t->string('odeme_yontemi', 50)->nullable();
                $t->date('son_odendi_tarihi')->nullable();
                $t->text('aciklama')->nullable();
                $t->tinyInteger('aktif')->default(1);
                $t->unsignedBigInteger('olusturan_id')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('aylik_odeme_bildirim_log')) {
            Schema::create('aylik_odeme_bildirim_log', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('odeme_id')->index();
                $t->string('tip', 20);
                $t->date('bildirim_tarihi');
                $t->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aylik_odeme_bildirim_log');
        Schema::dropIfExists('aylik_odemeler');
    }
};
