<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    if (Schema::hasTable('domain_orders')) {
      return;
    }
    
    Schema::create('domain_orders', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->string('domain');
      $table->decimal('price', 10, 2);
      $table->integer('years')->default(1);
      $table->enum('status', ['pending', 'paid', 'active', 'failed', 'expired'])->default('pending');
      $table->string('reseller_order_id')->nullable();
      $table->string('payment_method')->nullable(); // paytr, iyzico, havale
      $table->string('payment_id')->nullable();
      $table->unsignedBigInteger('fatura_id')->nullable();
      $table->text('error_message')->nullable();
      $table->timestamp('registered_at')->nullable();
      $table->timestamp('expires_at')->nullable();
      $table->timestamps();
      
      $table->index('user_id');
      $table->index('domain');
      $table->index('status');
      $table->index('reseller_order_id');
      $table->foreign('user_id')->references('id')->on('uyeler')->onDelete('cascade');
    });
  }
  
  public function down()
  {
    Schema::dropIfExists('domain_orders');
  }
};

