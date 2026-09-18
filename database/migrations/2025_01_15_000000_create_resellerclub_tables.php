<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    // ResellerClub müşteri tablosu
    if (!Schema::hasTable('resellerclub_customers')) {
      Schema::create('resellerclub_customers', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('uye_id');
        $table->string('customer_id')->unique();
        $table->string('username');
        $table->timestamps();
        
        $table->index('uye_id');
        $table->index('customer_id');
      });
    }
    
    // ResellerClub iletişim bilgileri tablosu
    if (!Schema::hasTable('resellerclub_contacts')) {
      Schema::create('resellerclub_contacts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('uye_id');
        $table->string('customer_id');
        $table->string('contact_id');
        $table->timestamps();
        
        $table->index('uye_id');
        $table->index('customer_id');
        $table->index('contact_id');
      });
    }
    
    // Domain kayıtları tablosu (eğer yoksa)
    if (!Schema::hasTable('alan_adlarim')) {
      Schema::create('alan_adlarim', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('uyeid');
        $table->string('domain');
        $table->date('kayit_tarihi');
        $table->date('bitis_tarihi');
        $table->tinyInteger('durum')->default(1); // 1: Aktif, 0: Pasif
        $table->unsignedBigInteger('siparis_id')->nullable();
        $table->string('order_id')->nullable(); // ResellerClub order ID
        $table->text('nameservers')->nullable();
        $table->timestamps();
        
        $table->index('uyeid');
        $table->index('domain');
        $table->index('durum');
      });
    }
    
    // satilanlar tablosuna domain kolonları ekle (eğer yoksa)
    if (Schema::hasTable('satilanlar')) {
      Schema::table('satilanlar', function (Blueprint $table) {
        if (!Schema::hasColumn('satilanlar', 'domain_durum')) {
          $table->string('domain_durum')->nullable()->after('durum'); // bekliyor, kayitli, hata
        }
        if (!Schema::hasColumn('satilanlar', 'domain_order_id')) {
          $table->string('domain_order_id')->nullable()->after('domain_durum');
        }
        if (!Schema::hasColumn('satilanlar', 'domain_kayit_tarihi')) {
          $table->timestamp('domain_kayit_tarihi')->nullable()->after('domain_order_id');
        }
        if (!Schema::hasColumn('satilanlar', 'domain_hata')) {
          $table->text('domain_hata')->nullable()->after('domain_kayit_tarihi');
        }
      });
    }
  }
  
  public function down()
  {
    Schema::dropIfExists('resellerclub_contacts');
    Schema::dropIfExists('resellerclub_customers');
    // alan_adlarim tablosunu silme (başka yerlerde kullanılıyor olabilir)
  }
};

