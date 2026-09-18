<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bayiler')) {
            Schema::table('bayiler', function (Blueprint $table) {
                if (!Schema::hasColumn('bayiler', 'yonetici_id')) {
                    $table->unsignedBigInteger('yonetici_id')->nullable()->after('uye_id')->comment('Yoneticiler tablosundaki ID (rol=3 olanlar için)');
                    $table->index('yonetici_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bayiler')) {
            Schema::table('bayiler', function (Blueprint $table) {
                if (Schema::hasColumn('bayiler', 'yonetici_id')) {
                    $table->dropIndex(['yonetici_id']);
                    $table->dropColumn('yonetici_id');
                }
            });
        }
    }
};
