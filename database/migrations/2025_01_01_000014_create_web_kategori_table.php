<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('web_kategori')) {
            Schema::create('web_kategori', function (Blueprint $table) {
                $table->id();
                $table->string('adi');
                $table->text('aciklama')->nullable();
                $table->string('seo')->nullable()->unique();
                $table->integer('sira')->default(0);
                $table->boolean('durum')->default(1);
                $table->tinyInteger('dil')->default(1);
                $table->timestamps();
                
                $table->index('seo');
                $table->index('durum');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('web_kategori');
    }
};
