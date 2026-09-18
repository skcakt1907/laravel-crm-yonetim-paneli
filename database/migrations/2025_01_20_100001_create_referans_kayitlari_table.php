<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('referans_kayitlari')) {
            Schema::create('referans_kayitlari', function (Blueprint $table) {
                $table->id();
                $table->string('bayi_kodu', 50)->index();
                $table->integer('bayi_id')->nullable()->index();
                $table->integer('uye_id')->index();
                $table->decimal('kazanc', 10, 2)->default(0);
                $table->tinyInteger('durum')->default(1); // 1: aktif, 0: pasif
                $table->timestamp('kayit_tarihi')->useCurrent();
                $table->timestamp('created_at')->useCurrent();
                
                $table->index(['bayi_id', 'kayit_tarihi']);
                $table->index(['uye_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('referans_kayitlari');
    }
};

