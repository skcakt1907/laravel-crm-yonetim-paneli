<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bakiye default değerini 0 yap
        DB::statement("ALTER TABLE uyeler ALTER COLUMN bakiye SET DEFAULT '0'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri al (eski değer 50 idi)
        DB::statement("ALTER TABLE uyeler ALTER COLUMN bakiye SET DEFAULT '50'");
    }
};
