<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bayi tipi + tek komisyon oranı.
 *
 *  internet → İnternet Bayiliği      (varsayılan %15)
 *  partner  → DN Ofis Partnerliği    (varsayılan %30, giriş bedeli 10.000 $)
 *
 * Peşin/vadeli komisyon oranları artık KULLANILMIYOR (tek orana inildi).
 * Kolonlar silinmiyor — canlıda veri olma ihtimaline karşı yerinde bırakıldı,
 * arayüz ve kayıt akışından çıkarıldı.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bayiler')) return;

        Schema::table('bayiler', function (Blueprint $table) {
            if (!Schema::hasColumn('bayiler', 'bayi_tipi')) {
                $table->enum('bayi_tipi', ['internet', 'partner'])
                      ->default('internet')->after('bayi_kodu')->index();
            }
            // Partnerlik giriş bedeli tahsilatı (10.000 $) — partner olmayanlarda NULL
            if (!Schema::hasColumn('bayiler', 'partnerlik_bedeli')) {
                $table->decimal('partnerlik_bedeli', 12, 2)->nullable()->after('bayi_tipi');
            }
            if (!Schema::hasColumn('bayiler', 'partnerlik_odendi_at')) {
                $table->timestamp('partnerlik_odendi_at')->nullable()->after('partnerlik_bedeli');
            }
        });

        // Mevcut bayiler internet bayisi sayılır; komisyonu 0/boş olanlara %15 verilir.
        try {
            DB::table('bayiler')->whereNull('bayi_tipi')->update(['bayi_tipi' => 'internet']);
            DB::table('bayiler')
                ->where('bayi_tipi', 'internet')
                ->where(function ($q) {
                    $q->whereNull('komisyon_orani')->orWhere('komisyon_orani', 0);
                })
                ->update(['komisyon_orani' => 15.00]);
        } catch (\Throwable $e) {
            // veri güncellemesi başarısız olsa da şema değişikliği geçerli kalsın
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('bayiler')) return;

        Schema::table('bayiler', function (Blueprint $table) {
            foreach (['bayi_tipi', 'partnerlik_bedeli', 'partnerlik_odendi_at'] as $kolon) {
                if (Schema::hasColumn('bayiler', $kolon)) {
                    $table->dropColumn($kolon);
                }
            }
        });
    }
};
