<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bayi kodları ile satış yapıldığında:
     * - Müşteri "musteri_indirim_orani" kadar indirim alır
     * - Bayi "komisyon_orani" kadar komisyon kazanır
     */
    public function up(): void
    {
        // Bayiler tablosuna müşteri indirim oranı ekle
        Schema::table('bayiler', function (Blueprint $table) {
            if (!Schema::hasColumn('bayiler', 'musteri_indirim_orani')) {
                $table->decimal('musteri_indirim_orani', 5, 2)->default(5.00)->after('komisyon_orani'); // Müşteriye %5 indirim
            }
            if (!Schema::hasColumn('bayiler', 'min_sepet_tutari')) {
                $table->decimal('min_sepet_tutari', 15, 2)->default(0)->after('musteri_indirim_orani'); // Minimum sepet tutarı
            }
        });
        
        // Bayi satışları tablosuna indirim bilgisi ekle
        Schema::table('bayi_satislar', function (Blueprint $table) {
            if (!Schema::hasColumn('bayi_satislar', 'musteri_indirim_tutari')) {
                $table->decimal('musteri_indirim_tutari', 15, 2)->default(0)->after('komisyon_tutari');
            }
            if (!Schema::hasColumn('bayi_satislar', 'uye_id')) {
                $table->unsignedBigInteger('uye_id')->nullable()->after('bayi_id');
            }
        });
        
        // Sepet tablosuna bayi kodu bilgisi ekle
        Schema::table('sepet', function (Blueprint $table) {
            if (!Schema::hasColumn('sepet', 'bayi_kodu')) {
                $table->string('bayi_kodu', 50)->nullable();
            }
        });
        
        // Genel bayi ayarları tablosu (admin panelden yönetim için)
        if (!Schema::hasTable('bayi_ayarlari')) {
            Schema::create('bayi_ayarlari', function (Blueprint $table) {
                $table->id();
                $table->decimal('varsayilan_komisyon_orani', 5, 2)->default(10.00); // %10 komisyon
                $table->decimal('varsayilan_musteri_indirim_orani', 5, 2)->default(5.00); // %5 indirim
                $table->decimal('min_sepet_tutari', 15, 2)->default(0); // Minimum sepet tutarı
                $table->decimal('max_musteri_indirim', 15, 2)->default(500); // Maksimum indirim tutarı
                $table->boolean('bayi_sistemi_aktif')->default(true);
                $table->boolean('yeni_bayi_otomatik_onay')->default(false);
                $table->text('kullanim_sartlari')->nullable(); // Kullanım şartları
                $table->timestamps();
            });
            
            // Varsayılan ayarları ekle
            DB::table('bayi_ayarlari')->insert([
                'varsayilan_komisyon_orani' => 10.00,
                'varsayilan_musteri_indirim_orani' => 5.00,
                'min_sepet_tutari' => 100,
                'max_musteri_indirim' => 500,
                'bayi_sistemi_aktif' => true,
                'yeni_bayi_otomatik_onay' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('bayiler', function (Blueprint $table) {
            if (Schema::hasColumn('bayiler', 'musteri_indirim_orani')) {
                $table->dropColumn('musteri_indirim_orani');
            }
            if (Schema::hasColumn('bayiler', 'min_sepet_tutari')) {
                $table->dropColumn('min_sepet_tutari');
            }
        });
        
        Schema::table('bayi_satislar', function (Blueprint $table) {
            if (Schema::hasColumn('bayi_satislar', 'musteri_indirim_tutari')) {
                $table->dropColumn('musteri_indirim_tutari');
            }
            if (Schema::hasColumn('bayi_satislar', 'uye_id')) {
                $table->dropColumn('uye_id');
            }
        });
        
        Schema::table('sepet', function (Blueprint $table) {
            if (Schema::hasColumn('sepet', 'bayi_kodu')) {
                $table->dropColumn('bayi_kodu');
            }
        });
        
        Schema::dropIfExists('bayi_ayarlari');
    }
};
