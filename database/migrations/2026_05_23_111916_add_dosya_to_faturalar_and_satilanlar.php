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
        Schema::table('faturalar', function (Blueprint $table) {
            $table->string('dosya')->nullable()->after('aciklama');
        });
        Schema::table('satilanlar', function (Blueprint $table) {
            $table->string('dosya')->nullable()->after('mesaj');
        });
    }

    public function down(): void
    {
        Schema::table('faturalar', function (Blueprint $table) {
            $table->dropColumn('dosya');
        });
        Schema::table('satilanlar', function (Blueprint $table) {
            $table->dropColumn('dosya');
        });
    }
};
