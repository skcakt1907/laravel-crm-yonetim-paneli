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
        if (!Schema::hasTable('navbar_settings')) {
        Schema::create('navbar_settings', function (Blueprint $table) {
            $table->id();
                $table->boolean('show_currency_dropdown')->default(true);
                $table->json('currency_options')->nullable(); // TRY, USD, EUR, AED
                $table->boolean('show_language_dropdown')->default(true);
                $table->json('language_options')->nullable(); // tr, en, ar
                $table->boolean('show_login_button')->default(true);
                $table->string('login_button_text_tr')->default('Giriş Yap');
                $table->string('login_button_text_en')->nullable();
                $table->string('login_button_text_ar')->nullable();
                $table->string('login_button_url')->default('/giris');
                $table->boolean('show_register_button')->default(true);
                $table->string('register_button_text_tr')->default('Kayıt Ol');
                $table->string('register_button_text_en')->nullable();
                $table->string('register_button_text_ar')->nullable();
                $table->string('register_button_url')->default('/kayit');
                $table->boolean('show_account_button')->default(true);
                $table->string('account_button_text_tr')->default('Hesabım');
                $table->string('account_button_text_en')->nullable();
                $table->string('account_button_text_ar')->nullable();
                $table->string('account_button_url')->default('/hesabim');
                $table->boolean('show_cart_button')->default(true);
                $table->string('cart_button_text_tr')->default('Sepetim');
                $table->string('cart_button_text_en')->nullable();
                $table->string('cart_button_text_ar')->nullable();
                $table->string('cart_button_url')->default('/sepet');
                $table->boolean('show_logout_button')->default(true);
                $table->string('logout_button_text_tr')->default('Çıkış Yap');
                $table->string('logout_button_text_en')->nullable();
                $table->string('logout_button_text_ar')->nullable();
                $table->string('logout_button_url')->default('/cikis');
                $table->integer('top_bar_order')->default(0); // Sıralama için
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navbar_settings');
    }
};
