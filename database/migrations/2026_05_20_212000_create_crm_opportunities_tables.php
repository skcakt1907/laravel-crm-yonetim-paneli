<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_pipelines')) {
            Schema::create('crm_pipelines', function (Blueprint $table) {
                $table->id();
                $table->string('adi', 150);
                $table->string('aciklama', 500)->nullable();
                $table->unsignedInteger('sira')->default(0);
                $table->boolean('varsayilan')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crm_stages')) {
            Schema::create('crm_stages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pipeline_id')->index();
                $table->string('adi', 150);
                $table->unsignedTinyInteger('olasilik')->default(0)->comment('% ihtimal');
                $table->string('renk', 20)->nullable();
                $table->unsignedInteger('sira')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crm_opportunities')) {
            Schema::create('crm_opportunities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('musteri_id')->index();
                $table->unsignedBigInteger('pipeline_id')->nullable()->index();
                $table->unsignedBigInteger('stage_id')->nullable()->index();
                $table->string('baslik', 200);
                $table->decimal('tutar', 14, 2)->default(0);
                $table->string('para_birimi', 10)->default('TRY');
                $table->enum('durum', ['acik', 'beklemede', 'kazanildi', 'kaybedildi'])->default('acik')->index();
                $table->unsignedBigInteger('sorumlu_id')->nullable()->index();
                $table->dateTime('beklenen_kapanis')->nullable();
                $table->unsignedTinyInteger('oncelik')->default(3);
                $table->text('aciklama')->nullable();
                $table->timestamps();
            });
        }

        // Default pipeline + stages
        if (DB::table('crm_pipelines')->count() === 0) {
            $pipelineId = DB::table('crm_pipelines')->insertGetId([
                'adi'         => 'Standart Satış',
                'aciklama'    => 'Varsayılan satış pipeline',
                'sira'        => 0,
                'varsayilan'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $stages = [
                ['adi' => 'Yeni Fırsat',   'olasilik' => 10, 'renk' => '#3b82f6', 'sira' => 1],
                ['adi' => 'İlk Görüşme',   'olasilik' => 25, 'renk' => '#06b6d4', 'sira' => 2],
                ['adi' => 'Teklif Verildi','olasilik' => 50, 'renk' => '#b8b62e', 'sira' => 3],
                ['adi' => 'Müzakere',      'olasilik' => 75, 'renk' => '#f59e0b', 'sira' => 4],
                ['adi' => 'Kazanıldı',     'olasilik' => 100,'renk' => '#22c55e', 'sira' => 5],
                ['adi' => 'Kaybedildi',    'olasilik' => 0,  'renk' => '#ef4444', 'sira' => 6],
            ];
            foreach ($stages as $s) {
                DB::table('crm_stages')->insert(array_merge($s, [
                    'pipeline_id' => $pipelineId,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
        Schema::dropIfExists('crm_stages');
        Schema::dropIfExists('crm_pipelines');
    }
};
