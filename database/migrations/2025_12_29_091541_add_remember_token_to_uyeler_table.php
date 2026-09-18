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
        Schema::table('uyeler', function (Blueprint $table) {
            if (!Schema::hasColumn('uyeler', 'remember_token')) {
                $table->rememberToken()->after('sifre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uyeler', function (Blueprint $table) {
            if (Schema::hasColumn('uyeler', 'remember_token')) {
                $table->dropRememberToken();
            }
        });
    }
};
