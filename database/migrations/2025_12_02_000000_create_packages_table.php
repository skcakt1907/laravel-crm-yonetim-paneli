<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();

            // Turkish (master language)
            $table->string('title_tr');
            $table->text('desc_tr')->nullable();

            // English
            $table->string('title_en')->nullable();
            $table->text('desc_en')->nullable();

            // Arabic
            $table->string('title_ar')->nullable();
            $table->text('desc_ar')->nullable();

            // Optional: status & meta fields
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};





















