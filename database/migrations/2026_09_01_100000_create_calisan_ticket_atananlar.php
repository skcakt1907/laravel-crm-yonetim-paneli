<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TICKET'A BİRDEN FAZLA KİŞİ ATAMA (01.09.2026 — müşteri isteği)
 *
 * Önce bir ticket yalnızca tek kişiye atanabiliyordu (calisan_tickets.atanan_id).
 * Artık atananlar bu ara tabloda tutulur ve bir ticket birden fazla kişiye
 * atanabilir.
 *
 * calisan_tickets.atanan_id / atanan_adi KOLONLARI SİLİNMEDİ:
 * onlar artık "birincil atanan" (listedeki ilk kişi) olarak doldurulmaya
 * devam eder. Böylece o kolonları okuyan mevcut kodlar — gün sonu raporu,
 * bildirim ekranı, eski kayıtlar — hiç değişmeden çalışmayı sürdürür.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('calisan_ticket_atananlar')) {
            return;
        }

        Schema::create('calisan_ticket_atananlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('yonetici_id');
            // Ad kopya olarak saklanır: yönetici kaydı silinse bile geçmiş
            // ticket'ta "kime atanmıştı" bilgisi okunabilir kalsın.
            $table->string('yonetici_adi', 191)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['ticket_id', 'yonetici_id']); // aynı kişi iki kez eklenmesin
            $table->index('yonetici_id');                 // "bana atananlar" filtresi için
        });

        // ── Mevcut tek atamaları ara tabloya taşı (veri kaybı olmasın) ──
        if (Schema::hasTable('calisan_tickets')) {
            $mevcut = DB::table('calisan_tickets')
                ->whereNotNull('atanan_id')
                ->select('id', 'atanan_id', 'atanan_adi')
                ->get();

            foreach ($mevcut->chunk(200) as $grup) {
                DB::table('calisan_ticket_atananlar')->insertOrIgnore(
                    $grup->map(fn ($t) => [
                        'ticket_id'    => $t->id,
                        'yonetici_id'  => $t->atanan_id,
                        'yonetici_adi' => $t->atanan_adi,
                        'created_at'   => now(),
                    ])->all()
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calisan_ticket_atananlar');
    }
};
