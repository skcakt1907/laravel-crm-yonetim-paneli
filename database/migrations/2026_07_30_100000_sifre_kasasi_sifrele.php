<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ŞİFRE KASASI — düz metin şifreleri şifreler.
 *
 * ÖNCE: sosyal_medya_hesaplari.sifre alanı DÜZ METİN tutuyordu.
 *       Veritabanı yedeğini ele geçiren herkes tüm müşteri şifrelerini okuyabiliyordu.
 * SONRA: Laravel şifrelemesi (APP_KEY) ile saklanır; model cast'i otomatik çözer.
 *
 * ⚠️ APP_KEY DEĞİŞİRSE ŞİFRELER OKUNAMAZ. .env yedeklenmeli.
 *
 * Tekrar çalıştırılabilir: zaten şifreli olan kayıtlar atlanır.
 * Geri alınabilir: down() şifreleri düz metne çevirir (APP_KEY dururken).
 */
return new class extends Migration
{
    /** Değer zaten şifreli mi? (çözülebiliyorsa şifrelidir) */
    private function sifreliMi(?string $deger): bool
    {
        if ($deger === null || $deger === '') return false;

        try {
            Crypt::decryptString($deger);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('sosyal_medya_hesaplari')) return;

        $sifrelenen = 0;
        $atlanan    = 0;
        $bos        = 0;

        try {
            // Model KULLANILMAZ — cast devrede olduğu için çift şifreleme olurdu.
            $kayitlar = DB::table('sosyal_medya_hesaplari')->select('id', 'sifre')->get();

            foreach ($kayitlar as $k) {
                if ($k->sifre === null || $k->sifre === '') { $bos++; continue; }
                if ($this->sifreliMi($k->sifre))            { $atlanan++; continue; }

                DB::table('sosyal_medya_hesaplari')
                    ->where('id', $k->id)
                    ->update(['sifre' => Crypt::encryptString($k->sifre)]);
                $sifrelenen++;
            }

            Log::info('Şifre kasası şifrelendi', [
                'sifrelenen' => $sifrelenen, 'zaten_sifreli' => $atlanan, 'bos' => $bos,
            ]);
        } catch (\Throwable $e) {
            Log::error('Şifre kasası şifreleme hatası', ['hata' => $e->getMessage()]);
            throw $e; // yarım kalmasın, görülsün
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sosyal_medya_hesaplari')) return;

        try {
            $kayitlar = DB::table('sosyal_medya_hesaplari')->select('id', 'sifre')->get();

            foreach ($kayitlar as $k) {
                if ($k->sifre === null || $k->sifre === '') continue;
                if (!$this->sifreliMi($k->sifre)) continue;

                DB::table('sosyal_medya_hesaplari')
                    ->where('id', $k->id)
                    ->update(['sifre' => Crypt::decryptString($k->sifre)]);
            }
        } catch (\Throwable $e) {
            Log::error('Şifre kasası geri alma hatası', ['hata' => $e->getMessage()]);
        }
    }
};
