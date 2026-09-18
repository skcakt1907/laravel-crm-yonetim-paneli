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
        Schema::table('sayfalar', function (Blueprint $table) {
            if (!Schema::hasColumn('sayfalar', 'builder_content')) {
                $table->longText('builder_content')->nullable()->after('aciklama_ar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sayfalar', function (Blueprint $table) {
            if (Schema::hasColumn('sayfalar', 'builder_content')) {
                $table->dropColumn('builder_content');
            }
        });
    }
};
