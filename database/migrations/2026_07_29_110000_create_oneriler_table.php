<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ÖNERİ ZİNCİRİ — "kim kimi önerdi" tek merkezde.
 *
 * İki tür öneri var:
 *   bayi_onerisi      → Bir bayi başka bir bayiyi önerdi (Öner-Kazan).
 *                       Önerilen bayinin her satışında, onun komisyonunun %50'si
 *                       kadar öneren de kazanır. SÜRESİZ.
 *   musteri_referansi → Bir referans bir müşteriye bağlandı.
 *                       O müşterinin aldığı her şeyin %10'u öneren kişiye gider.
 *
 * Kural: Bir kişinin bir türde YALNIZCA BİR önereni olabilir (unique index).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('oneriler')) return;

        Schema::create('oneriler', function (Blueprint $table) {
            $table->id();

            // Öneren taraf
            $table->enum('oneren_tip', ['bayi', 'uye'])->default('bayi');
            $table->unsignedBigInteger('oneren_id');

            // Önerilen taraf
            $table->enum('onerilen_tip', ['bayi', 'uye'])->default('uye');
            $table->unsignedBigInteger('onerilen_id');

            $table->enum('tip', ['bayi_onerisi', 'musteri_referansi'])->default('bayi_onerisi');

            // Kazanç kuralı
            $table->decimal('oran', 5, 2)->default(0);          // %50 veya %10
            $table->boolean('suresiz')->default(1);
            $table->date('gecerlilik_bitis')->nullable();       // süresiz değilse

            $table->enum('durum', ['beklemede', 'aktif', 'iptal'])->default('aktif');
            $table->decimal('toplam_kazanc', 15, 2)->default(0);

            $table->string('kaynak', 40)->nullable();           // form / admin / basvuru / eski_sistem
            $table->text('aciklama')->nullable();
            $table->unsignedBigInteger('olusturan_id')->nullable();

            $table->timestamps();

            // Bir kişinin bir türde tek önereni olur
            $table->unique(['onerilen_tip', 'onerilen_id', 'tip'], 'oneriler_onerilen_benzersiz');
            $table->index(['oneren_tip', 'oneren_id'], 'oneriler_oneren_idx');
            $table->index(['tip', 'durum'], 'oneriler_tip_durum_idx');
        });

        // Eski referans_kayitlari kayıtlarını taşı (bayi bir üyeyi getirmiş)
        try {
            if (!Schema::hasTable('referans_kayitlari')) return;

            $eskiler = DB::table('referans_kayitlari')
                ->whereNotNull('bayi_id')->where('bayi_id', '>', 0)
                ->whereNotNull('uye_id')->where('uye_id', '>', 0)
                ->get();

            foreach ($eskiler as $e) {
                $varMi = DB::table('oneriler')
                    ->where('onerilen_tip', 'uye')->where('onerilen_id', $e->uye_id)
                    ->where('tip', 'musteri_referansi')->exists();
                if ($varMi) continue;

                DB::table('oneriler')->insert([
                    'oneren_tip'    => 'bayi',
                    'oneren_id'     => $e->bayi_id,
                    'onerilen_tip'  => 'uye',
                    'onerilen_id'   => $e->uye_id,
                    'tip'           => 'musteri_referansi',
                    'oran'          => 10.00,
                    'suresiz'       => 1,
                    'durum'         => ((int) ($e->durum ?? 1)) === 1 ? 'aktif' : 'iptal',
                    'toplam_kazanc' => (float) ($e->kazanc ?? 0),
                    'kaynak'        => 'eski_sistem',
                    'aciklama'      => 'referans_kayitlari #' . $e->id . ' kaydından aktarıldı',
                    'created_at'    => $e->kayit_tarihi ?? now(),
                    'updated_at'    => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // aktarım başarısız olsa da tablo kurulmuş olsun
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oneriler');
    }
};
