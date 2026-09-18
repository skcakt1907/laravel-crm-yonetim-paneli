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
        Schema::create('crm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('adi', 150);
            $table->string('aciklama', 255)->nullable();
            $table->unsignedInteger('sira')->default(0);
            $table->boolean('varsayilan')->default(false);
            $table->timestamps();
        });

        Schema::create('crm_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')
                ->constrained('crm_pipelines')
                ->cascadeOnDelete();
            $table->string('adi', 150);
            $table->unsignedTinyInteger('olasilik')->default(0);
            $table->string('renk', 20)->nullable();
            $table->unsignedInteger('sira')->default(0);
            $table->timestamps();
        });

        Schema::create('crm_customers', function (Blueprint $table) {
            $table->id();
            $table->string('adi', 150);
            $table->string('unvan', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('telefon', 50)->nullable();
            $table->string('sektor', 100)->nullable();
            $table->string('kaynak', 100)->nullable();
            $table->string('durum', 50)->default('aktif');
            $table->foreignId('sorumlu_id')
                ->nullable()
                ->constrained('yoneticiler')
                ->nullOnDelete();
            $table->text('adres')->nullable();
            $table->json('etiketler')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musteri_id')
                ->constrained('crm_customers')
                ->cascadeOnDelete();
            $table->foreignId('pipeline_id')
                ->nullable()
                ->constrained('crm_pipelines')
                ->nullOnDelete();
            $table->foreignId('stage_id')
                ->nullable()
                ->constrained('crm_stages')
                ->nullOnDelete();
            $table->string('baslik', 180);
            $table->decimal('tutar', 12, 2)->default(0);
            $table->string('para_birimi', 10)->default('TRY');
            $table->string('durum', 50)->default('acik');
            $table->foreignId('sorumlu_id')
                ->nullable()
                ->constrained('yoneticiler')
                ->nullOnDelete();
            $table->date('beklenen_kapanis')->nullable();
            $table->unsignedTinyInteger('oncelik')->default(1);
            $table->text('aciklama')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musteri_id')
                ->nullable()
                ->constrained('crm_customers')
                ->cascadeOnDelete();
            $table->foreignId('firsat_id')
                ->nullable()
                ->constrained('crm_opportunities')
                ->cascadeOnDelete();
            $table->foreignId('olusturan_id')
                ->nullable()
                ->constrained('yoneticiler')
                ->nullOnDelete();
            $table->string('baslik', 150)->nullable();
            $table->text('icerik');
            $table->timestamps();
        });

        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musteri_id')
                ->nullable()
                ->constrained('crm_customers')
                ->cascadeOnDelete();
            $table->foreignId('firsat_id')
                ->nullable()
                ->constrained('crm_opportunities')
                ->cascadeOnDelete();
            $table->foreignId('atanan_id')
                ->nullable()
                ->constrained('yoneticiler')
                ->nullOnDelete();
            $table->string('konu', 180);
            $table->string('tip', 60)->default('genel');
            $table->enum('durum', ['beklemede', 'devam', 'tamamlandi'])->default('beklemede');
            $table->dateTime('son_tarih')->nullable();
            $table->dateTime('tamamlandi_at')->nullable();
            $table->text('aciklama')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('crm_notes');
        Schema::dropIfExists('crm_opportunities');
        Schema::dropIfExists('crm_customers');
        Schema::dropIfExists('crm_stages');
        Schema::dropIfExists('crm_pipelines');
    }
};

