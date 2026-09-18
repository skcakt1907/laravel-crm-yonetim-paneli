<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SÖZLEŞMELERİ TEK YERE TOPLA.
 *
 * Önce iki ayrı sistem vardı:
 *   crm_sozlesmeler     → admin müşteri kartı (yapılandırılmış, 2 kayıt)
 *   musteri_sozlesmeler → üye paneli "Sözleşmelerim" (dosya arşivi, 218 kayıt)
 * Birbirlerinden habersizlerdi: admin'de girilen sözleşme üyede görünmüyordu.
 *
 * Bu migration 218 kaydı crm_sozlesmeler'e taşır ve müşteri kartlarına bağlar.
 * KAYNAK TABLO SİLİNMEZ — geri dönüş için olduğu gibi durur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_sozlesmeler')) return;

        // 1) Eksik kolonlar
        Schema::table('crm_sozlesmeler', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_sozlesmeler', 'dosya')) {
                $table->string('dosya', 255)->nullable()->after('icerik');
            }
            if (!Schema::hasColumn('crm_sozlesmeler', 'uye_id')) {
                $table->unsignedBigInteger('uye_id')->nullable()->after('musteri_id')->index();
            }
            if (!Schema::hasColumn('crm_sozlesmeler', 'dil')) {
                $table->string('dil', 5)->nullable()->after('dosya');
            }
            if (!Schema::hasColumn('crm_sozlesmeler', 'kaynak')) {
                $table->string('kaynak', 30)->nullable()->after('dil');
            }
        });

        // 2) Mevcut crm kayıtlarına uye_id doldur (müşteri kartından)
        try {
            DB::statement("
                UPDATE crm_sozlesmeler s
                  JOIN crm_customers c ON c.id = s.musteri_id
                   SET s.uye_id = c.uye_id
                 WHERE s.uye_id IS NULL AND c.uye_id IS NOT NULL
            ");
        } catch (\Throwable $e) {}

        // 3) musteri_sozlesmeler kayıtlarını taşı
        if (!Schema::hasTable('musteri_sozlesmeler')) return;

        try {
            $eskiler = DB::table('musteri_sozlesmeler')->orderBy('id')->get();

            foreach ($eskiler as $e) {
                // Aynı kayıt daha önce taşındıysa atla
                $varMi = DB::table('crm_sozlesmeler')
                    ->where('kaynak', 'musteri_sozlesmeler')
                    ->where('sozlesme_no', 'MS-' . $e->id)
                    ->exists();
                if ($varMi) continue;

                $musteriId = DB::table('crm_customers')->where('uye_id', $e->uyeid)->value('id');
                if (!$musteriId) {
                    // uye_id ile bulunamazsa e-posta üzerinden dene
                    $musteriId = DB::table('crm_customers as c')
                        ->join('uyeler as u', 'u.email', '=', 'c.email')
                        ->where('u.id', $e->uyeid)
                        ->value('c.id');
                }
                if (!$musteriId) continue; // bağlanamayanı taşıma, kaynakta kalsın

                DB::table('crm_sozlesmeler')->insert([
                    'musteri_id'  => $musteriId,
                    'uye_id'      => $e->uyeid,
                    'sozlesme_no' => 'MS-' . $e->id,
                    'baslik'      => $e->baslik ?: 'Sözleşme',
                    'icerik'      => $e->icerik ?: null,
                    'dosya'       => $e->dosya ?: null,
                    'dil'         => $e->dil ?: null,
                    'tutar'       => is_numeric($e->tutar ?? null) ? $e->tutar : 0,
                    'durum'       => 'aktif',
                    'tarih'       => $e->tarih ?: now(),
                    'kaynak'      => 'musteri_sozlesmeler',
                    'created_at'  => $e->tarih ?: now(),
                    'updated_at'  => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // taşıma başarısız olsa da şema değişikliği geçerli kalsın
        }
    }

    public function down(): void
    {
        // Taşınan kayıtları geri al (kaynak tablo zaten duruyor)
        try {
            DB::table('crm_sozlesmeler')->where('kaynak', 'musteri_sozlesmeler')->delete();
        } catch (\Throwable $e) {}

        if (!Schema::hasTable('crm_sozlesmeler')) return;

        Schema::table('crm_sozlesmeler', function (Blueprint $table) {
            foreach (['dosya', 'uye_id', 'dil', 'kaynak'] as $k) {
                if (Schema::hasColumn('crm_sozlesmeler', $k)) $table->dropColumn($k);
            }
        });
    }
};
