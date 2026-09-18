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
                if (!Schema::hasColumn('moduller', 'alan8')) {
                    $table->boolean('alan8')->default(1)->after('alan7');
                }
                if (!Schema::hasColumn('moduller', 'alan9')) {
                    $table->boolean('alan9')->default(1)->after('alan8');
                }
                if (!Schema::hasColumn('moduller', 'alan10')) {
                    $table->boolean('alan10')->default(1)->after('alan9')->comment('Referanslar modülü (alternatif)');
                }
            });
            
            // Mevcut kayıtları güncelle
            DB::table('moduller')->update([
                'alan8' => 1,
                'alan9' => 1,
                'alan10' => 1,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('moduller')) {
            Schema::table('moduller', function (Blueprint $table) {
                if (Schema::hasColumn('moduller', 'alan10')) {
                    $table->dropColumn('alan10');
                }
                if (Schema::hasColumn('moduller', 'alan9')) {
                    $table->dropColumn('alan9');
                }
                if (Schema::hasColumn('moduller', 'alan8')) {
                    $table->dropColumn('alan8');
                }
            });
        }
    }
};
