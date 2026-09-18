<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('krediler')) {
            Schema::create('krediler', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('uye_id')->index();
                $table->unsignedBigInteger('musteri_id')->nullable()->index()->comment('crm_customers.id');
                $table->string('kredi_no', 30)->unique()->comment('KRD-2026-XXXX');
                $table->decimal('ana_para', 14, 2);
                $table->decimal('faiz_orani', 6, 3)->default(0)->comment('aylık % — örn 1.50 = aylık %1.5');
                $table->unsignedSmallInteger('vade_ay');
                $table->decimal('aylik_taksit', 14, 2);
                $table->decimal('toplam_geri_odeme', 14, 2);
                $table->decimal('odenen_tutar', 14, 2)->default(0);
                $table->decimal('kalan_borc', 14, 2);
                $table->date('baslangic_tarihi');
                $table->date('bitis_tarihi');
                $table->enum('durum', ['aktif', 'kapandi', 'gecikmede', 'iptal'])->default('aktif')->index();
                $table->text('aciklama')->nullable();
                $table->unsignedBigInteger('olusturan_id')->nullable()->comment('yoneticiler.id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kredi_taksitleri')) {
            Schema::create('kredi_taksitleri', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kredi_id')->index();
                $table->unsignedSmallInteger('sira')->comment('1..vade_ay');
                $table->date('vade_tarihi');
                $table->decimal('taksit_tutari', 14, 2);
                $table->decimal('odenen_tutar', 14, 2)->default(0);
                $table->date('odeme_tarihi')->nullable();
                $table->enum('durum', ['bekliyor', 'odendi', 'gecikti', 'kismi'])->default('bekliyor')->index();
                $table->text('not')->nullable();
                $table->timestamps();

                $table->unique(['kredi_id', 'sira']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kredi_taksitleri');
        Schema::dropIfExists('krediler');
    }
};
