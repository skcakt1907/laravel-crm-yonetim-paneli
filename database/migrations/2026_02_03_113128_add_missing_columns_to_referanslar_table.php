<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referanslar', function (Blueprint $table) {
            if (!Schema::hasColumn('referanslar', 'baslik')) {
                $table->string('baslik', 255)->nullable()->after('adi');
            }
            if (!Schema::hasColumn('referanslar', 'logo')) {
                $table->string('logo', 500)->nullable()->after('resim');
            }
            if (!Schema::hasColumn('referanslar', 'link')) {
                $table->string('link', 500)->nullable()->after('logo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referanslar', function (Blueprint $table) {
            if (Schema::hasColumn('referanslar', 'baslik')) {
                $table->dropColumn('baslik');
            }
            if (Schema::hasColumn('referanslar', 'logo')) {
                $table->dropColumn('logo');
            }
            if (Schema::hasColumn('referanslar', 'link')) {
                $table->dropColumn('link');
            }
        });
    }
};
