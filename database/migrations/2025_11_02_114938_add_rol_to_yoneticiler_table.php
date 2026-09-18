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
        Schema::table('yoneticiler', function (Blueprint $table) {
            // Rol kolonu ekle: 1=Patron, 2=Çalışan, 3=Bayi (eğer yoksa)
            if (!Schema::hasColumn('yoneticiler', 'rol')) {
                $table->tinyInteger('rol')->default(2)->after('yetki')->comment('1=Patron, 2=Çalışan, 3=Bayi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yoneticiler', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }
};
