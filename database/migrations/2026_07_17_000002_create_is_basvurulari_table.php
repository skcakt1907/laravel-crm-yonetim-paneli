<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş Başvurusu (Rubito) — ornek.com/is-basvurusu'nun İş Ortağım'a native hâli.
 * Typeform tarzı adım-adım form; başvurular burada tutulur, CV dosyası public/uploads/basvuru_cv'de.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('is_basvurulari')) {
            return;
        }
        Schema::create('is_basvurulari', function (Blueprint $table) {
            $table->id();
            $table->string('ad_soyad', 190);
            $table->string('email', 190);
            $table->string('telefon', 40)->nullable();
            $table->string('calisma_durumu', 60)->nullable();
            $table->string('egitim_duzeyi', 60)->nullable();
            $table->string('pozisyon', 80)->nullable();
            $table->unsignedSmallInteger('deneyim_yili')->default(0);
            $table->string('lokasyon', 40)->nullable();
            $table->string('dil', 40)->nullable();
            $table->string('maas_beklenti', 190)->nullable();
            $table->string('cv_dosya', 255)->nullable();
            $table->text('ek_bilgi')->nullable();
            $table->string('ip', 45)->nullable();
            $table->tinyInteger('okundu')->default(0);
            $table->timestamps();

            $table->index('created_at');
            $table->index('okundu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('is_basvurulari');
    }
};
