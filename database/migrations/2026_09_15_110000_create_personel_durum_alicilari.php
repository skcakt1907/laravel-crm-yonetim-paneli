<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DURUM BİLDİRİM MAİLİNİ KİMLER ALACAK
 *
 * Adresler koda gömülmüyor: Nurseli / Nesimi / Dilan kod içinde varsayılan
 * olarak duruyor ama panelden değiştirilebiliyor. Kişi değişince kod
 * değiştirmek gerekmesin diye.
 *
 * Tablo BOŞ kalırsa sistem koddaki varsayılan listeye döner — kurulumdan
 * hemen sonra da mail gitsin, seçim yapılmadı diye susmasın.
 *
 * Aynı desen sosyal medya raporunda da kullanılıyor
 * (sosyal_medya_rapor_alicilari).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personel_durum_alicilari')) {
            return;
        }

        Schema::create('personel_durum_alicilari', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('yonetici_id');
            $t->timestamps();

            $t->unique('yonetici_id', 'pd_alici_benzersiz');
        });

        // NOT: yoneticiler tablosu MyISAM oldugu icin foreign key yok.
    }

    public function down(): void
    {
        Schema::dropIfExists('personel_durum_alicilari');
    }
};
