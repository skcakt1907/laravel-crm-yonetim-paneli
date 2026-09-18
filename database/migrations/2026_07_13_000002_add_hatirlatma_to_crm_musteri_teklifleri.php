<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teklif değerlendirme hatırlatması (madde 6): teklif gönderildikten 24 saat sonra
 * hâlâ yanıtlanmadıysa otomatik hatırlatma maili gider. Bu kolon, hatırlatmanın
 * bir kez gönderildiğini işaretler (tekrar tekrar gönderilmesin).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_musteri_teklifleri')
            && !Schema::hasColumn('crm_musteri_teklifleri', 'hatirlatma_sent_at')) {
            Schema::table('crm_musteri_teklifleri', function (Blueprint $table) {
                $table->timestamp('hatirlatma_sent_at')->nullable()->after('sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_musteri_teklifleri')
            && Schema::hasColumn('crm_musteri_teklifleri', 'hatirlatma_sent_at')) {
            Schema::table('crm_musteri_teklifleri', function (Blueprint $table) {
                $table->dropColumn('hatirlatma_sent_at');
            });
        }
    }
};
