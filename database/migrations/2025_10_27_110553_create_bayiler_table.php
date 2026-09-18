<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bayiler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uye_id')->unique();
            $table->string('bayi_kodu', 20)->unique();
            $table->decimal('komisyon_orani', 5, 2)->default(10.00); // %10
            $table->decimal('toplam_kazanc', 15, 2)->default(0);
            $table->decimal('cekilebilir_bakiye', 15, 2)->default(0);
            $table->decimal('cekilen_toplam', 15, 2)->default(0);
            $table->text('adres')->nullable();
            $table->text('adres_tarifi')->nullable();
            $table->string('banka_adi')->nullable();
            $table->string('iban')->nullable();
            $table->string('hesap_sahibi')->nullable();
            $table->string('vergi_no')->nullable();
            $table->string('vergi_dairesi')->nullable();
            $table->boolean('durum')->default(1);
            $table->boolean('onay_durumu')->default(0); // Admin onayı
            $table->timestamp('onay_tarihi')->nullable();
            $table->timestamps();
            
            $table->foreign('uye_id')->references('id')->on('uyeler')->onDelete('cascade');
        });
        
        // Bayi satışları tablosu
        Schema::create('bayi_satislar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->unsignedBigInteger('fatura_id');
            $table->decimal('satis_tutari', 15, 2);
            $table->decimal('komisyon_orani', 5, 2);
            $table->decimal('komisyon_tutari', 15, 2);
            $table->boolean('odendi')->default(0);
            $table->timestamp('odeme_tarihi')->nullable();
            $table->timestamps();
            
            $table->foreign('bayi_id')->references('id')->on('bayiler')->onDelete('cascade');
        });
        
        // Bayi ödeme talepleri
        Schema::create('bayi_odeme_talepleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->decimal('talep_tutari', 15, 2);
            $table->string('durum')->default('beklemede'); // beklemede, onaylandi, reddedildi
            $table->text('aciklama')->nullable();
            $table->text('red_nedeni')->nullable();
            $table->timestamp('onay_tarihi')->nullable();
            $table->unsignedBigInteger('onaylayan_admin_id')->nullable();
            $table->timestamps();
            
            $table->foreign('bayi_id')->references('id')->on('bayiler')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bayi_odeme_talepleri');
        Schema::dropIfExists('bayi_satislar');
        Schema::dropIfExists('bayiler');
    }
};
