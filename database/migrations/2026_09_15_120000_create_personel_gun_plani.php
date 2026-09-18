<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GÜN PLANI — saatli program.
 *
 * Her satır bir iş: 09:00–11:00 "Vidal Dent çekimi" gibi. Kişi kendi
 * gününü planlar; yöneticiler herkesinkini görür ve gerekirse düzenler.
 *
 * DURUM BİLDİRİMİYLE BAĞI YOK (bilinçli karar): plandaki saat gelince
 * durum otomatik değişmiyor. Plan sapabilir; otomatik değişseydi pano
 * yanlış bilgi gösterir ve o yanlış bilgi 3 yöneticiye mail olarak
 * giderdi. Durumu kişi kendi değiştiriyor.
 *
 * BITIS NEDEN NULLABLE: "14:00 kurgu" gibi bitişi belirsiz işler var;
 * saat zorunlu tutulursa insanlar uydurma bitiş yazar.
 *
 * MOTOR: InnoDB açıkça yazılı — bu sunucunun varsayılanı MyISAM.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personel_gun_plani')) {
            return;
        }

        Schema::create('personel_gun_plani', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('yonetici_id');
            $t->date('tarih');
            $t->time('baslangic');
            $t->time('bitis')->nullable();
            $t->string('baslik', 160);
            $t->text('aciklama')->nullable();
            $t->boolean('tamamlandi')->default(false);

            // Satırı kim yazdı — kendisi mi, yönetici mi
            $t->unsignedBigInteger('olusturan_id')->nullable();

            $t->timestamps();

            // Gün ekranının ana sorgusu: "şu tarihte herkesin planı"
            $t->index(['tarih', 'yonetici_id']);
            $t->index(['yonetici_id', 'tarih', 'baslangic']);
        });

        // NOT: yoneticiler tablosu MyISAM oldugu icin foreign key yok.
    }

    public function down(): void
    {
        Schema::dropIfExists('personel_gun_plani');
    }
};
