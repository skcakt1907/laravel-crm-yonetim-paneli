<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TARİHE ÖZEL PAYLAŞIM KALEMLERİ — "Vidal Dent 30 Ağustos postu"
 *
 * NEDEN AYRI TABLO:
 * Mevcut plan yapısı yalnızca SAYI tutuyordu ("salı günü 3 paylaşım").
 * Kullanıcı her paylaşımın adını da girmek istedi ("Reels: klinik tanıtım",
 * "Story: hasta yorumu"). Bu, haftalık şablona sığmaz — şablon her hafta
 * tekrar eder, isimler ise TARİHE özeldir.
 *
 * HEDEF HESABINDAKİ YERİ (SosyalMedyaTakip::hedef):
 *   1. O tarihe kalem girilmişse  -> hedef = kalem sayısı   (en öncelikli)
 *   2. Yoksa tarihe özel istisna  -> hedef = istisna adedi
 *   3. O da yoksa haftalık şablon -> hedef = şablon adedi
 * Yani kalem girmek, o günü otomatik olarak o kadar paylaşıma ayarlar;
 * ayrıca sayı girmeye gerek kalmaz.
 *
 * MOTOR: InnoDB açıkça yazılı — bu sunucunun varsayılanı MyISAM ve orada
 * foreign key SESSİZCE yok sayılıyor (bu modülde bir kez yaşandı).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sosyal_medya_plan_kalemleri')) {
            return;
        }

        Schema::create('sosyal_medya_plan_kalemleri', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('hesap_id');
            $t->date('tarih');
            $t->string('baslik', 255);
            $t->unsignedSmallInteger('sira')->default(0);
            $t->unsignedBigInteger('olusturan_id')->nullable();
            $t->timestamps();

            // Aynı gün aynı başlık iki kez girilmesin (yazım hatası/çift tıklama)
            $t->unique(['hesap_id', 'tarih', 'baslik'], 'sm_kalem_benzersiz');
            $t->index(['hesap_id', 'tarih'], 'sm_kalem_hesap_tarih');
            $t->index('tarih');

            $t->foreign('hesap_id')->references('id')
              ->on('sosyal_medya_hesaplari')->onDelete('cascade');
        });

        // Bir paylaşım hangi kaleme ait — boş olabilir (kalemsiz gün, eski kayıtlar)
        if (Schema::hasTable('sosyal_medya_paylasimlar')
            && ! Schema::hasColumn('sosyal_medya_paylasimlar', 'kalem_id')) {
            Schema::table('sosyal_medya_paylasimlar', function (Blueprint $t) {
                $t->unsignedBigInteger('kalem_id')->nullable()->after('tarih');
                $t->index('kalem_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sosyal_medya_paylasimlar', 'kalem_id')) {
            Schema::table('sosyal_medya_paylasimlar', function (Blueprint $t) {
                $t->dropIndex(['kalem_id']);
                $t->dropColumn('kalem_id');
            });
        }

        Schema::dropIfExists('sosyal_medya_plan_kalemleri');
    }
};
