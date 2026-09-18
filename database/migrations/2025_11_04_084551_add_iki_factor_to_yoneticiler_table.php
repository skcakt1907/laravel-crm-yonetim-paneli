<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('yoneticiler', function (Blueprint $table) {
            if (!Schema::hasColumn('yoneticiler', 'iki_factor_aktif')) {
                $table->boolean('iki_factor_aktif')->default(0)->after('durum');
    }
            if (!Schema::hasColumn('yoneticiler', 'iki_factor_secret')) {
                $table->string('iki_factor_secret', 255)->nullable()->after('iki_factor_aktif');
            }
        });
    }

    public function down()
    {
        Schema::table('yoneticiler', function (Blueprint $table) {
            if (Schema::hasColumn('yoneticiler', 'iki_factor_secret')) {
                $table->dropColumn('iki_factor_secret');
            }
            if (Schema::hasColumn('yoneticiler', 'iki_factor_aktif')) {
                $table->dropColumn('iki_factor_aktif');
            }
        });
    }
};
