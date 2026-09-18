<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Taşınan sözleşmelere kategori atar.
 *
 * Eski musteri_sozlesmeler tablosunda kategori alanı YOKTU; taşınan 218 kayıt
 * kategorisiz kalmıştı. Başlıklara bakılarak dağıtılır.
 * Eksik kategoriler (Domain & Hosting, Sosyal Medya, SEO) burada oluşturulur.
 *
 * NOT: Yalnızca kategorisi BOŞ olan kayıtlara dokunur; elle atanmışları bozmaz.
 */
return new class extends Migration
{
    /** Türkçe güvenli küçültme — İ/I/ı karışıklığını çözer */
    private function trk(?string $s): string
    {
        $s = str_replace(['İ','I','Ş','Ğ','Ü','Ö','Ç','ı'], ['i','i','s','g','u','o','c','i'], (string) $s);
        return mb_strtolower($s, 'UTF-8');
    }

    private function kategoriBulVeyaOlustur(string $ad, string $renk, int $sira): int
    {
        $mevcut = DB::table('crm_sozlesme_kategorileri')->where('ad', $ad)->value('id');
        if ($mevcut) return (int) $mevcut;

        return (int) DB::table('crm_sozlesme_kategorileri')->insertGetId([
            'ad' => $ad, 'renk' => $renk, 'sira' => $sira, 'durum' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function up(): void
    {
        if (!Schema::hasTable('crm_sozlesmeler') || !Schema::hasTable('crm_sozlesme_kategorileri')) return;

        // Eksik kategoriler
        $domain = $this->kategoriBulVeyaOlustur('Domain & Hosting', '#0ea5e9', 5);
        $sosyal = $this->kategoriBulVeyaOlustur('Sosyal Medya', '#ec4899', 6);
        $seo    = $this->kategoriBulVeyaOlustur('SEO / Dijital Pazarlama', '#8b5cf6', 7);

        // Mevcutlar (yoksa oluşturulur)
        $web    = $this->kategoriBulVeyaOlustur('Web Tasarım Sözleşmesi', '#6366f1', 1);
        $hizmet = $this->kategoriBulVeyaOlustur('Hizmet Sözleşmesi', '#10b981', 2);
        $bakim  = $this->kategoriBulVeyaOlustur('Bakım/Destek Sözleşmesi', '#f59e0b', 3);

        // Başlık → kategori eşlemesi (sıra önemli: ilk eşleşen kazanır)
        $kurallar = [
            [$domain, ['domain', 'hosting']],
            [$sosyal, ['sosyal medya', 'instagram', 'meta', 'reklam']],
            [$seo,    ['seo', 'dijital pazarlama', 'google']],
            [$bakim,  ['bakim', 'destek']],
            [$web,    ['web', 'tasarim', 'yazilim', 'e-ticaret', 'site', 'sanal tur']],
        ];

        try {
            $kayitlar = DB::table('crm_sozlesmeler')->whereNull('kategori_id')->get(['id', 'baslik']);

            foreach ($kayitlar as $k) {
                $baslik = $this->trk($k->baslik);
                $hedef  = $hizmet; // eşleşmeyenler genel "Hizmet Sözleşmesi"

                foreach ($kurallar as [$katId, $kelimeler]) {
                    foreach ($kelimeler as $kelime) {
                        if (mb_strpos($baslik, $this->trk($kelime)) !== false) {
                            $hedef = $katId;
                            break 2;
                        }
                    }
                }

                DB::table('crm_sozlesmeler')->where('id', $k->id)
                    ->update(['kategori_id' => $hedef, 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            // atama başarısız olsa da kategoriler oluşmuş kalsın
        }
    }

    public function down(): void
    {
        // Yalnızca taşınan kayıtların kategorisini geri boşalt
        try {
            DB::table('crm_sozlesmeler')->where('kaynak', 'musteri_sozlesmeler')
                ->update(['kategori_id' => null]);
        } catch (\Throwable $e) {}
    }
};
