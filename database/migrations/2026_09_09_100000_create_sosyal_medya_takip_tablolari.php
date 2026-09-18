<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SOSYAL MEDYA PAYLAŞIM TAKİP MODÜLÜ — tablolar
 *
 * MEVCUT YAPIYA BAĞLANIR, YENİSİNİ KURMAZ:
 *   sosyal_medya_kayitlari  = marka        (zaten var, CRM müşterisine bağlı)
 *   sosyal_medya_hesaplari  = platform     (zaten var: Instagram, LinkedIn ...)
 * Bu modül yalnızca "hangi gün kaç paylaşım" ve "yapıldı mı" kısmını ekler.
 *
 * MOTOR — DİKKAT: her tabloda `$t->engine = 'InnoDB'` AÇIKÇA yazılmalı.
 * config/database.php'de 'engine' => null olduğu için Laravel motoru MySQL'e
 * bırakıyor; bu sunucunun varsayılanı MyISAM. İlk sürümde bunu yazmamıştım ve
 * dört tablo da MyISAM oluştu: foreign key'ler SESSİZCE yok sayıldı, hesap
 * silindiğinde paylaşım satırları öksüz kaldı (yaşandı, 10 satır).
 *
 * `yoneticiler` MyISAM — o yüzden kullanıcı alanlarında FK YOK, yalnızca id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. SORUMLU UZMAN ────────────────────────────────────────
        // Ayrı tablo değil, tek kolon: istek "Sorumlu Uzman" diye TEK kişi
        // istiyor. Çoklu gerekirse ileride ara tabloya taşınır.
        if (Schema::hasTable('sosyal_medya_hesaplari')
            && ! Schema::hasColumn('sosyal_medya_hesaplari', 'sorumlu_id')) {
            Schema::table('sosyal_medya_hesaplari', function (Blueprint $t) {
                $t->unsignedBigInteger('sorumlu_id')->nullable()->after('sira');
                $t->index('sorumlu_id');
            });
        }

        // ── 2. HAFTALIK ŞABLON ──────────────────────────────────────
        // "Marka X - Instagram - Salı: 3 paylaşım"
        // gun: 1=Pazartesi ... 7=Pazar (ISO-8601, Carbon::dayOfWeekIso ile aynı)
        // Pazar varsayılan kapalı -> o gün için satır açılmaz veya hedef 0 olur.
        if (! Schema::hasTable('sosyal_medya_planlar')) {
            Schema::create('sosyal_medya_planlar', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->unsignedBigInteger('hesap_id');
                $t->unsignedTinyInteger('gun');           // 1-7
                $t->unsignedSmallInteger('hedef_adet')->default(1);
                $t->boolean('aktif')->default(true);
                $t->timestamps();

                // Aynı hesap+gün ikinci kez tanımlanamaz
                $t->unique(['hesap_id', 'gun'], 'sm_plan_benzersiz');
                $t->foreign('hesap_id')->references('id')
                  ->on('sosyal_medya_hesaplari')->onDelete('cascade');
            });
        }

        // ── 3. TARİHE ÖZEL İSTİSNA ──────────────────────────────────
        // Şablonu EZER. İki işi birden görür:
        //   "12 Eylül kampanya var, 5 paylaşım"  -> hedef_adet = 5
        //   "29 Ekim tatil, paylaşım yok"        -> hedef_adet = 0
        // Böylece resmî tatiller için ayrı bir mekanizma gerekmiyor.
        if (! Schema::hasTable('sosyal_medya_plan_istisnalar')) {
            Schema::create('sosyal_medya_plan_istisnalar', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->unsignedBigInteger('hesap_id');
                $t->date('tarih');
                $t->unsignedSmallInteger('hedef_adet');
                $t->string('sebep', 255)->nullable();
                $t->unsignedBigInteger('olusturan_id')->nullable();
                $t->timestamps();

                $t->unique(['hesap_id', 'tarih'], 'sm_istisna_benzersiz');
                $t->index('tarih');
                $t->foreign('hesap_id')->references('id')
                  ->on('sosyal_medya_hesaplari')->onDelete('cascade');
            });
        }

        // ── 4. YAPILAN PAYLAŞIMLAR ──────────────────────────────────
        // HER PAYLAŞIM AYRI SATIR. Günde birden fazla paylaşım olabildiği için
        // (hesap_id, tarih) üzerinde BİLEREK unique YOK. Böylece her paylaşımın
        // kendi linki ve kendi saati tutulabiliyor.
        //
        // tarih          -> paylaşımın YAPILDIĞI gün
        // isaretlendi_at -> panele İŞARETLENDİĞİ an
        // İkisi farklıysa "sonradan işaretlendi" demektir; rapor bunu gösterir.
        if (! Schema::hasTable('sosyal_medya_paylasimlar')) {
            Schema::create('sosyal_medya_paylasimlar', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->unsignedBigInteger('hesap_id');
                $t->date('tarih');
                $t->string('link', 500)->nullable();
                $t->text('not')->nullable();
                $t->unsignedBigInteger('isaretleyen_id')->nullable();
                $t->dateTime('isaretlendi_at')->nullable();
                $t->timestamps();

                $t->index(['hesap_id', 'tarih'], 'sm_paylasim_hesap_tarih');
                $t->index('tarih');   // gün sonu raporu bu kolondan tarar
                $t->foreign('hesap_id')->references('id')
                  ->on('sosyal_medya_hesaplari')->onDelete('cascade');
            });
        }

        // ── 5. GÜN NOTU: ERTELEME / İPTAL ───────────────────────────
        // Paylaşım DEĞİL, güne ait bir karar. Ayrı tabloda çünkü:
        //   - günde tek karar olur -> (hesap_id, tarih) unique kurulabiliyor
        //   - paylaşımlarla aynı tabloda olsaydı bu kısıt kurulamazdı
        //
        // tip = 'ertelendi' -> ertelendi_tarih DOLU olmalı
        // tip = 'iptal'     -> ertelendi_tarih boş
        if (! Schema::hasTable('sosyal_medya_gun_notlari')) {
            Schema::create('sosyal_medya_gun_notlari', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->unsignedBigInteger('hesap_id');
                $t->date('tarih');
                $t->enum('tip', ['ertelendi', 'iptal']);
                $t->date('ertelendi_tarih')->nullable();
                $t->string('sebep', 500)->nullable();
                $t->unsignedBigInteger('isaretleyen_id')->nullable();
                $t->dateTime('isaretlendi_at')->nullable();
                $t->timestamps();

                $t->unique(['hesap_id', 'tarih'], 'sm_gun_notu_benzersiz');
                $t->index('tarih');
                $t->index('ertelendi_tarih');   // "bugüne ertelenenler" sorgusu
                $t->foreign('hesap_id')->references('id')
                  ->on('sosyal_medya_hesaplari')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sosyal_medya_gun_notlari');
        Schema::dropIfExists('sosyal_medya_paylasimlar');
        Schema::dropIfExists('sosyal_medya_plan_istisnalar');
        Schema::dropIfExists('sosyal_medya_planlar');

        if (Schema::hasColumn('sosyal_medya_hesaplari', 'sorumlu_id')) {
            Schema::table('sosyal_medya_hesaplari', function (Blueprint $t) {
                $t->dropColumn('sorumlu_id');
            });
        }
    }
};
