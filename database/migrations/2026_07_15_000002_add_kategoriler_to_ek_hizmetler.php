<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ek hizmet kapsami: bir ek hizmet artik SADECE secilen kategorilerdeki paketlerin
 * detayinda gorunur (ornek: Web Master Hizmeti -> yalnizca Php Web Site Tasarim).
 *
 * kategoriler : virgullu kategori id listesi ("45" veya "45,31"). Bos = tum kategoriler.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ek_hizmetler')) {
            return;
        }

        if (!Schema::hasColumn('ek_hizmetler', 'kategoriler')) {
            Schema::table('ek_hizmetler', function (Blueprint $table) {
                $table->string('kategoriler', 190)->nullable()->after('aciklama');
            });
        }

        // Mevcut kayitlar her pakette cikiyordu; Php Web Site Tasarim (45) ile sinirla.
        DB::table('ek_hizmetler')
            ->whereNull('kategoriler')
            ->orWhere('kategoriler', '')
            ->update(['kategoriler' => '45']);
    }

    public function down(): void
    {
        if (Schema::hasTable('ek_hizmetler') && Schema::hasColumn('ek_hizmetler', 'kategoriler')) {
            Schema::table('ek_hizmetler', function (Blueprint $table) {
                $table->dropColumn('kategoriler');
            });
        }
    }
};
