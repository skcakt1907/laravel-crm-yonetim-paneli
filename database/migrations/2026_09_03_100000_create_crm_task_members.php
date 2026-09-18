<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Goreve birden fazla kisi atanabilmesi icin ara tablo.
 *
 * Mevcut crm_tasks.atanan_id kolonu KALDIRILMADI: "birincil atanan"
 * olarak duruyor. Boylece eski sorgular, raporlar ve bildirimler
 * calismaya devam ediyor. Ikinci/ucuncu atananlar bu tabloda.
 *
 * Yapi bilerek kanban_card_members ile ayni.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_task_members')) {
            return;
        }

        Schema::create('crm_task_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('yonetici_id');
            $table->timestamps();

            // DIKKAT: Canli veritabani MyISAM; MyISAM foreign key'i SESSIZCE
            // yok sayar, yani ON DELETE CASCADE CALISMAZ. Temizlik uygulama
            // tarafinda yapiliyor (TaskController::destroy). Bu satirlar
            // ileride InnoDB'ye gecilirse devreye girsin diye duruyor.
            $table->foreign('task_id')->references('id')->on('crm_tasks')->onDelete('cascade');
            $table->foreign('yonetici_id')->references('id')->on('yoneticiler')->onDelete('cascade');
            $table->unique(['task_id', 'yonetici_id']);
            $table->index('task_id');
        });

        // Mevcut gorevlerin atananlarini tabloya tasi ki
        // "bana atananlar" listeleri ilk gunden dolu gelsin.
        if (Schema::hasTable('crm_tasks')) {
            \DB::statement("
                INSERT IGNORE INTO crm_task_members (task_id, yonetici_id, created_at, updated_at)
                SELECT t.id, t.atanan_id, NOW(), NOW()
                FROM crm_tasks t
                INNER JOIN yoneticiler y ON y.id = t.atanan_id
                WHERE t.atanan_id IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_task_members');
    }
};
