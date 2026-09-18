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
        if (!Schema::hasTable('kampanyalar')) {
            Schema::create('kampanyalar', function (Blueprint $table) {
            $table->id();
            $table->string('baslik', 255);
            $table->text('aciklama')->nullable();
            $table->string('resim', 255)->nullable();
            $table->decimal('indirim', 5, 2)->default(0)->comment('Yüzde indirim');
            $table->string('link', 500)->nullable()->comment('Kampanya linki');
            $table->date('baslangic_tarihi')->nullable();
            $table->date('bitis_tarihi')->nullable();
            $table->integer('sira')->default(0);
            $table->tinyInteger('durum')->default(1)->comment('1: Aktif, 0: Pasif');
            $table->string('seo', 191)->nullable()->unique();
            $table->unsignedBigInteger('bayi_id')->nullable()->comment('Bayi kampanyası ise bayi_id');
            $table->timestamps();
            
            $table->index('durum');
            $table->index('seo');
            $table->index('bayi_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kampanyalar');
    }
};
