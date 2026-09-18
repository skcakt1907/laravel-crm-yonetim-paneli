<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sayfa_bakim')) {
            Schema::create('sayfa_bakim', function (Blueprint $table) {
                $table->id();
                $table->string('sayfa_adi', 100); // sepet, odeme, paketler, vb.
                $table->string('route_name', 100)->nullable(); // route adı
                $table->string('url_pattern', 255)->nullable(); // URL pattern
                $table->boolean('aktif')->default(false);
                $table->string('baslik', 255)->nullable();
                $table->text('mesaj')->nullable();
                $table->timestamp('baslangic_tarihi')->nullable();
                $table->timestamp('bitis_tarihi')->nullable();
                $table->timestamps();
            });
            
            // Varsayılan sayfaları ekle
            \DB::table('sayfa_bakim')->insert([
                ['sayfa_adi' => 'Sepet', 'route_name' => 'sepet', 'url_pattern' => '/sepet*', 'aktif' => false, 'baslik' => 'Sepet Bakımda', 'mesaj' => 'Sepet sistemi şu anda bakımdadır. Lütfen daha sonra tekrar deneyin.', 'created_at' => now(), 'updated_at' => now()],
                ['sayfa_adi' => 'Ödeme', 'route_name' => 'odeme', 'url_pattern' => '/odeme*', 'aktif' => false, 'baslik' => 'Ödeme Sistemi Bakımda', 'mesaj' => 'Ödeme sistemi şu anda bakımdadır. Lütfen daha sonra tekrar deneyin.', 'created_at' => now(), 'updated_at' => now()],
                ['sayfa_adi' => 'Domain Sorgulama', 'route_name' => 'domain', 'url_pattern' => '/domain*', 'aktif' => false, 'baslik' => 'Domain Sistemi Bakımda', 'mesaj' => 'Domain sorgulama sistemi şu anda bakımdadır.', 'created_at' => now(), 'updated_at' => now()],
                ['sayfa_adi' => 'Paketler', 'route_name' => 'paketler', 'url_pattern' => '/paketler*', 'aktif' => false, 'baslik' => 'Paketler Sayfası Bakımda', 'mesaj' => 'Paketler sayfası şu anda bakımdadır.', 'created_at' => now(), 'updated_at' => now()],
                ['sayfa_adi' => 'Hosting', 'route_name' => 'hosting', 'url_pattern' => '/hosting*', 'aktif' => false, 'baslik' => 'Hosting Sayfası Bakımda', 'mesaj' => 'Hosting sayfası şu anda bakımdadır.', 'created_at' => now(), 'updated_at' => now()],
                ['sayfa_adi' => 'Müşteri Paneli', 'route_name' => 'hesabim', 'url_pattern' => '/hesabim*', 'aktif' => false, 'baslik' => 'Müşteri Paneli Bakımda', 'mesaj' => 'Müşteri paneli şu anda bakımdadır.', 'created_at' => now(), 'updated_at' => now()],
                ['sayfa_adi' => 'Kayıt', 'route_name' => 'kayit', 'url_pattern' => '/kayit*', 'aktif' => false, 'baslik' => 'Kayıt Sistemi Bakımda', 'mesaj' => 'Kayıt sistemi şu anda bakımdadır.', 'created_at' => now(), 'updated_at' => now()],
                ['sayfa_adi' => 'Destek', 'route_name' => 'destek', 'url_pattern' => '/destek*', 'aktif' => false, 'baslik' => 'Destek Sistemi Bakımda', 'mesaj' => 'Destek sistemi şu anda bakımdadır.', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sayfa_bakim');
    }
};
