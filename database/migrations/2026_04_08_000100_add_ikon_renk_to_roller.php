<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roller', function (Blueprint $table) {
            if (!Schema::hasColumn('roller', 'ikon')) {
                $table->string('ikon', 10)->nullable()->after('aciklama');
            }
            if (!Schema::hasColumn('roller', 'renk')) {
                $table->string('renk', 20)->default('primary')->after('ikon');
            }
        });

        // Seed varsayilanlar
        $defaults = [
            1 => ['ikon' => '👑', 'renk' => 'danger'],
            2 => ['ikon' => '👤', 'renk' => 'primary'],
            3 => ['ikon' => '🤝', 'renk' => 'success'],
            4 => ['ikon' => '👥', 'renk' => 'info'],
            5 => ['ikon' => '💼', 'renk' => 'warning'],
        ];
        foreach ($defaults as $id => $v) {
            DB::table('roller')->where('id', $id)->update($v);
        }
    }

    public function down(): void
    {
        Schema::table('roller', function (Blueprint $table) {
            if (Schema::hasColumn('roller', 'ikon')) $table->dropColumn('ikon');
            if (Schema::hasColumn('roller', 'renk')) $table->dropColumn('renk');
        });
    }
};
