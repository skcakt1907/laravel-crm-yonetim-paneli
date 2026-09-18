<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('calisan_tickets')) {
            Schema::table('calisan_tickets', function (Blueprint $table) {
                if (!Schema::hasColumn('calisan_tickets', 'dosyalar')) {
                    $table->json('dosyalar')->nullable()->after('mesaj')->comment('Eklenen dosyalar (resim, PDF vb.)');
                }
            });
        }

        if (Schema::hasTable('calisan_ticket_cevaplar')) {
            Schema::table('calisan_ticket_cevaplar', function (Blueprint $table) {
                if (!Schema::hasColumn('calisan_ticket_cevaplar', 'dosyalar')) {
                    $table->json('dosyalar')->nullable()->after('mesaj')->comment('Eklenen dosyalar (resim, PDF vb.)');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('calisan_tickets')) {
            Schema::table('calisan_tickets', function (Blueprint $table) {
                if (Schema::hasColumn('calisan_tickets', 'dosyalar')) {
                    $table->dropColumn('dosyalar');
                }
            });
        }

        if (Schema::hasTable('calisan_ticket_cevaplar')) {
            Schema::table('calisan_ticket_cevaplar', function (Blueprint $table) {
                if (Schema::hasColumn('calisan_ticket_cevaplar', 'dosyalar')) {
                    $table->dropColumn('dosyalar');
                }
            });
        }
    }
};
