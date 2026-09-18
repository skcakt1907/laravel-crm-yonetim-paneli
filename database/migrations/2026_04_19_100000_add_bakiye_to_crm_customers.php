<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_customers') && !Schema::hasColumn('crm_customers', 'bakiye')) {
            Schema::table('crm_customers', function (Blueprint $table) {
                $table->decimal('bakiye', 14, 2)->default(0)->after('adres');
            });
        }

        if (!Schema::hasTable('crm_customer_bakiye_hareketleri')) {
            Schema::create('crm_customer_bakiye_hareketleri', function (Blueprint $table) {
                $table->id();
                $table->foreignId('musteri_id')->constrained('crm_customers')->cascadeOnDelete();
                $table->decimal('tutar', 14, 2);
                $table->string('tip', 20)->default('ekle');
                $table->string('aciklama', 255)->nullable();
                $table->unsignedBigInteger('yonetici_id')->nullable();
                $table->timestamps();
                $table->index('musteri_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_customer_bakiye_hareketleri');

        if (Schema::hasTable('crm_customers') && Schema::hasColumn('crm_customers', 'bakiye')) {
            Schema::table('crm_customers', function (Blueprint $table) {
                $table->dropColumn('bakiye');
            });
        }
    }
};
