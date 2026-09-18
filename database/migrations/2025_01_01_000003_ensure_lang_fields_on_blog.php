<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blog', function (Blueprint $table) {
            if (!Schema::hasColumn('blog', 'baslik_en')) {
                $table->string('baslik_en')->nullable();
            }
            if (!Schema::hasColumn('blog', 'baslik_ar')) {
                $table->string('baslik_ar')->nullable();
            }
            if (!Schema::hasColumn('blog', 'ozet_en')) {
                $table->text('ozet_en')->nullable();
            }
            if (!Schema::hasColumn('blog', 'ozet_ar')) {
                $table->text('ozet_ar')->nullable();
            }
            if (!Schema::hasColumn('blog', 'icerik_en')) {
                $table->longText('icerik_en')->nullable();
            }
            if (!Schema::hasColumn('blog', 'icerik_ar')) {
                $table->longText('icerik_ar')->nullable();
            }
            if (!Schema::hasColumn('blog', 'seo_baslik_en')) {
                $table->string('seo_baslik_en')->nullable();
            }
            if (!Schema::hasColumn('blog', 'seo_baslik_ar')) {
                $table->string('seo_baslik_ar')->nullable();
            }
            if (!Schema::hasColumn('blog', 'seo_aciklama_en')) {
                $table->string('seo_aciklama_en')->nullable();
            }
            if (!Schema::hasColumn('blog', 'seo_aciklama_ar')) {
                $table->string('seo_aciklama_ar')->nullable();
            }
            if (!Schema::hasColumn('blog', 'seo_anahtar_en')) {
                $table->string('seo_anahtar_en')->nullable();
            }
            if (!Schema::hasColumn('blog', 'seo_anahtar_ar')) {
                $table->string('seo_anahtar_ar')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'baslik_en', 'baslik_ar',
                'ozet_en', 'ozet_ar',
                'icerik_en', 'icerik_ar',
                'seo_baslik_en', 'seo_baslik_ar',
                'seo_aciklama_en', 'seo_aciklama_ar',
                'seo_anahtar_en', 'seo_anahtar_ar',
            ] as $col) {
                if (Schema::hasColumn('blog', $col)) {
                    $drop[] = $col;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};












