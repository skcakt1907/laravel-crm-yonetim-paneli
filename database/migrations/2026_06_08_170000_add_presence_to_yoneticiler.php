<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yoneticiler', function (Blueprint $table) {
            if (!Schema::hasColumn('yoneticiler', 'son_gorulme')) {
                $table->timestamp('son_gorulme')->nullable()->after('son_ip'); // son heartbeat (sekme açık)
            }
            if (!Schema::hasColumn('yoneticiler', 'son_etkinlik')) {
                $table->timestamp('son_etkinlik')->nullable()->after('son_gorulme'); // son gerçek kullanıcı hareketi
            }
        });
    }

    public function down(): void
    {
        Schema::table('yoneticiler', function (Blueprint $table) {
            foreach (['son_gorulme', 'son_etkinlik'] as $c) {
                if (Schema::hasColumn('yoneticiler', $c)) $table->dropColumn($c);
            }
        });
    }
};
