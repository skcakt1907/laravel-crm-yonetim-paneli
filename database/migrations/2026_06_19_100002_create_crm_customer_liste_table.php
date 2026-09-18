<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // PIVOT: bir müşteri birden çok listede olabilir (Çoka Çok)
    public function up(): void
    {
        Schema::create('crm_customer_liste', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('liste_id');
            $table->string('liste_durum', 20)->nullable(); // o listedeki hedef statü (aktif/potansiyel/pasif)
            $table->string('kaynak', 20)->default('manuel'); // manuel / otomatik (gelecekteki kurallar)
            $table->timestamps();

            $table->unique(['customer_id', 'liste_id']);    // aynı müşteri aynı listede 1 kez
            $table->index('liste_id');

            // Müşteri silinince ilişki gider; LİSTE silinince SADECE ilişki kopar, müşteri kalır.
            $table->foreign('customer_id')->references('id')->on('crm_customers')->cascadeOnDelete();
            $table->foreign('liste_id')->references('id')->on('crm_listeler')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_customer_liste');
    }
};
