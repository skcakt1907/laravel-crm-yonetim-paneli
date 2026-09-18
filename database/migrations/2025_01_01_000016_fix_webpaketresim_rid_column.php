<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webpaketresim')) {
            if (Schema::hasColumn('webpaketresim', 'yazilim_id') && !Schema::hasColumn('webpaketresim', 'rid')) {
                DB::statement('ALTER TABLE `webpaketresim` CHANGE COLUMN `yazilim_id` `rid` BIGINT UNSIGNED NOT NULL COMMENT "yazilim_id / paket_id"');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webpaketresim')) {
            if (Schema::hasColumn('webpaketresim', 'rid') && !Schema::hasColumn('webpaketresim', 'yazilim_id')) {
                DB::statement('ALTER TABLE `webpaketresim` CHANGE COLUMN `rid` `yazilim_id` BIGINT UNSIGNED NOT NULL');
            }
        }
    }
};
