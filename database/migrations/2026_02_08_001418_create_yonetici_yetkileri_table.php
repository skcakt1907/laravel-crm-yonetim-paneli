<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('yonetici_yetkileri')) {
            Schema::create('yonetici_yetkileri', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('yonetici_id');
                $table->string('sayfa_route', 150)->comment('Route adı veya sayfa yolu');
                $table->string('sayfa_adi', 255)->comment('Sayfa adı');
                $table->boolean('gorebilir')->default(true)->comment('Bu sayfayı görebilir mi?');
                $table->timestamps();
                
                $table->foreign('yonetici_id')->references('id')->on('yoneticiler')->onDelete('cascade');
                $table->unique(['yonetici_id', 'sayfa_route'], 'yonetici_sayfa_unique');
                $table->index('yonetici_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('yonetici_yetkileri');
    }
};
