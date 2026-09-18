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
        Schema::table('bayiler', function (Blueprint $table) {
            $table->decimal('kredi_limiti', 12, 2)->default(0)->after('vadeli_komisyon_orani')
                ->comment('Admin tarafından tanımlanan kredi limiti (TL)');
            $table->decimal('kredi_kullanim', 12, 2)->default(0)->after('kredi_limiti')
                ->comment('Kullanılan kredi miktarı (TL)');
        });
    }

    public function down(): void
    {
        Schema::table('bayiler', function (Blueprint $table) {
            $table->dropColumn(['kredi_limiti', 'kredi_kullanim']);
        });
    }
};
