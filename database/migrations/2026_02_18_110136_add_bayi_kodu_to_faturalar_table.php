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
            if (!Schema::hasColumn('faturalar', 'bayi_kodu')) {
                $table->string('bayi_kodu', 50)->nullable()->after('uyeid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faturalar', function (Blueprint $table) {
            if (Schema::hasColumn('faturalar', 'bayi_kodu')) {
                $table->dropColumn('bayi_kodu');
            }
        });
    }
};
