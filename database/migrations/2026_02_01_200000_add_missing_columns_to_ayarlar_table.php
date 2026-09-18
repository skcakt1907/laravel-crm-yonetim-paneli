<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ayarlar', function (Blueprint $table) {
            // Mail Ayarları
            if (!Schema::hasColumn('ayarlar', 'mail_host')) {
                $table->string('mail_host', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'mail_port')) {
                $table->string('mail_port', 10)->nullable()->default('587');
            }
            if (!Schema::hasColumn('ayarlar', 'mail_username')) {
                $table->string('mail_username', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'mail_password')) {
                $table->string('mail_password', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'mail_encryption')) {
                $table->string('mail_encryption', 10)->nullable()->default('tls');
            }
            if (!Schema::hasColumn('ayarlar', 'mail_from_address')) {
                $table->string('mail_from_address', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'mail_from_name')) {
                $table->string('mail_from_name', 255)->nullable();
            }
            
            // SMS Ayarları
            if (!Schema::hasColumn('ayarlar', 'sms_baslik')) {
                $table->string('sms_baslik', 100)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'sms_aktif')) {
                $table->boolean('sms_aktif')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'sms_kayit')) {
                $table->boolean('sms_kayit')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'sms_siparis')) {
                $table->boolean('sms_siparis')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'sms_odeme')) {
                $table->boolean('sms_odeme')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'sms_destek')) {
                $table->boolean('sms_destek')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'netgsm_kullanici')) {
                $table->string('netgsm_kullanici', 100)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'netgsm_sifre')) {
                $table->string('netgsm_sifre', 100)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'netgsm_baslik')) {
                $table->string('netgsm_baslik', 50)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'mutlucell_kullanici')) {
                $table->string('mutlucell_kullanici', 100)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'mutlucell_sifre')) {
                $table->string('mutlucell_sifre', 100)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'mutlucell_baslik')) {
                $table->string('mutlucell_baslik', 50)->nullable();
            }
            
            // Sanal POS Ayarları
            if (!Schema::hasColumn('ayarlar', 'iyzico_aktif')) {
                $table->boolean('iyzico_aktif')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'paytr_aktif')) {
                $table->boolean('paytr_aktif')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'paytr_merchant_id')) {
                $table->string('paytr_merchant_id', 100)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'paytr_merchant_key')) {
                $table->string('paytr_merchant_key', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'paytr_merchant_salt')) {
                $table->string('paytr_merchant_salt', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'paytr_test_mode')) {
                $table->boolean('paytr_test_mode')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'havale_aktif')) {
                $table->boolean('havale_aktif')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'bakiye_odeme_aktif')) {
                $table->boolean('bakiye_odeme_aktif')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'min_bakiye_yukleme')) {
                $table->decimal('min_bakiye_yukleme', 10, 2)->default(50);
            }
            if (!Schema::hasColumn('ayarlar', 'para_birimi')) {
                $table->string('para_birimi', 10)->default('TRY');
            }
            
            // API Ayarları
            if (!Schema::hasColumn('ayarlar', 'domain_api_provider')) {
                $table->string('domain_api_provider', 50)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'domain_api_url')) {
                $table->string('domain_api_url', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'domain_api_key')) {
                $table->string('domain_api_key', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'domain_api_secret')) {
                $table->string('domain_api_secret', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'domain_api_test')) {
                $table->boolean('domain_api_test')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'whmcs_url')) {
                $table->string('whmcs_url', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'whmcs_identifier')) {
                $table->string('whmcs_identifier', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'whmcs_secret')) {
                $table->string('whmcs_secret', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'whmcs_accesskey')) {
                $table->string('whmcs_accesskey', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'whmcs_aktif')) {
                $table->boolean('whmcs_aktif')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'hosting_api_type')) {
                $table->string('hosting_api_type', 50)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'hosting_api_url')) {
                $table->string('hosting_api_url', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'hosting_api_user')) {
                $table->string('hosting_api_user', 100)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'hosting_api_token')) {
                $table->text('hosting_api_token')->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'ssl_provider')) {
                $table->string('ssl_provider', 50)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'ssl_api_key')) {
                $table->string('ssl_api_key', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'ssl_api_partner')) {
                $table->string('ssl_api_partner', 100)->nullable();
            }
            
            // Bakım Modu Ayarları
            if (!Schema::hasColumn('ayarlar', 'bakim_modu')) {
                $table->boolean('bakim_modu')->default(false);
            }
            if (!Schema::hasColumn('ayarlar', 'bakim_baslik')) {
                $table->string('bakim_baslik', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'bakim_mesaj')) {
                $table->text('bakim_mesaj')->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'bakim_bitis_tarihi')) {
                $table->timestamp('bakim_bitis_tarihi')->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'bakim_email')) {
                $table->string('bakim_email', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'bakim_beyaz_liste')) {
                $table->text('bakim_beyaz_liste')->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'bakim_admin_erisim')) {
                $table->boolean('bakim_admin_erisim')->default(true);
            }
            
            // Modül Ayarları
            if (!Schema::hasColumn('ayarlar', 'modul_web_paket')) {
                $table->boolean('modul_web_paket')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_hosting')) {
                $table->boolean('modul_hosting')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_domain')) {
                $table->boolean('modul_domain')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_ssl')) {
                $table->boolean('modul_ssl')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_bayi')) {
                $table->boolean('modul_bayi')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_blog')) {
                $table->boolean('modul_blog')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_referans')) {
                $table->boolean('modul_referans')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_firsat')) {
                $table->boolean('modul_firsat')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_destek')) {
                $table->boolean('modul_destek')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_canli_destek')) {
                $table->boolean('modul_canli_destek')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_bakiye')) {
                $table->boolean('modul_bakiye')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_kupon')) {
                $table->boolean('modul_kupon')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_havale')) {
                $table->boolean('modul_havale')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_coklu_dil')) {
                $table->boolean('modul_coklu_dil')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_coklu_para')) {
                $table->boolean('modul_coklu_para')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_email_bildirim')) {
                $table->boolean('modul_email_bildirim')->default(true);
            }
            if (!Schema::hasColumn('ayarlar', 'modul_sms_bildirim')) {
                $table->boolean('modul_sms_bildirim')->default(false);
            }
            
            // Limit Ayarları
            if (!Schema::hasColumn('ayarlar', 'limit_paket_anasayfa')) {
                $table->integer('limit_paket_anasayfa')->default(6);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_paket_liste')) {
                $table->integer('limit_paket_liste')->default(12);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_blog_anasayfa')) {
                $table->integer('limit_blog_anasayfa')->default(3);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_blog_liste')) {
                $table->integer('limit_blog_liste')->default(10);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_referans_anasayfa')) {
                $table->integer('limit_referans_anasayfa')->default(6);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_admin_tablo')) {
                $table->integer('limit_admin_tablo')->default(25);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_dashboard_son_islem')) {
                $table->integer('limit_dashboard_son_islem')->default(5);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_bildirim')) {
                $table->integer('limit_bildirim')->default(10);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_resim_boyut')) {
                $table->integer('limit_resim_boyut')->default(5);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_dosya_boyut')) {
                $table->integer('limit_dosya_boyut')->default(10);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_izinli_uzantilar')) {
                $table->string('limit_izinli_uzantilar', 255)->default('jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx');
            }
            if (!Schema::hasColumn('ayarlar', 'limit_giris_deneme')) {
                $table->integer('limit_giris_deneme')->default(5);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_kilit_suresi')) {
                $table->integer('limit_kilit_suresi')->default(15);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_session_suresi')) {
                $table->integer('limit_session_suresi')->default(120);
            }
            if (!Schema::hasColumn('ayarlar', 'limit_api_rate')) {
                $table->integer('limit_api_rate')->default(60);
            }
            
            // Sosyal Medya Ek
            if (!Schema::hasColumn('ayarlar', 'tiktok')) {
                $table->string('tiktok', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'pinterest')) {
                $table->string('pinterest', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'telegram')) {
                $table->string('telegram', 255)->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'discord')) {
                $table->string('discord', 255)->nullable();
            }
            
            // Timestamps
            if (!Schema::hasColumn('ayarlar', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('ayarlar', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Kolonları silmiyoruz, veri kaybı olmasın
    }
};
