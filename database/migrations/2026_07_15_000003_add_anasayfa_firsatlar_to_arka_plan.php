<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * arka_plan tablosuna eksik iki sutun.
 *
 * Admin (AyarlarController@arkaplanPost) 'anasayfa' ve 'firsatlar' arkaplani
 * yuklemeyi zaten destekliyor ve `arka_plan` tablosunda o sutunlari ariyor;
 * ama sutunlar hic olusturulmamis -> o iki alandan yukleme yapilamiyordu.
 *
 * anasayfa: yeni hero'nun arka plan gorseli (bos = koyu marka gradyani kullanilir)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('arka_plan')) {
            return;
        }
        Schema::table('arka_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('arka_plan', 'anasayfa')) {
                $table->string('anasayfa', 190)->nullable()->after('id');
            }
            if (!Schema::hasColumn('arka_plan', 'firsatlar')) {
                $table->string('firsatlar', 190)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('arka_plan')) {
            return;
        }
        Schema::table('arka_plan', function (Blueprint $table) {
            foreach (['anasayfa', 'firsatlar'] as $c) {
                if (Schema::hasColumn('arka_plan', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
