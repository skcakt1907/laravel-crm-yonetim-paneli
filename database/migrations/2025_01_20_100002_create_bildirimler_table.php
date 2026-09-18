<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bildirimler')) {
            Schema::create('bildirimler', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->nullable()->index(); // Üye ID
                $table->integer('admin_id')->nullable()->index(); // Admin/Bayi ID
                $table->string('tip', 50)->index(); // 'uye', 'admin', 'bayi'
                $table->string('baslik', 255);
                $table->text('mesaj');
                $table->string('link')->nullable();
                $table->tinyInteger('okundu')->default(0)->index();
                $table->timestamp('created_at')->useCurrent();
                
                $table->index(['tip', 'okundu', 'created_at']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bildirimler');
    }
};

