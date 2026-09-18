<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Ana statü (durum) performans için indekslenir (brief teknik notu)
    public function up(): void
    {
        Schema::table('crm_customers', function (Blueprint $table) {
            $table->index('durum', 'crm_customers_durum_index');
        });
    }

    public function down(): void
    {
        Schema::table('crm_customers', function (Blueprint $table) {
            $table->dropIndex('crm_customers_durum_index');
        });
    }
};
