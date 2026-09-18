<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('yazilimlar')) {
            return;
        }

        try {
            DB::statement("SET SESSION sql_mode = ''");
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::table('yazilimlar')
                ->whereIn('sontarih', ['0000-00-00', '0000-00-00 00:00:00'])
                ->update(['sontarih' => null]);
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('yazilimlar', function (Blueprint $table) {
            if (!Schema::hasColumn('yazilimlar', 'kisa_en')) {
                $column = $table->text('kisa_en')->nullable();
                if (Schema::hasColumn('yazilimlar', 'kisa')) {
                    $column->after('kisa');
                }
            }
            if (!Schema::hasColumn('yazilimlar', 'kisa_ar')) {
                $column = $table->text('kisa_ar')->nullable();
                if (Schema::hasColumn('yazilimlar', 'kisa_en')) {
                    $column->after('kisa_en');
                }
            }
            if (!Schema::hasColumn('yazilimlar', 'ozellik_en')) {
                $column = $table->longText('ozellik_en')->nullable();
                if (Schema::hasColumn('yazilimlar', 'ozellik')) {
                    $column->after('ozellik');
                }
            }
            if (!Schema::hasColumn('yazilimlar', 'ozellik_ar')) {
                $column = $table->longText('ozellik_ar')->nullable();
                if (Schema::hasColumn('yazilimlar', 'ozellik_en')) {
                    $column->after('ozellik_en');
                }
            }
            if (!Schema::hasColumn('yazilimlar', 'talimat_en')) {
                $column = $table->longText('talimat_en')->nullable();
                if (Schema::hasColumn('yazilimlar', 'talimat')) {
                    $column->after('talimat');
                }
            }
            if (!Schema::hasColumn('yazilimlar', 'talimat_ar')) {
                $column = $table->longText('talimat_ar')->nullable();
                if (Schema::hasColumn('yazilimlar', 'talimat_en')) {
                    $column->after('talimat_en');
                }
            }
            if (!Schema::hasColumn('yazilimlar', 'icerik_en')) {
                $column = $table->longText('icerik_en')->nullable();
                if (Schema::hasColumn('yazilimlar', 'icerik')) {
                    $column->after('icerik');
                }
            }
            if (!Schema::hasColumn('yazilimlar', 'icerik_ar')) {
                $column = $table->longText('icerik_ar')->nullable();
                if (Schema::hasColumn('yazilimlar', 'icerik_en')) {
                    $column->after('icerik_en');
                }
            }
        });

        try {
            DB::statement("UPDATE yazilimlar SET kisa_en = kisa, kisa_ar = kisa WHERE kisa_en IS NULL");
            DB::statement("UPDATE yazilimlar SET ozellik_en = ozellik, ozellik_ar = ozellik WHERE ozellik_en IS NULL");
            DB::statement("UPDATE yazilimlar SET talimat_en = talimat, talimat_ar = talimat WHERE talimat_en IS NULL");
            DB::statement("UPDATE yazilimlar SET icerik_en = icerik, icerik_ar = icerik WHERE icerik_en IS NULL");
        } catch (\Throwable $e) {
            // Sessizce geç
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('yazilimlar')) {
            return;
        }

        Schema::table('yazilimlar', function (Blueprint $table) {
            $columns = [
                'kisa_en', 'kisa_ar',
                'ozellik_en', 'ozellik_ar',
                'talimat_en', 'talimat_ar',
                'icerik_en', 'icerik_ar',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('yazilimlar', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

