<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_musteri_teklifleri')) return;

        Schema::create('crm_musteri_teklifleri', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id');
            $t->string('tip', 20)->default('bayi'); // bayi | ozel
            $t->unsignedBigInteger('kaynak_id')->nullable(); // bayilikler.id veya yazilimlar.id
            $t->string('urun_tipi', 30)->nullable(); // bayi_paket, web_paket, hosting, domain, ozel
            $t->string('paket_adi', 190);
            $t->decimal('tutar', 10, 2)->default(0);
            $t->string('para_birimi', 10)->default('TRY');
            $t->string('odeme_yontemi', 20)->default('online');
            $t->string('token', 64)->unique();
            $t->enum('durum', ['bekliyor','gonderildi','odendi','onaylandi','iptal'])->default('bekliyor');
            $t->text('mesaj')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamps();
            $t->index('customer_id');
            $t->index('token');
            $t->index('durum');
            $t->index('tip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_musteri_teklifleri');
    }
};
