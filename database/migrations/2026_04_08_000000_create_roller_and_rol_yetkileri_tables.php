<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('roller')) {
            Schema::create('roller', function (Blueprint $table) {
                $table->increments('id');
                $table->string('ad', 100);
                $table->string('slug', 100)->unique();
                $table->string('aciklama', 255)->nullable();
                $table->tinyInteger('korumali')->default(0); // 1 = silinemez/degistirilemez (patron)
                $table->tinyInteger('durum')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('rol_yetkileri')) {
            Schema::create('rol_yetkileri', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('rol_id');
                $table->string('sayfa_route', 191);
                $table->string('sayfa_adi', 191)->nullable();
                $table->tinyInteger('gorebilir')->default(1);
                $table->timestamps();

                $table->unique(['rol_id', 'sayfa_route'], 'rol_yetki_unique');
                $table->index('rol_id');
            });
        }

        // Seed baslangic rolleri: mevcut yoneticiler.rol ID'leri (1-5) korunur.
        $now = now();
        $seed = [
            ['id' => 1, 'ad' => 'Patron',   'slug' => 'patron',   'aciklama' => 'Super admin - tum yetkiler', 'korumali' => 1],
            ['id' => 2, 'ad' => 'Calisan',  'slug' => 'calisan',  'aciklama' => 'Varsayilan calisan rolu',    'korumali' => 0],
            ['id' => 3, 'ad' => 'Bayi',     'slug' => 'bayi',     'aciklama' => 'Bayi paneli rolu',           'korumali' => 0],
            ['id' => 4, 'ad' => 'Musteri',  'slug' => 'musteri',  'aciklama' => 'Musteri paneli rolu',        'korumali' => 0],
            ['id' => 5, 'ad' => 'Muhasebe', 'slug' => 'muhasebe', 'aciklama' => 'Muhasebe rolu',              'korumali' => 0],
        ];
        foreach ($seed as $row) {
            $exists = DB::table('roller')->where('id', $row['id'])->exists();
            if (!$exists) {
                DB::table('roller')->insert(array_merge($row, [
                    'durum' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_yetkileri');
        Schema::dropIfExists('roller');
    }
};
