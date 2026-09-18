<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Müşteri DM — "havuz" (shared inbox) modeline geçiş.
     * - Konuşma artık sadece uye_id ile (yonetici_id = mesajı hangi admin cevapladı; müşteri mesajında NULL).
     * - musteri_dm_atama: bir müşteriyle hangi adminin "ilgilendiği" (üstlendiği).
     */
    public function up(): void
    {
        // yonetici_id nullable (müşteri mesajlarında boş kalır)
        DB::statement('ALTER TABLE `musteri_dm_mesajlar` MODIFY `yonetici_id` BIGINT UNSIGNED NULL');

        if (!Schema::hasTable('musteri_dm_atama')) {
            Schema::create('musteri_dm_atama', function (Blueprint $table) {
                $table->unsignedBigInteger('uye_id')->primary();   // müşteri başına tek atama
                $table->unsignedBigInteger('yonetici_id');         // ilgilenen admin
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('musteri_dm_atama');
        DB::statement('ALTER TABLE `musteri_dm_mesajlar` MODIFY `yonetici_id` BIGINT UNSIGNED NOT NULL');
    }
};
