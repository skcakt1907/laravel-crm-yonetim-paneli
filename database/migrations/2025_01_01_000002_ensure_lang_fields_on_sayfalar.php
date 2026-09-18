<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sayfalar', function (Blueprint $table) {
            if (!Schema::hasColumn('sayfalar', 'adi_en')) {
                $table->string('adi_en')->nullable()->after('adi');
            }
            if (!Schema::hasColumn('sayfalar', 'adi_ar')) {
                $table->string('adi_ar')->nullable()->after('adi_en');
            }
            if (!Schema::hasColumn('sayfalar', 'kisa_en')) {
                $table->text('kisa_en')->nullable()->after('kisa');
            }
            if (!Schema::hasColumn('sayfalar', 'kisa_ar')) {
                $table->text('kisa_ar')->nullable()->after('kisa_en');
            }
            if (!Schema::hasColumn('sayfalar', 'aciklama_en')) {
                $table->longText('aciklama_en')->nullable()->after('aciklama');
            }
            if (!Schema::hasColumn('sayfalar', 'aciklama_ar')) {
                $table->longText('aciklama_ar')->nullable()->after('aciklama_en');
            }
            if (!Schema::hasColumn('sayfalar', 'keywords_en')) {
                $table->string('keywords_en')->nullable()->after('keywords');
            }
            if (!Schema::hasColumn('sayfalar', 'keywords_ar')) {
                $table->string('keywords_ar')->nullable()->after('keywords_en');
            }
            if (!Schema::hasColumn('sayfalar', 'description_en')) {
                $table->string('description_en')->nullable()->after('description');
            }
            if (!Schema::hasColumn('sayfalar', 'description_ar')) {
                $table->string('description_ar')->nullable()->after('description_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sayfalar', function (Blueprint $table) {
            $dropColumns = [];
            foreach ([
                'adi_en', 'adi_ar',
                'kisa_en', 'kisa_ar',
                'aciklama_en', 'aciklama_ar',
                'keywords_en', 'keywords_ar',
                'description_en', 'description_ar',
            ] as $col) {
                if (Schema::hasColumn('sayfalar', $col)) {
                    $dropColumns[] = $col;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};












