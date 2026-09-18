<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * İşlem Geçmişi + Geri-Al motoru (Faz 0).
 *
 * Panelde (ileride AI asistanı ile) yapılan yazma işlemleri buradan geçer:
 *   1) İşlemi uygulamadan ÖNCE etkilenen satırların ESKİ hâlini topla
 *   2) İşlemi uygula
 *   3) IslemGecmisi::kaydet(...) ile eski/yeni hâli logla
 * Sonra istenirse IslemGecmisi::geriAl($id) eski hâli geri yazar.
 *
 * Kullanım (örnek):
 *   $eski = (array) DB::table('yazilimlar')->where('id',$id)->first();
 *   DB::table('yazilimlar')->where('id',$id)->update(['baslik'=>$yeni]);
 *   IslemGecmisi::kaydet('guncelle', "'{$eski['adi']}' paketinin başlığı değiştirildi", [[
 *       'tablo' => 'yazilimlar', 'id' => $id,
 *       'eski'  => ['baslik' => $eski['baslik']],
 *       'yeni'  => ['baslik' => $yeni],
 *   ]], 'manuel');
 */
class IslemGecmisi
{
    /**
     * Geri-al sırasında YAZILMASINA izin verilen tablolar (güvenlik beyaz listesi).
     * Bozulmuş/kurcalanmış bir kayıt, listede olmayan bir tabloyu ASLA güncelleyemez.
     */
    private const IZINLI_TABLOLAR = [
        'yazilimlar', 'web_kategori', 'kampanyalar', 'crm_customers',
        'faturalar', 'referanslar', 'blog', 'hostingler', 'satilanlar',
        'destek', 'randevular', 'uyeler', 'crm_musteri_teklifleri',
        // Geri alınabilir silme için eklendi (31.07.2026)
        'crm_tasks', 'crm_notes', 'crm_opportunities', 'crm_sozlesmeler',
    ];

    /**
     * SİLME İŞLEMİNİ KAYDEDER — geri alınabilir olsun diye satırın TAMAMINI saklar.
     *
     * Silmeden ÖNCE çağrılmalı; satır hâlâ dururken okunur.
     * Bağlı alt kayıtlar da verilirse (not, görev vb.) onlar da geri gelir.
     *
     * Kullanım:
     *   IslemGecmisi::silmeKaydet('crm_customers', $id, 'Müşteri silindi: Ahmet Y.', [
     *       ['tablo' => 'crm_notes', 'kolon' => 'musteri_id'],
     *       ['tablo' => 'crm_tasks', 'kolon' => 'musteri_id'],
     *   ]);
     *   DB::table('crm_customers')->where('id', $id)->delete();
     *
     * @param  string $tablo      ana tablo
     * @param  int    $id         silinecek satırın id'si
     * @param  string $aciklama   insan diliyle özet
     * @param  array  $altKayitlar [['tablo'=>?, 'kolon'=>?], ...] birlikte silinecek alt kayıtlar
     * @return int    işlem geçmişi kaydının id'si (0 = kaydedilemedi)
     */
    public static function silmeKaydet(string $tablo, int $id, string $aciklama, array $altKayitlar = [], string $kaynak = 'manuel'): int
    {
        if (!in_array($tablo, self::IZINLI_TABLOLAR, true)) {
            return 0;   // beyaz listede değilse geri alınamaz, kaydetmenin anlamı yok
        }

        $satir = DB::table($tablo)->where('id', $id)->first();
        if (!$satir) return 0;

        $etkilenen = [[
            'tablo' => $tablo,
            'id'    => $id,
            'islem' => 'sil',
            'eski'  => (array) $satir,   // satırın TAMAMI — geri alırken bu INSERT edilir
        ]];

        // Birlikte silinecek alt kayıtlar (her biri ayrı satır olarak saklanır)
        foreach ($altKayitlar as $alt) {
            if (empty($alt['tablo']) || empty($alt['kolon'])) continue;
            if (!in_array($alt['tablo'], self::IZINLI_TABLOLAR, true)) continue;
            if (!Schema::hasTable($alt['tablo']) || !Schema::hasColumn($alt['tablo'], $alt['kolon'])) continue;

            try {
                foreach (DB::table($alt['tablo'])->where($alt['kolon'], $id)->get() as $altSatir) {
                    $etkilenen[] = [
                        'tablo' => $alt['tablo'],
                        'id'    => $altSatir->id ?? null,
                        'islem' => 'sil',
                        'eski'  => (array) $altSatir,
                    ];
                }
            } catch (\Throwable $e) {
                // alt kayıt okunamazsa ana kayıt yine de saklansın
            }
        }

        return self::kaydet('sil', $aciklama, $etkilenen, $kaynak);
    }

    /**
     * Bir işlemi kaydeder.
     *
     * @param string $aksiyon    makine anahtarı (guncelle|sil|indirim|ekle...)
     * @param string $aciklama   insan diliyle özet
     * @param array  $etkilenen  [['tablo'=>?, 'id'=>?, 'eski'=>[kol=>deger], 'yeni'=>[kol=>deger]], ...]
     * @param string $kaynak     'manuel' | 'ai'
     * @return int  oluşturulan işlem geçmişi kaydının id'si
     */
    public static function kaydet(string $aksiyon, string $aciklama, array $etkilenen, string $kaynak = 'manuel'): int
    {
        // Geri alınabilir olması için en az bir etkilenen satırın ESKİ hâli gerekir
        $geriAlinabilir = false;
        foreach ($etkilenen as $e) {
            if (!empty($e['tablo']) && isset($e['id']) && !empty($e['eski']) && is_array($e['eski'])) {
                $geriAlinabilir = true;
                break;
            }
        }

        return DB::table('islem_gecmisi')->insertGetId([
            'kullanici_id'    => session('admin_id') ?: null,
            'kullanici_adi'   => session('admin_adi') ?: session('admin_ad') ?: 'Sistem',
            'aksiyon'         => mb_substr($aksiyon, 0, 50),
            'aciklama'        => mb_substr($aciklama, 0, 500),
            'etkilenen'       => json_encode(array_values($etkilenen), JSON_UNESCAPED_UNICODE),
            'etkilenen_adet'  => count($etkilenen),
            'kaynak'          => $kaynak === 'ai' ? 'ai' : 'manuel',
            'geri_alinabilir' => $geriAlinabilir ? 1 : 0,
            'geri_alindi'     => 0,
            'tarih'           => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Bir işlemi geri alır: etkilenen her satırın ESKİ hâlini geri yazar.
     *
     * @return array{ok: bool, mesaj: string}
     */
    public static function geriAl(int $id): array
    {
        $kayit = DB::table('islem_gecmisi')->where('id', $id)->first();

        if (!$kayit)                  return ['ok' => false, 'mesaj' => 'İşlem bulunamadı.'];
        if ($kayit->geri_alindi)      return ['ok' => false, 'mesaj' => 'Bu işlem zaten geri alınmış.'];
        if (!$kayit->geri_alinabilir) return ['ok' => false, 'mesaj' => 'Bu işlem geri alınamaz.'];

        $etkilenen = json_decode($kayit->etkilenen, true);
        if (!is_array($etkilenen) || !$etkilenen) {
            return ['ok' => false, 'mesaj' => 'Geri alınacak veri bulunamadı.'];
        }

        /*
         * ── ÖN KONTROL (hiçbir şey yazmadan) ────────────────────────────────
         *
         * CRM tablolarının motoru MyISAM; DB::transaction() bu tablolarda
         * ÇALIŞMAZ (geri sarma yok). Bu yüzden yazmaya başlamadan önce her şeyi
         * doğruluyoruz — yarısı yazılıp yarısı hata veren bir geri alma olmasın.
         */
        $yazilacak = [];
        foreach ($etkilenen as $e) {
            if (empty($e['tablo']) || !isset($e['id']) || empty($e['eski']) || !is_array($e['eski'])) {
                continue;
            }
            if (!in_array($e['tablo'], self::IZINLI_TABLOLAR, true)) {
                continue;   // beyaz listede olmayan tabloya dokunma
            }
            if (!Schema::hasTable($e['tablo'])) {
                return ['ok' => false, 'mesaj' => "Geri alınamadı: '{$e['tablo']}' tablosu artık yok."];
            }

            $silmeMi = ($e['islem'] ?? '') === 'sil';
            $mevcut  = DB::table($e['tablo'])->where('id', $e['id'])->exists();

            /*
             * ID ÇAKIŞMASI — MyISAM silinen en büyük id'yi yeniden kullanır.
             * Silinen satırın id'si bu arada BAŞKA bir kayda verilmişse, eski
             * veriyi üzerine yazmak o yeni kaydı yok eder. Bu durumda hiçbir şey
             * yazmadan duruyoruz; sessizce veri kaybetmektense geri almayı reddet.
             */
            if ($silmeMi && $mevcut) {
                return ['ok' => false, 'mesaj' =>
                    "Geri alınamadı: '{$e['tablo']}' tablosunda {$e['id']} numaralı id'yi artık başka bir kayıt "
                    . 'kullanıyor. Üzerine yazmak o kaydı silerdi, bu yüzden işlem durduruldu.'];
            }

            // Şema değişmiş olabilir — tabloda artık olmayan kolonları at
            $kolonlar = Schema::getColumnListing($e['tablo']);
            $satir    = array_intersect_key($e['eski'], array_flip($kolonlar));
            if (!$satir) {
                return ['ok' => false, 'mesaj' => "Geri alınamadı: '{$e['tablo']}' tablosunun kolonları değişmiş."];
            }

            $yazilacak[] = ['tablo' => $e['tablo'], 'id' => $e['id'], 'satir' => $satir, 'ekle' => !$mevcut];
        }

        if (!$yazilacak) {
            return ['ok' => false, 'mesaj' => 'Hiçbir kayıt geri alınamadı (veri geçersiz).'];
        }

        /* ── YAZMA ── */
        $geriYazilan = 0;
        try {
            DB::transaction(function () use ($yazilacak, &$geriYazilan) {
                foreach ($yazilacak as $y) {
                    if ($y['ekle']) {
                        $y['satir']['id'] = $y['id'];   // id korunur, bağlı kayıtlar tutarlı kalsın
                        DB::table($y['tablo'])->insert($y['satir']);
                    } else {
                        DB::table($y['tablo'])->where('id', $y['id'])->update($y['satir']);
                    }
                    $geriYazilan++;
                }
            });
        } catch (\Throwable $ex) {
            $kalan = count($yazilacak) - $geriYazilan;

            return ['ok' => false, 'mesaj' => "Geri alma yarıda kesildi — {$geriYazilan} kayıt yazıldı, "
                . "{$kalan} kayıt yazılamadı. Hata: " . $ex->getMessage()];
        }

        DB::table('islem_gecmisi')->where('id', $id)->update([
            'geri_alindi'      => 1,
            'geri_alan_id'     => session('admin_id') ?: null,
            'geri_alma_tarihi' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'mesaj' => "İşlem geri alındı — {$geriYazilan} kayıt eski hâline döndürüldü."];
    }
}
