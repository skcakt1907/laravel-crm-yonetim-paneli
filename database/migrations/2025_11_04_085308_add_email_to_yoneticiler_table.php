<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('yoneticiler', function (Blueprint $table) {
            if (!Schema::hasColumn('yoneticiler', 'email')) {
                $table->string('email', 255)->nullable()->after('adi');
            }
            if (!Schema::hasColumn('yoneticiler', 'telefon')) {
                $table->string('telefon', 20)->nullable()->after('email');
            }
        });
    }

    public function down()
    {
        Schema::table('yoneticiler', function (Blueprint $table) {
            if (Schema::hasColumn('yoneticiler', 'telefon')) {
                $table->dropColumn('telefon');
            }
            if (Schema::hasColumn('yoneticiler', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
