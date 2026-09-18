<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('yazilimlar')) {
            Schema::create('yazilimlar', function (Blueprint $table) {
                $table->id();
                $table->string('adi');
                $table->text('kisa')->nullable();
                $table->text('aciklama')->nullable();
                $table->text('ozellik')->nullable();
                $table->string('resim')->nullable();
                $table->decimal('tutar', 15, 2)->default(0);
                $table->decimal('indirim', 5, 2)->default(0)->comment('İndirim yüzdesi');
                $table->boolean('firsat')->default(0)->comment('Fırsat olarak işaretli mi');
                $table->string('seo')->nullable()->unique();
                $table->tinyInteger('durum')->default(1);
                $table->tinyInteger('dil')->default(1)->comment('1: TR, 2: EN, 3: AR');
                $table->string('kategori')->nullable();
                $table->timestamps();
                
                $table->index('durum');
                $table->index('seo');
                $table->index('dil');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('yazilimlar');
    }
};
