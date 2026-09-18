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
                if (!Schema::hasColumn('calisan_tickets', 'atanan_id')) {
                    // olusturan_id varsa ondan sonra, yoksa sona ekle
                    if (Schema::hasColumn('calisan_tickets', 'olusturan_id')) {
                        $table->unsignedBigInteger('atanan_id')->nullable()->after('olusturan_id');
                    } else {
                        $table->unsignedBigInteger('atanan_id')->nullable();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('calisan_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('calisan_tickets', 'atanan_id')) {
                $table->dropColumn('atanan_id');
            }
        });
    }
};
