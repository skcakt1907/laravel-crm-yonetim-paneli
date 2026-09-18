<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GÜN SONU RAPORU ALICILARI
 *
 * Alıcı listesi kodda sabitti (SosyalMedyaRaporu::ALICILAR); istek listesinde
 * "mail gönderilecek yönetim e-postalarını panelden yapılandırma" maddesi
 * vardı. Artık panelden seçiliyor.
 *
 * TABLO BOŞSA koddaki liste kullanılır — böylece kurulumdan hemen sonra,
 * henüz kimse seçim yapmadan da rapor gitmeye devam eder. "Hiç kimseye
 * gitmesin" demek isteyen, listeyi boş bırakamaz; bunun yerine raporu
 * kapatmak için zamanlayıcıdan çıkarılması gerekir (bilinçli tercih:
 * yanlışlıkla boş bırakıp raporun sessizce kesilmesi daha kötü).
 *
 * MOTOR: InnoDB açıkça yazılı — bu sunucunun varsayılanı MyISAM ve orada
 * foreign key sessizce yok sayılıyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sosyal_medya_rapor_alicilari')) {
            return;
        }

        Schema::create('sosyal_medya_rapor_alicilari', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('yonetici_id');
            $t->timestamps();

            // Aynı kişi iki kez eklenemesin
            $t->unique('yonetici_id', 'sm_rapor_alici_benzersiz');
        });

        // `yoneticiler` MyISAM olduğu için FK kurulmuyor; silinen yönetici
        // burada artık satır bırakabilir. alicilar() sorgusu zaten
        // yoneticiler ile JOIN yaptığı için öksüz satır maile yol açmaz.
    }

    public function down(): void
    {
        Schema::dropIfExists('sosyal_medya_rapor_alicilari');
    }
};
