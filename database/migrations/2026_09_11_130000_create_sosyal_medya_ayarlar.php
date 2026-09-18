<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SOSYAL MEDYA MODÜLÜ AYARLARI (anahtar/değer)
 *
 * İlk kullanıcısı: gün sonu raporunun gideceği saat. Tek bir ayar için
 * kolon açmak yerine anahtar/değer tablosu tercih edildi — modüle ileride
 * eklenecek ayarlar (geriye işaretleme sınırı, rapor hafta sonu gitsin mi
 * gibi) aynı tabloya girsin, her seferinde migration yazılmasın.
 *
 * MOTOR: InnoDB açıkça yazılı — bu sunucunun varsayılanı MyISAM.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sosyal_medya_ayarlar')) {
            return;
        }

        Schema::create('sosyal_medya_ayarlar', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->string('anahtar', 100)->unique();
            $t->string('deger', 255)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sosyal_medya_ayarlar');
    }
};
