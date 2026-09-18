<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hostingler')) {
            Schema::create('hostingler', function (Blueprint $table) {
                $table->id();
                $table->string('adi');
                $table->text('aciklama')->nullable();
                $table->string('disk_alani')->nullable();
                $table->string('trafik')->nullable();
                $table->string('veritabani')->nullable();
                $table->string('email')->nullable();
                $table->decimal('fiyat', 10, 2)->default(0);
                $table->string('resim')->nullable();
                $table->string('seo')->nullable()->unique();
                $table->unsignedBigInteger('kategori')->nullable();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->boolean('durum')->default(1);
                $table->boolean('anasayfa')->default(0);
                $table->integer('sira')->default(0);
                $table->tinyInteger('dil')->default(1);
                $table->timestamps();
                
                $table->index('seo');
                $table->index('durum');
                $table->index('kategori');
                $table->index('kategori_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hostingler');
    }
};
