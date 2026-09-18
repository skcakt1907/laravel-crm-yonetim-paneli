<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * PERSONEL ANLIK DURUMU — tek kaynak.
 *
 * Ekran, sidebar listesi ve mail hep buradan besleniyor; aksi hâlde
 * panelde görünen durumla maildeki birbirini tutmaz.
 *
 * DİKKAT: Bu, PresenceController'daki OTOMATİK durumla (çevrimiçi/uzakta/
 * çevrimdışı) aynı şey değil. O, tarayıcı sinyalinden "bilgisayarı açık mı"
 * hesaplıyor. Burası kişinin kendi seçtiği "ne yapıyor" bilgisi. İkisi yan
 * yana gösteriliyor, biri diğerinin yerine geçmiyor.
 */
class PersonelDurumu
{
    /**
     * Mail alıcılarının varsayılan listesi (yoneticiler.kullaniciadi).
     *
     * Panelden seçim yapılmamışsa bunlara gider. Kurulumdan hemen sonra
     * mail çalışsın, "kimse seçilmemiş" diye susmasın diye.
     */
    public const VARSAYILAN_ALICILAR = ['nurselinan', 'NesimiAtes', 'dilanatescom'];

    /* ─────────────────────────────────────────────────────────────
     |  DURUM TİPLERİ
     * ───────────────────────────────────────────────────────────── */

    /** Panelde seçilebilir durumlar (pasife alınanlar hariç). */
    public static function tipler()
    {
        if (! Schema::hasTable('personel_durum_tipleri')) {
            return collect();
        }

        return DB::table('personel_durum_tipleri')
            ->where('aktif', 1)
            ->orderBy('sira')->orderBy('id')
            ->get();
    }

    /** Pasife alınanlar dahil bütün tipler — yönetim ekranı için. */
    public static function tiplerHepsi()
    {
        if (! Schema::hasTable('personel_durum_tipleri')) {
            return collect();
        }

        return DB::table('personel_durum_tipleri')
            ->orderBy('sira')->orderBy('id')
            ->get();
    }

    /* ─────────────────────────────────────────────────────────────
     |  DURUM OKUMA
     * ───────────────────────────────────────────────────────────── */

    /**
     * Bir kişinin şu anki durumu (yoksa null).
     *
     * "Şu anki" = bitis alanı boş olan son kayıt.
     */
    public static function suAnki(int $yoneticiId)
    {
        if (! Schema::hasTable('personel_durumlari')) {
            return null;
        }

        return DB::table('personel_durumlari as d')
            ->join('personel_durum_tipleri as t', 't.id', '=', 'd.durum_tipi_id')
            ->where('d.yonetici_id', $yoneticiId)
            ->whereNull('d.bitis')
            ->orderByDesc('d.id')
            ->select('d.id', 'd.not', 'd.baslangic', 't.ad', 't.emoji', 't.renk', 't.id as tip_id')
            ->first();
    }

    /**
     * Sidebar listesi: aktif personel + şu anki durumları.
     *
     * Tek sorguda çekiliyor; kişi başına ayrı sorgu atılırsa sidebar her
     * sayfada onlarca sorgu açar (panelin her ekranında yükleniyor).
     */
    public static function herkes()
    {
        if (! Schema::hasTable('personel_durumlari')) {
            return collect();
        }

        // Her yöneticinin açık durum kaydının id'si
        $sonlar = DB::table('personel_durumlari')
            ->selectRaw('yonetici_id, MAX(id) as son_id')
            ->whereNull('bitis')
            ->groupBy('yonetici_id');

        return DB::table('yoneticiler as y')
            ->leftJoinSub($sonlar, 's', 's.yonetici_id', '=', 'y.id')
            ->leftJoin('personel_durumlari as d', 'd.id', '=', 's.son_id')
            ->leftJoin('personel_durum_tipleri as t', 't.id', '=', 'd.durum_tipi_id')
            ->where('y.durum', 1)
            ->orderBy('y.adi')
            ->select(
                'y.id', 'y.adi', 'y.kullaniciadi', 'y.profil_foto',
                'y.son_gorulme', 'y.son_etkinlik',
                't.ad as durum_adi', 't.emoji as durum_emoji', 't.renk as durum_renk',
                'd.not as durum_notu', 'd.baslangic as durum_baslangic'
            )
            ->get();
    }

    /** Bir kişinin durum geçmişi (en yeni önce). */
    public static function gecmis(int $yoneticiId, int $limit = 50)
    {
        if (! Schema::hasTable('personel_durumlari')) {
            return collect();
        }

        return DB::table('personel_durumlari as d')
            ->join('personel_durum_tipleri as t', 't.id', '=', 'd.durum_tipi_id')
            ->leftJoin('yoneticiler as dg', 'dg.id', '=', 'd.degistiren_id')
            ->where('d.yonetici_id', $yoneticiId)
            ->orderByDesc('d.id')
            ->limit($limit)
            ->select('d.*', 't.ad', 't.emoji', 't.renk', 'dg.adi as degistiren_adi')
            ->get();
    }

    /* ─────────────────────────────────────────────────────────────
     |  DURUM YAZMA
     * ───────────────────────────────────────────────────────────── */

    /**
     * Durumu değiştirir. Dönen değer: mail gönderilmeli mi.
     *
     * AYNI DURUM ARKA ARKAYA SEÇİLİRSE yeni kayıt açılmaz ve mail gitmez —
     * yanlış tıklayıp geri alan biri iki mail göndermesin. Not değişmişse
     * kayıt güncellenir ama yine mail gitmez.
     */
    public static function degistir(int $yoneticiId, int $tipId, ?string $not = null, ?int $degistirenId = null): bool
    {
        $mevcut = self::suAnki($yoneticiId);

        // Aynı duruma tekrar geçiş: sadece notu tazele, mail yok
        if ($mevcut && (int) $mevcut->tip_id === $tipId) {
            if (($mevcut->not ?? null) !== $not) {
                DB::table('personel_durumlari')->where('id', $mevcut->id)
                    ->update(['not' => $not, 'updated_at' => now()]);
            }

            return false;
        }

        DB::transaction(function () use ($yoneticiId, $tipId, $not, $degistirenId) {
            // Önceki durumu kapat — tarihçe böyle oluşuyor
            DB::table('personel_durumlari')
                ->where('yonetici_id', $yoneticiId)
                ->whereNull('bitis')
                ->update(['bitis' => now(), 'updated_at' => now()]);

            DB::table('personel_durumlari')->insert([
                'yonetici_id'    => $yoneticiId,
                'durum_tipi_id'  => $tipId,
                'not'            => $not,
                'baslangic'      => now(),
                'degistiren_id'  => $degistirenId ?: $yoneticiId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });

        // Bu durum tipi için mail kapatılmış olabilir
        return (bool) DB::table('personel_durum_tipleri')->where('id', $tipId)->value('mail_gonder');
    }

    /* ─────────────────────────────────────────────────────────────
     |  MAİL
     * ───────────────────────────────────────────────────────────── */

    /** Bildirim gidecek e-posta adresleri. */
    public static function alicilar(): array
    {
        try {
            if (Schema::hasTable('personel_durum_alicilari')) {
                $secilen = DB::table('personel_durum_alicilari as a')
                    ->join('yoneticiler as y', 'y.id', '=', 'a.yonetici_id')
                    ->where('y.durum', 1)
                    ->whereNotNull('y.email')->where('y.email', '!=', '')
                    ->pluck('y.email')->unique()->values()->all();

                if (! empty($secilen)) {
                    return $secilen;
                }
            }

            return DB::table('yoneticiler')
                ->whereIn('kullaniciadi', self::VARSAYILAN_ALICILAR)
                ->where('durum', 1)
                ->whereNotNull('email')->where('email', '!=', '')
                ->pluck('email')->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Panelde seçili alıcıların id'leri. */
    public static function seciliIdler(): array
    {
        try {
            if (! Schema::hasTable('personel_durum_alicilari')) {
                return [];
            }

            return DB::table('personel_durum_alicilari')->pluck('yonetici_id')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Durum değişikliği mailini gönderir.
     *
     * Mail gönderimi rezervasyonun/işin akışını KESMEMELİ: SMTP çökse bile
     * durum değişikliği kaydedilmiş olmalı, o yüzden her şey try/catch içinde.
     */
    public static function mailGonder(int $yoneticiId, int $tipId, ?string $not = null): void
    {
        try {
            $alicilar = self::alicilar();

            if (empty($alicilar)) {
                return;
            }

            $kisi = DB::table('yoneticiler')->where('id', $yoneticiId)->first(['adi', 'kullaniciadi']);
            $tip  = DB::table('personel_durum_tipleri')->where('id', $tipId)->first();

            if (! $kisi || ! $tip) {
                return;
            }

            $ad    = $kisi->adi ?: $kisi->kullaniciadi;
            $durum = trim(($tip->emoji ?? '') . ' ' . $tip->ad);
            $konu  = $ad . ' — ' . $durum;
            $govde = self::mailGovdesi($ad, $tip, $not);

            foreach ($alicilar as $adres) {
                \App\Services\EmailNotificationService::send($adres, $konu, $govde, false, 'Personel Durumu');
            }
        } catch (\Throwable $e) {
            Log::warning('Personel durum maili gönderilemedi', [
                'yonetici_id' => $yoneticiId,
                'hata' => $e->getMessage(),
            ]);
        }
    }

    private static function mailGovdesi(string $ad, object $tip, ?string $not): string
    {
        $renk = $tip->renk ?: '#6b7280';

        $h = '<p style="margin:0 0 14px;font-size:14px;color:#5b6168">'
           . e($ad) . ' durumunu değiştirdi.</p>';

        $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px"><tr>'
            . '<td style="padding:16px;background:' . $renk . '14;border-left:4px solid ' . $renk . ';border-radius:8px">'
            . '<div style="font-size:20px;font-weight:800;color:' . $renk . '">'
            . e(trim(($tip->emoji ?? '') . ' ' . $tip->ad))
            . '</div>';

        if (filled($not)) {
            $h .= '<div style="font-size:13px;color:#5b6168;margin-top:6px">' . e($not) . '</div>';
        }

        $h .= '</td></tr></table>';

        $h .= '<p style="margin:0;font-size:11px;color:#9aa0a6">'
            . now()->translatedFormat('d F Y, H:i') . ' — bu bildirim durum her değiştiğinde otomatik gönderilir.</p>';

        return $h;
    }
}
