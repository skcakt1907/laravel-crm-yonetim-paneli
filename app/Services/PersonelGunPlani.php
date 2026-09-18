<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GÜN PLANI — tek kaynak.
 *
 * Saatli program: her satır bir iş. Kişi kendi gününü planlar,
 * yöneticiler herkesinkini görür.
 *
 * DURUM BİLDİRİMİNDEN AYRI (bilinçli): plandaki saat gelince durum
 * kendiliğinden değişmiyor. Plan sapabilir; otomatik değişseydi pano
 * yanlış bilgi gösterir, o yanlış bilgi de maille 3 yöneticiye giderdi.
 */
class PersonelGunPlani
{
    /**
     * Bir günün planı, kişi kişi gruplanmış.
     *
     * Tek sorguyla çekiliyor; kişi başına ayrı sorgu atılsaydı 15 kişilik
     * ekipte her sayfa açılışı 15 sorgu açardı.
     */
    public static function gun(Carbon $tarih)
    {
        if (! Schema::hasTable('personel_gun_plani')) {
            return collect();
        }

        $satirlar = DB::table('personel_gun_plani as p')
            ->leftJoin('yoneticiler as o', 'o.id', '=', 'p.olusturan_id')
            ->whereDate('p.tarih', $tarih->toDateString())
            ->orderBy('p.baslangic')->orderBy('p.id')
            ->select('p.*', 'o.adi as olusturan_adi')
            ->get();

        return $satirlar->groupBy('yonetici_id');
    }

    /** Bir kişinin bir günlük planı. */
    public static function kisiGun(int $yoneticiId, Carbon $tarih)
    {
        if (! Schema::hasTable('personel_gun_plani')) {
            return collect();
        }

        return DB::table('personel_gun_plani')
            ->where('yonetici_id', $yoneticiId)
            ->whereDate('tarih', $tarih->toDateString())
            ->orderBy('baslangic')->orderBy('id')
            ->get();
    }

    /** Aktif personel — plan ekranında satırları bunlara göre diziliyor. */
    public static function personeller()
    {
        return DB::table('yoneticiler')
            ->where('durum', 1)
            ->orderBy('adi')
            ->get(['id', 'adi', 'kullaniciadi', 'profil_foto']);
    }

    /**
     * Plan satırı ekler.
     *
     * Bitiş saati başlangıçtan önce olamaz; gece yarısını aşan vardiya
     * bu panelde yok, öyle bir kayıt girilirse ekranda sıralama bozulur.
     */
    public static function ekle(array $veri): void
    {
        DB::table('personel_gun_plani')->insert([
            'yonetici_id'  => $veri['yonetici_id'],
            'tarih'        => $veri['tarih'],
            'baslangic'    => $veri['baslangic'],
            'bitis'        => $veri['bitis'] ?? null,
            'baslik'       => $veri['baslik'],
            'aciklama'     => $veri['aciklama'] ?? null,
            'tamamlandi'   => false,
            'olusturan_id' => $veri['olusturan_id'] ?? $veri['yonetici_id'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * Bir günün planını başka bir güne kopyalar.
     *
     * Çoğu gün bir öncekine benziyor; her sabah aynı satırları elle
     * yazmak insanları planı hiç doldurmamaya iter.
     * Hedef günde kayıt varsa üstüne EKLER, silmez.
     */
    public static function kopyala(int $yoneticiId, Carbon $kaynak, Carbon $hedef): int
    {
        $satirlar = self::kisiGun($yoneticiId, $kaynak);

        if ($satirlar->isEmpty()) {
            return 0;
        }

        $yeni = $satirlar->map(fn ($s) => [
            'yonetici_id'  => $yoneticiId,
            'tarih'        => $hedef->toDateString(),
            'baslangic'    => $s->baslangic,
            'bitis'        => $s->bitis,
            'baslik'       => $s->baslik,
            'aciklama'     => $s->aciklama,
            'tamamlandi'   => false,   // kopya yeni gün, işaretler sıfırlanır
            'olusturan_id' => $yoneticiId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ])->all();

        DB::table('personel_gun_plani')->insert($yeni);

        return count($yeni);
    }

    /** Gün özeti: kaç iş, kaçı tamamlandı, toplam kaç saat. */
    public static function ozet($satirlar): array
    {
        $toplamDakika = 0;

        foreach ($satirlar as $s) {
            if (! $s->bitis) {
                continue;   // bitişi belirsiz iş süreye katılmıyor
            }

            $bas = Carbon::parse($s->baslangic);
            $bit = Carbon::parse($s->bitis);

            if ($bit->greaterThan($bas)) {
                $toplamDakika += $bas->diffInMinutes($bit);
            }
        }

        return [
            'adet'       => count($satirlar),
            'tamamlanan' => collect($satirlar)->where('tamamlandi', 1)->count(),
            'saat'       => round($toplamDakika / 60, 1),
        ];
    }
}
