<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SÖZLEŞMELER ARASI BAĞ (3. öncelik — "ana sözleşme / ek sözleşme ilişkisi").
 *
 * Bir sözleşme başka bir sözleşmenin EKİ olabilir. Örnek:
 *   "Kurumsal Web Sitesi Sözleşmesi" (ana)
 *     └─ "Ek Protokol — 2 Dil Desteği" (ek)
 *     └─ "Zeyilname — Süre Uzatımı"    (ek)
 *
 * Kural: tek seviye. Bir EK sözleşme başka bir sözleşmeye ana olamaz;
 * böylece zincir uzayıp karmaşıklaşmıyor (kontrol controller'da).
 *
 * Yabancı anahtar KULLANILMIYOR: crm_sozlesmeler tablosunda hâlihazırda FK yok,
 * ayrıca ana sözleşme silinince eklerin yetim kalmaması controller'da yönetiliyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_sozlesmeler')) return;
        if (Schema::hasColumn('crm_sozlesmeler', 'ana_sozlesme_id')) return;

        Schema::table('crm_sozlesmeler', function (Blueprint $table) {
            $table->unsignedBigInteger('ana_sozlesme_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('crm_sozlesmeler')) return;
        if (!Schema::hasColumn('crm_sozlesmeler', 'ana_sozlesme_id')) return;

        Schema::table('crm_sozlesmeler', function (Blueprint $table) {
            $table->dropIndex(['ana_sozlesme_id']);
            $table->dropColumn('ana_sozlesme_id');
        });
    }
};
