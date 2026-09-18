<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kanban_boards') && !Schema::hasColumn('kanban_boards', 'ozel')) {
            Schema::table('kanban_boards', function (Blueprint $table) {
                // ozel=0 → tüm yöneticiler görür (ortak pano)
                // ozel=1 → sadece oluşturan (+ eklenen üyeler) görür
                $table->boolean('ozel')->default(0)->after('kategori');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kanban_boards') && Schema::hasColumn('kanban_boards', 'ozel')) {
            Schema::table('kanban_boards', function (Blueprint $table) {
                $table->dropColumn('ozel');
            });
        }
    }
};
