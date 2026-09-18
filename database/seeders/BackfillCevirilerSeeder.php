<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackfillCevirilerSeeder extends Seeder
{
    public function run(): void
    {
        $diller = ['en', 'ar'];
        $kaynaklar = [
            // [generated_file, table, field, lookup_field]
            ['category_titles.php', 'web_kategori', 'adi', 'adi'],
            ['package_titles.php',  'yazilimlar',   'adi', 'adi'],
        ];

        $toplam = 0;
        foreach ($diller as $lang) {
            foreach ($kaynaklar as [$file, $table, $alan, $lookup]) {
                $path = lang_path("generated/{$lang}/{$file}");
                if (!File::exists($path)) {
                    $this->command->warn("Eksik: {$path}");
                    continue;
                }
                $map = include $path;
                if (!is_array($map)) continue;

                $eklenen = 0;
                foreach ($map as $tr => $ceviri) {
                    if (!is_string($tr) || !is_string($ceviri) || $tr === '' || $ceviri === '') continue;

                    // TR metin -> kayıt id
                    $ids = DB::table($table)->where($lookup, $tr)->pluck('id');
                    if ($ids->isEmpty()) continue;

                    foreach ($ids as $id) {
                        DB::table('ceviriler')->updateOrInsert(
                            ['model_type' => $table, 'model_id' => $id, 'lang' => $lang, 'alan' => $alan],
                            ['deger' => $ceviri, 'updated_at' => now(), 'created_at' => now()]
                        );
                        $eklenen++;
                    }
                }
                $this->command->info("{$lang}/{$file} -> {$table}: {$eklenen} kayıt");
                $toplam += $eklenen;
            }
        }
        $this->command->info("Toplam: {$toplam} çeviri kaydı.");
    }
}
