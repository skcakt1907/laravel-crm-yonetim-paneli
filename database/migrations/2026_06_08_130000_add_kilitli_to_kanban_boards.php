<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kanban_boards') && !Schema::hasColumn('kanban_boards', 'kilitli')) {
            Schema::table('kanban_boards', function (Blueprint $table) {
                // Kilitliyken sadece panoyu oluşturan (sahibi) düzenleyebilir,
                // üyeler yalnızca görüntüler.
                $table->boolean('kilitli')->default(0)->after('durum');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kanban_boards') && Schema::hasColumn('kanban_boards', 'kilitli')) {
            Schema::table('kanban_boards', function (Blueprint $table) {
                $table->dropColumn('kilitli');
            });
        }
    }
};
