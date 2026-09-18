<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bayi_odeme_talepleri')) return;

        Schema::create('bayi_odeme_talepleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('bayilik_id')->nullable();
            $table->string('paket_adi', 150);
            $table->decimal('tutar', 10, 2)->default(0);
            $table->string('para_birimi', 10)->default('TRY');
            $table->string('odeme_yontemi', 20)->default('online'); // online, havale
            $table->string('token', 64)->unique();
            $table->enum('durum', ['bekliyor', 'gonderildi', 'odendi', 'onaylandi', 'iptal'])->default('bekliyor');
            $table->text('mesaj')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('token');
            $table->index('durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bayi_odeme_talepleri');
    }
};
