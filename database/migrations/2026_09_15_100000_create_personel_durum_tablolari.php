<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERSONEL ANLIK DURUM BİLDİRİMİ
 *
 * İki tablo:
 *  1. personel_durum_tipleri — panelden yönetilen durum listesi
 *     (🎬 Çekimde, ☕ Molada, 🌴 İzinde ...)
 *  2. personel_durumlari — kim, ne zaman, hangi durumdaydı
 *
 * NEDEN TARİHÇE: Son durumu tek satırda güncellemek yerine her değişiklik
 * yeni satır açıyor. Aksi hâlde "dün kaçta izne çıktı", "bu hafta kaç saat
 * çekimdeydi" sorularının cevabı hiç olmaz. bitis = NULL -> hâlâ devam ediyor.
 *
 * BU TABLOLARIN OTOMATİK DURUMLA (PresenceController) İLGİSİ YOK.
 * O, tarayıcı sinyalinden "bilgisayarı açık mı" hesaplıyor; bu ise kişinin
 * kendi seçtiği "ne yapıyor" bilgisi. İkisi yan yana gösteriliyor.
 *
 * MOTOR: InnoDB açıkça yazılı — bu sunucunun varsayılanı MyISAM ve MyISAM
 * foreign key'leri SESSİZCE yok sayıyor, hata da vermiyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personel_durum_tipleri')) {
            Schema::create('personel_durum_tipleri', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->string('ad', 60);
                $t->string('emoji', 16)->nullable();
                $t->string('renk', 20)->default('#6b7280');
                $t->unsignedSmallInteger('sira')->default(0);

                /*
                 * Bu durum degisince 3 yoneticiye mail gitsin mi?
                 * Hepsi acik basliyor. "Molada" gunde on kez mail atmaya
                 * baslarsa panelden tek tikla kapatilabilsin diye ayri
                 * bayrak -- kod degistirmeye gerek kalmasin.
                 */
                $t->boolean('mail_gonder')->default(true);

                // Silmek yerine pasife almak icin: silinen tipe bagli
                // gecmis kayitlar varsa tarihce bozulur.
                $t->boolean('aktif')->default(true);

                $t->timestamps();
                $t->index(['aktif', 'sira']);
            });
        }

        if (! Schema::hasTable('personel_durumlari')) {
            Schema::create('personel_durumlari', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->unsignedBigInteger('yonetici_id');
                $t->unsignedBigInteger('durum_tipi_id');

                // "Vidal Dent cekimi" gibi serbest aciklama
                $t->string('not', 200)->nullable();

                $t->timestamp('baslangic')->useCurrent();
                $t->timestamp('bitis')->nullable();

                // Durumu kim yazdi -- kendisi mi, yonetici mi
                $t->unsignedBigInteger('degistiren_id')->nullable();

                $t->timestamps();

                $t->index(['yonetici_id', 'bitis']);
                $t->index('baslangic');

                $t->foreign('durum_tipi_id')->references('id')->on('personel_durum_tipleri')
                    ->cascadeOnUpdate()->restrictOnDelete();
            });

            /*
             * yoneticiler tablosu MyISAM oldugu icin ona foreign key
             * KURULAMIYOR. Bag uygulama tarafinda korunuyor.
             */
        }

        // ── Baslangic durum listesi ──────────────────────────────────
        if (DB::table('personel_durum_tipleri')->count() === 0) {
            $simdi = now();
            DB::table('personel_durum_tipleri')->insert([
                ['ad' => 'Aktif',      'emoji' => '🟢', 'renk' => '#22c55e', 'sira' => 1, 'mail_gonder' => 1, 'aktif' => 1, 'created_at' => $simdi, 'updated_at' => $simdi],
                ['ad' => 'Çekimde',    'emoji' => '🎬', 'renk' => '#8b5cf6', 'sira' => 2, 'mail_gonder' => 1, 'aktif' => 1, 'created_at' => $simdi, 'updated_at' => $simdi],
                ['ad' => 'Toplantıda', 'emoji' => '📅', 'renk' => '#3b82f6', 'sira' => 3, 'mail_gonder' => 1, 'aktif' => 1, 'created_at' => $simdi, 'updated_at' => $simdi],
                ['ad' => 'Etütte',     'emoji' => '📚', 'renk' => '#0ea5e9', 'sira' => 4, 'mail_gonder' => 1, 'aktif' => 1, 'created_at' => $simdi, 'updated_at' => $simdi],
                ['ad' => 'Molada',     'emoji' => '☕', 'renk' => '#f59e0b', 'sira' => 5, 'mail_gonder' => 1, 'aktif' => 1, 'created_at' => $simdi, 'updated_at' => $simdi],
                ['ad' => 'Uyuyor',     'emoji' => '💤', 'renk' => '#6366f1', 'sira' => 6, 'mail_gonder' => 1, 'aktif' => 1, 'created_at' => $simdi, 'updated_at' => $simdi],
                ['ad' => 'İzinde',     'emoji' => '🌴', 'renk' => '#14b8a6', 'sira' => 7, 'mail_gonder' => 1, 'aktif' => 1, 'created_at' => $simdi, 'updated_at' => $simdi],
                ['ad' => 'Mesai dışı', 'emoji' => '🏠', 'renk' => '#6b7280', 'sira' => 8, 'mail_gonder' => 1, 'aktif' => 1, 'created_at' => $simdi, 'updated_at' => $simdi],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('personel_durumlari');
        Schema::dropIfExists('personel_durum_tipleri');
    }
};
