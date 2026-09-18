<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kanban_cards', function (Blueprint $table) {
            $table->string('renk', 20)->nullable()->after('etiketler'); // cover color hex
            $table->timestamp('archived_at')->nullable()->after('renk');
        });
    }

    public function down(): void
    {
        Schema::table('kanban_cards', function (Blueprint $table) {
            $table->dropColumn(['renk', 'archived_at']);
        });
    }
};
