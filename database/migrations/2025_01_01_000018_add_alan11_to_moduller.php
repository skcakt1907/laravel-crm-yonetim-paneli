<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('moduller')) {
            Schema::table('moduller', function (Blueprint $table) {
                if (!Schema::hasColumn('moduller', 'alan11')) {
                    $table->boolean('alan11')->default(1)->after('alan10');
                }
            });
            
            // Mevcut kayıtları güncelle
            DB::table('moduller')->update([
                'alan11' => 1,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('moduller')) {
            Schema::table('moduller', function (Blueprint $table) {
                if (Schema::hasColumn('moduller', 'alan11')) {
                    $table->dropColumn('alan11');
                }
            });
        }
    }
};
