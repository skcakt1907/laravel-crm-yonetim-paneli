<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sayfalar', function (Blueprint $table) {
            $table->string('adi_en')->nullable()->after('adi');
            $table->string('adi_ar')->nullable()->after('adi_en');
            $table->text('kisa_en')->nullable()->after('kisa');
            $table->text('kisa_ar')->nullable()->after('kisa_en');
            $table->longText('aciklama_en')->nullable()->after('aciklama');
            $table->longText('aciklama_ar')->nullable()->after('aciklama_en');
            $table->string('keywords_en')->nullable()->after('keywords');
            $table->string('keywords_ar')->nullable()->after('keywords_en');
            $table->string('description_en')->nullable()->after('description');
            $table->string('description_ar')->nullable()->after('description_en');
        });
    }

    public function down(): void
    {
        Schema::table('sayfalar', function (Blueprint $table) {
            $table->dropColumn([
                'adi_en',
                'adi_ar',
                'kisa_en',
                'kisa_ar',
                'aciklama_en',
                'aciklama_ar',
                'keywords_en',
                'keywords_ar',
                'description_en',
                'description_ar',
            ]);
        });
    }
};












