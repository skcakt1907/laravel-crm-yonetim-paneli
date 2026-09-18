<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hizmet_fiyatlari')) {
            Schema::create('hizmet_fiyatlari', function (Blueprint $table) {
                $table->id();
                $table->string('anahtar', 100)->unique();
                $table->string('etiket', 200);
                $table->decimal('fiyat', 12, 2)->default(0);
                $table->string('para_birimi', 5)->default('TRY');
                $table->unsignedInteger('sira')->default(0);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });

            // Default kayıtlar
            DB::table('hizmet_fiyatlari')->insert([
                ['anahtar' => 'business_hosting', 'etiket' => 'Bussines Hosting', 'fiyat' => 2640, 'sira' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['anahtar' => 'domain',           'etiket' => 'Domain',           'fiyat' => 900,  'sira' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['anahtar' => 'ssl',              'etiket' => 'SSL',              'fiyat' => 0,    'sira' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['anahtar' => 'mail_hosting',     'etiket' => 'Mail Hosting',     'fiyat' => 2200, 'sira' => 4, 'created_at' => now(), 'updated_at' => now()],
                ['anahtar' => 'url_yonlendirme',  'etiket' => 'URL Yönlendirme',  'fiyat' => 750,  'sira' => 5, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hizmet_fiyatlari');
    }
};
