<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bayiler', function (Blueprint $table) {
            if (!Schema::hasColumn('bayiler', 'il')) {
                $table->string('il', 100)->nullable()->after('telefon');
            }
            if (!Schema::hasColumn('bayiler', 'ilce')) {
                $table->string('ilce', 100)->nullable()->after('il');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bayiler', function (Blueprint $table) {
            if (Schema::hasColumn('bayiler', 'ilce')) $table->dropColumn('ilce');
            if (Schema::hasColumn('bayiler', 'il')) $table->dropColumn('il');
        });
    }
};
