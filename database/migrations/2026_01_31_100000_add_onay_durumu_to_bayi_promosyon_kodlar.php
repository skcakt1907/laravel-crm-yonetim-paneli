<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Bayi promosyon kodlar tablosuna onay durumu ekle
        if (Schema::hasTable('bayi_promosyon_kodlar')) {
            Schema::table('bayi_promosyon_kodlar', function (Blueprint $table) {
                if (!Schema::hasColumn('bayi_promosyon_kodlar', 'onay_durumu')) {
                    $table->tinyInteger('onay_durumu')->default(0)->after('durum')
                        ->comment('0: Beklemede, 1: Onaylandi, 2: Reddedildi');
                }
                if (!Schema::hasColumn('bayi_promosyon_kodlar', 'onay_tarihi')) {
                    $table->timestamp('onay_tarihi')->nullable()->after('onay_durumu');
                }
                if (!Schema::hasColumn('bayi_promosyon_kodlar', 'red_nedeni')) {
                    $table->text('red_nedeni')->nullable()->after('onay_tarihi');
                }
            });
        }
        
        // Admin bildirimleri tablosu (promo kod onayları için)
        if (!Schema::hasTable('admin_bildirimler')) {
            Schema::create('admin_bildirimler', function (Blueprint $table) {
                $table->id();
                $table->string('tip', 50); // promo_kod_onay, bayi_basvuru, vs.
                $table->string('baslik');
                $table->text('mesaj');
                $table->unsignedBigInteger('ilgili_id')->nullable(); // İlgili kayıt ID
                $table->string('ilgili_tablo', 100)->nullable(); // İlgili tablo adı
                $table->boolean('okundu')->default(0);
                $table->timestamp('okunma_tarihi')->nullable();
                $table->timestamps();
                
                $table->index('tip');
                $table->index('okundu');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('admin_bildirimler');
        
        if (Schema::hasTable('bayi_promosyon_kodlar')) {
            Schema::table('bayi_promosyon_kodlar', function (Blueprint $table) {
                if (Schema::hasColumn('bayi_promosyon_kodlar', 'onay_durumu')) {
                    $table->dropColumn('onay_durumu');
                }
                if (Schema::hasColumn('bayi_promosyon_kodlar', 'onay_tarihi')) {
                    $table->dropColumn('onay_tarihi');
                }
                if (Schema::hasColumn('bayi_promosyon_kodlar', 'red_nedeni')) {
                    $table->dropColumn('red_nedeni');
                }
            });
        }
    }
};
