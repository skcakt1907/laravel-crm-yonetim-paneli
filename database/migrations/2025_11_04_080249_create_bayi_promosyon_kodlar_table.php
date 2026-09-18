<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bayi_promosyon_kodlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->string('kod', 50)->unique();
            $table->enum('indirim_tipi', ['yuzde', 'tutar']);
            $table->decimal('indirim_miktari', 10, 2);
            $table->integer('kullanim_limiti')->nullable();
            $table->integer('kullanim_sayisi')->default(0);
            $table->date('bitis_tarihi')->nullable();
            $table->boolean('durum')->default(1);
            $table->timestamps();
            
            $table->index('bayi_id');
            $table->index('kod');
        });
        
        Schema::create('bayi_destek_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->string('konu');
            $table->string('kategori', 50);
            $table->enum('oncelik', ['dusuk', 'normal', 'yuksek', 'acil'])->default('normal');
            $table->enum('durum', ['acik', 'cevaplandi', 'cozuldu', 'kapali'])->default('acik');
            $table->boolean('okundu')->default(0);
            $table->timestamps();
            
            $table->index('bayi_id');
            $table->index('durum');
        });
        
        Schema::create('bayi_destek_mesajlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('gonderen_id');
            $table->enum('gonderen_tip', ['bayi', 'admin']);
            $table->text('mesaj');
            $table->string('dosya')->nullable();
            $table->timestamps();
            
            $table->index('ticket_id');
        });
        
        Schema::create('bayi_bildirimler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->string('baslik');
            $table->text('mesaj');
            $table->string('tip', 50)->default('genel');
            $table->boolean('okundu')->default(0);
            $table->timestamp('okunma_tarihi')->nullable();
            $table->timestamps();
            
            $table->index('bayi_id');
            $table->index(['bayi_id', 'okundu']);
        });
        
        Schema::create('bayi_banka_hesaplari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->string('banka_adi');
            $table->string('hesap_sahibi');
            $table->string('iban', 26);
            $table->string('sube_kodu', 20)->nullable();
            $table->string('hesap_no', 50)->nullable();
            $table->boolean('varsayilan')->default(0);
            $table->timestamps();
            
            $table->index('bayi_id');
        });
        
        Schema::create('bayi_stoklar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->string('urun_adi');
            $table->integer('miktar')->default(0);
            $table->decimal('birim_fiyat', 10, 2)->nullable();
            $table->text('aciklama')->nullable();
            $table->timestamps();
            
            $table->index('bayi_id');
        });
        
        Schema::create('bayi_stok_talepleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->string('urun_adi');
            $table->integer('miktar');
            $table->text('aciklama')->nullable();
            $table->tinyInteger('durum')->default(0)->comment('0:Beklemede, 1:Onaylandı, 2:Reddedildi');
            $table->text('red_nedeni')->nullable();
            $table->timestamps();
            
            $table->index('bayi_id');
            $table->index('durum');
        });
        
        Schema::create('bayi_lisanslar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->string('urun_adi');
            $table->string('lisans_kodu')->unique();
            $table->string('aktivasyon_kodu')->nullable();
            $table->date('baslangic_tarihi');
            $table->date('bitis_tarihi')->nullable();
            $table->boolean('aktif')->default(1);
            $table->timestamps();
            
            $table->index('bayi_id');
            $table->index('lisans_kodu');
        });
        
        Schema::create('bayi_bildirim_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id')->unique();
            $table->boolean('yeni_satis_email')->default(1);
            $table->boolean('odeme_onay_email')->default(1);
            $table->boolean('sistem_duyuru_email')->default(1);
            $table->timestamps();
            
            $table->index('bayi_id');
        });
        
        Schema::create('bayi_oturum_gecmisi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bayi_id');
            $table->string('ip_adresi', 45);
            $table->string('tarayici')->nullable();
            $table->string('cihaz')->nullable();
            $table->timestamp('giris_tarihi');
            $table->timestamp('cikis_tarihi')->nullable();
            $table->timestamps();
            
            $table->index('bayi_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bayi_oturum_gecmisi');
        Schema::dropIfExists('bayi_bildirim_ayarlari');
        Schema::dropIfExists('bayi_lisanslar');
        Schema::dropIfExists('bayi_stok_talepleri');
        Schema::dropIfExists('bayi_stoklar');
        Schema::dropIfExists('bayi_banka_hesaplari');
        Schema::dropIfExists('bayi_bildirimler');
        Schema::dropIfExists('bayi_destek_mesajlar');
        Schema::dropIfExists('bayi_destek_tickets');
        Schema::dropIfExists('bayi_promosyon_kodlar');
    }
};
