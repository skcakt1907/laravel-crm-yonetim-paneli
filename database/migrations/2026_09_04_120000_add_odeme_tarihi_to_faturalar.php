<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * faturalar.odeme_tarihi — kodun beklediği ama HİÇ OLUŞTURULMAMIŞ kolon.
 *
 * Fatura modeli fillable/casts içinde tanımlıyor, fatura PDF'i ve
 * müşteri tarafındaki üç ekran ("Faturalarım", fatura detayı) bu alanı
 * basıyor, iyzico callback'i buraya yazıyor — ama kolon veritabanında yok.
 *
 * SONUÇLARI:
 *   - Müşteri hiçbir yerde ödeme tarihini göremiyordu (hep boş).
 *   - Fatura PDF'inde "Ödeme Tarihi" satırı hiç basılmıyordu.
 *   - iyzico callback'i olmayan kolona yazmaya çalıştığı için SQL
 *     hatası veriyordu. Dökümde tek bir 'iyzico' kaydı yok — bu yol
 *     hiç başarıyla tamamlanmamış.
 *
 * NOT: Raporlar bu kolona DEĞİL `odenen_tarih`e bakar. İkisi ayrı işler:
 *   odenen_tarih  -> tahsilat GÜNÜ (muhasebe/raporlama)
 *   odeme_tarihi  -> işlemin tam ZAMANI (müşteriye gösterim, dekont)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('faturalar') || Schema::hasColumn('faturalar', 'odeme_tarihi')) {
            return;
        }

        Schema::table('faturalar', function (Blueprint $t) {
            $t->dateTime('odeme_tarihi')->nullable()->after('odenen_tarih');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('faturalar', 'odeme_tarihi')) {
            Schema::table('faturalar', function (Blueprint $t) {
                $t->dropColumn('odeme_tarihi');
            });
        }
    }
};
