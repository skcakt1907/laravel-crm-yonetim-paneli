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
            if (!Schema::hasColumn('faturalar', 'fatura_no')) {
                $table->string('fatura_no', 50)->nullable()->unique()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faturalar', function (Blueprint $table) {
            if (Schema::hasColumn('faturalar', 'fatura_no')) {
                $table->dropColumn('fatura_no');
            }
        });
    }
};
