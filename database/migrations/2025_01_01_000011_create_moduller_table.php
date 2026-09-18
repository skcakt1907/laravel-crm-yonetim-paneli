<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moduller', function (Blueprint $table) {
            $table->id();
            $table->boolean('alan1')->default(1)->comment('Slider modülü');
            $table->boolean('alan2')->default(1)->comment('Paketler modülü');
            $table->boolean('alan3')->default(1)->comment('Kurumsal modülü');
            $table->boolean('alan4')->default(1)->comment('Hosting modülü');
            $table->boolean('alan5')->default(1)->comment('Referanslar modülü');
            $table->boolean('alan6')->default(1)->comment('Blog modülü');
            $table->boolean('alan7')->default(1)->comment('Üst menü modülü');
            $table->timestamps();
        });
        
        // Varsayılan modül ayarlarını ekle
        DB::table('moduller')->insert([
            'alan1' => 1,
            'alan2' => 1,
            'alan3' => 1,
            'alan4' => 1,
            'alan5' => 1,
            'alan6' => 1,
            'alan7' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('moduller');
    }
};
