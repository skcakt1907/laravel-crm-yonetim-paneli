<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * metunic_domains'e TUTAR / MALİYET / SATIŞ TARİHİ.
 *
 * NEDEN: Domain listesi iki ayrı tabloya bölünüyor — bizim elle girdiğimiz
 * kayıtlar (satilanlar, tipi=3) ve Metunic senkronundan gelenler. Metunic'e
 * özel olan (bizde karşılığı bulunmayan) domainlerin fiyat bilgisi hiçbir
 * yerde yok; tablo yalnızca domain + tarih tutuyor. Bu yüzden kâr raporunda
 * hiç görünemiyorlar.
 *
 * Bu kolonlar panelden elle doldurulabilsin diye açılıyor. Boş kaldıkları
 * sürece kayıt ciroya KATILMAZ — tahmini rakamla raporu şişirmek yerine
 * "bilinmiyor" olarak kalması tercih edildi.
 *
 * satis_tarihi AYRI BİR KOLON, çünkü mevcut `date_added` domainin ilk
 * tescil tarihi (veride 2020–2026 arasına yayılıyor), satış tarihi değil.
 * Ciroyu ona göre dağıtmak geliri yanlış yıllara yazar.
 *
 * VERİ TAŞIMA YOK: `satilanlar` kayıtları silinmiyor, oldukları yerde
 * kalıyorlar. Bu migration yalnızca kolon açar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('metunic_domains')) {
            return;
        }

        Schema::table('metunic_domains', function (Blueprint $t) {
            if (! Schema::hasColumn('metunic_domains', 'tutar')) {
                $t->decimal('tutar', 12, 2)->nullable()->after('uye_id');
            }
            if (! Schema::hasColumn('metunic_domains', 'maliyet')) {
                $t->decimal('maliyet', 12, 2)->nullable()->after('tutar');
            }
            if (! Schema::hasColumn('metunic_domains', 'satis_tarihi')) {
                $t->date('satis_tarihi')->nullable()->after('maliyet');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('metunic_domains')) {
            return;
        }

        Schema::table('metunic_domains', function (Blueprint $t) {
            foreach (['tutar', 'maliyet', 'satis_tarihi'] as $k) {
                if (Schema::hasColumn('metunic_domains', $k)) {
                    $t->dropColumn($k);
                }
            }
        });
    }
};
