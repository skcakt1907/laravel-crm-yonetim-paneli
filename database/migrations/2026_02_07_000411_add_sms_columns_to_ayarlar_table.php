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
        Schema::table('ayarlar', function (Blueprint $table) {
            // SMS Post URL
            if (!Schema::hasColumn('ayarlar', 'sms_post_url')) {
                $table->string('sms_post_url', 255)->nullable()->after('defaultsms');
            }
            
            // SMS Kullanıcı Adı
            if (!Schema::hasColumn('ayarlar', 'sms_kullanici_adi')) {
                $table->string('sms_kullanici_adi', 100)->nullable()->after('sms_post_url');
            }
            
            // SMS Şifre
            if (!Schema::hasColumn('ayarlar', 'sms_sifre')) {
                $table->string('sms_sifre', 100)->nullable()->after('sms_kullanici_adi');
            }
            
            // SMS Başlık
            if (!Schema::hasColumn('ayarlar', 'sms_baslik')) {
                $table->string('sms_baslik', 100)->nullable()->after('sms_sifre');
            }
            
            // SMS Test Telefon
            if (!Schema::hasColumn('ayarlar', 'sms_test_telefon')) {
                $table->string('sms_test_telefon', 20)->nullable()->after('sms_baslik');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ayarlar', function (Blueprint $table) {
            if (Schema::hasColumn('ayarlar', 'sms_post_url')) {
                $table->dropColumn('sms_post_url');
            }
            if (Schema::hasColumn('ayarlar', 'sms_kullanici_adi')) {
                $table->dropColumn('sms_kullanici_adi');
            }
            if (Schema::hasColumn('ayarlar', 'sms_sifre')) {
                $table->dropColumn('sms_sifre');
            }
            if (Schema::hasColumn('ayarlar', 'sms_baslik')) {
                $table->dropColumn('sms_baslik');
            }
            if (Schema::hasColumn('ayarlar', 'sms_test_telefon')) {
                $table->dropColumn('sms_test_telefon');
            }
        });
    }
};
