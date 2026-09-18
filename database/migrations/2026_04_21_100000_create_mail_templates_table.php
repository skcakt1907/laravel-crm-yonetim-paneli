<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mail_templates')) return;

        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('subject', 200);
            $table->text('body');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_templates');
    }
};
