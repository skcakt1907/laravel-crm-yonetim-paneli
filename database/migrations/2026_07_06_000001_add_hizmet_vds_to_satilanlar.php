<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('satilanlar')) {
            return;
        }

        Schema::table('satilanlar', function (Blueprint $table) {
            if (!Schema::hasColumn('satilanlar', 'hizmetler')) {
                $table->json('hizmetler')->nullable(); // ["Domain","Bussines Hosting","SSL"]
            }
            if (!Schema::hasColumn('satilanlar', 'vds')) {
                $table->string('vds', 100)->nullable();
            }
            if (!Schema::hasColumn('satilanlar', 'saglayici')) {
                $table->string('saglayici', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('satilanlar')) {
            return;
        }

        Schema::table('satilanlar', function (Blueprint $table) {
            foreach (['hizmetler', 'vds'] as $col) {
                if (Schema::hasColumn('satilanlar', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
