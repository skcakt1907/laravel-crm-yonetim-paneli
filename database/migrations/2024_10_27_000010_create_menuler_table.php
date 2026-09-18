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
        if (!Schema::hasTable('menuler')) {
            Schema::create('menuler', function (Blueprint $table) {
                $table->id();
                $table->string('menu_isim')->nullable();
                $table->string('menu_url')->nullable();
                $table->string('link')->nullable();
                $table->string('adi')->nullable(); // Footer/Header için
                $table->string('tip')->nullable(); // header, footer, navbar
                $table->integer('ustid')->default(0);
                $table->integer('dil')->default(1);
                $table->integer('sira')->default(0);
                $table->boolean('durum')->default(true);
                $table->boolean('sekme')->default(false);
                $table->timestamps();
            });
        } else {
            // Eğer tablo varsa eksik kolonları ekle
            if (!Schema::hasColumn('menuler', 'tip')) {
                Schema::table('menuler', function (Blueprint $table) {
                    $table->string('tip')->nullable()->after('link');
                });
            }
            if (!Schema::hasColumn('menuler', 'adi')) {
                Schema::table('menuler', function (Blueprint $table) {
                    $table->string('adi')->nullable()->after('menu_isim');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menuler');
    }
};







