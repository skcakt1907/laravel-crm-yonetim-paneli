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
        // Rol kolonu var mı kontrol et
        $hasRolColumn = Schema::hasColumn('yoneticiler', 'rol');
        
        if ($hasRolColumn) {
            // Rol kolonu zaten var, sadece comment güncelliyoruz
            DB::statement("ALTER TABLE yoneticiler MODIFY COLUMN rol TINYINT NOT NULL DEFAULT 2 COMMENT '1=Patron, 2=Çalışan, 3=Bayi, 4=Müşteri'");
        } else {
            // Rol kolonu yok, oluştur
        Schema::table('yoneticiler', function (Blueprint $table) {
                $table->tinyInteger('rol')->default(2)->after('yetki')->comment('1=Patron, 2=Çalışan, 3=Bayi, 4=Müşteri');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri alma işlemi yapmıyoruz çünkü rol kolonu gerekli
    }
};
