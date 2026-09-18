<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('arka_plan')) {
            Schema::create('arka_plan', function (Blueprint $table) {
                $table->id();
                $table->string('sayfa')->nullable()->comment('Sayfa adı (paketler, firsatlar, vb.)');
                $table->enum('tip', ['resim', 'renk', 'gradient'])->default('resim');
                $table->string('resim')->nullable();
                $table->string('renk')->nullable();
                $table->text('gradient')->nullable();
                $table->timestamps();
                
                $table->index('sayfa');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('arka_plan');
    }
};
