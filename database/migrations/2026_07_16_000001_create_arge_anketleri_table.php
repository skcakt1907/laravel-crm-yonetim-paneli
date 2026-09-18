<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ar-Ge Anketi (Rubito) — ornek.com/arge-anketi'nin İş Ortağım'a taşınmış hâli.
 * Typeform yerine native form; cevaplar burada tutulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('arge_anketleri')) {
            return;
        }
        Schema::create('arge_anketleri', function (Blueprint $table) {
            $table->id();
            // Kişisel
            $table->string('ad_soyad', 190);
            $table->string('email', 190);
            $table->string('telefon', 40)->nullable();
            $table->date('dogum_tarihi')->nullable();
            // Marka
            $table->text('marka_guclu')->nullable();
            $table->text('marka_gelistir')->nullable();
            $table->string('geri_bildirim', 10)->nullable();      // evet/hayir
            $table->text('rakipler')->nullable();
            $table->text('rakip_kampanya')->nullable();
            // Ajans
            $table->string('ajans_calisti', 10)->nullable();      // evet/hayir
            $table->text('ajans_katki')->nullable();
            $table->text('ajans_beklenti')->nullable();
            $table->string('duydu_mu', 10)->nullable();           // evet/hayir
            $table->text('butce')->nullable();
            // Meta
            $table->string('ip', 45)->nullable();
            $table->tinyInteger('okundu')->default(0);            // admin okudu mu
            $table->timestamps();

            $table->index('created_at');
            $table->index('okundu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arge_anketleri');
    }
};
