<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_task_files')) return;
        Schema::create('crm_task_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->index();
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->unsignedInteger('size')->default(0);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('uploader_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_task_files');
    }
};
