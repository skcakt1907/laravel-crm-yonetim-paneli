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
            $table->decimal('pesin_komisyon_orani', 5, 2)->default(0)->after('komisyon_orani')
                ->comment('Peşin satış komisyon oranı (%)');
            $table->decimal('vadeli_komisyon_orani', 5, 2)->default(0)->after('pesin_komisyon_orani')
                ->comment('Vadeli satış komisyon oranı (%)');
        });
    }

    public function down(): void
    {
        Schema::table('bayiler', function (Blueprint $table) {
            $table->dropColumn(['pesin_komisyon_orani', 'vadeli_komisyon_orani']);
        });
    }
};
