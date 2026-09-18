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
        Schema::table('yazilimlar', function (Blueprint $table) {
            // İndirim kolonu (yüzde olarak, 0-100 arası)
            if (!Schema::hasColumn('yazilimlar', 'indirim')) {
                $table->decimal('indirim', 5, 2)->default(0)->after('tutar')->comment('İndirim yüzdesi (0-100)');
            }
            
            // Fırsat kolonu (boolean - fırsat olarak gösterilsin mi?)
            if (!Schema::hasColumn('yazilimlar', 'firsat')) {
                $table->boolean('firsat')->default(0)->after('indirim')->comment('Fırsat olarak gösterilsin mi?');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yazilimlar', function (Blueprint $table) {
            if (Schema::hasColumn('yazilimlar', 'indirim')) {
                $table->dropColumn('indirim');
            }
            if (Schema::hasColumn('yazilimlar', 'firsat')) {
                $table->dropColumn('firsat');
            }
        });
    }
};
