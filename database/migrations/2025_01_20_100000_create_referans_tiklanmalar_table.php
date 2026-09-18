<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('referans_tiklanmalar')) {
            Schema::create('referans_tiklanmalar', function (Blueprint $table) {
                $table->id();
                $table->string('bayi_kodu', 50)->index();
                $table->integer('bayi_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->string('referer')->nullable();
                $table->timestamp('created_at')->useCurrent();
                
                $table->index(['bayi_kodu', 'created_at']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('referans_tiklanmalar');
    }
};

