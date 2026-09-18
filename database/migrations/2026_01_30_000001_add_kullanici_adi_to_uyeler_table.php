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
            if (!Schema::hasColumn('uyeler', 'kullanici_adi')) {
                $table->string('kullanici_adi', 50)->nullable()->unique()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uyeler', function (Blueprint $table) {
            if (Schema::hasColumn('uyeler', 'kullanici_adi')) {
                $table->dropUnique(['kullanici_adi']);
                $table->dropColumn('kullanici_adi');
            }
        });
    }
};
