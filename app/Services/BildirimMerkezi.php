<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BİLDİRİM MERKEZİ.
 *
 * Sistemde 12 ayrı hatırlatma komutu var ve her biri kendi log tablosuna yazıyor
 * (domain_yenileme_log, odeme_hatirlatma_log, hizmet_bitis_log...). Bu yüzden
 * "neye, ne zaman, kime bildirim gitti / gidecek" sorusunun tek cevabı yoktu.
 *
 * Bu servis komutları DEĞİŞTİRMEZ — sadece hepsini tek yerde toplar:
 *   yaklasanlar() → önümüzdeki günlerde hatırlatma tetikleyecek kayıtlar
 *   gonderilenler() → geçmişte gönderilmiş bildirimlerin birleşik kaydı
 *   zamanlama()   → hangi hatırlatma hangi saatte çalışıyor
 */
class BildirimMerkezi
{
    /**
     * Hatırlatma türleri: ekranda renk/etiket ve hangi cron'un ilgilendiği.
     * [anahtar => [etiket, ikon, cron, saat]]
     */
    public const TURLER = [
        'domain'    => ['Domain / Hosting', 'globe',        'domain-yenileme-hatirlat', '10:00'],
        'sozlesme'  => ['Sözleşme',         'file-text',    'sozlesme-hatirlat',        '09:45'],
        'fatura'    => ['Fatura',           'receipt',      'odeme-hatirlat',           '09:00'],
        'alacak'    => ['Aylık Alacak',     'hand-coins',   'alacak-hatirlat',          '09:15'],
        'odeme'     => ['Aylık Ödeme',      'calendar-clock','aylik-odeme-hatirlat',    '09:30'],
        'randevu'   => ['Randevu',          'calendar',     'randevu-hatirlat',         'her dk'],
    ];

    /** Aciliyet rengi — gün sayısına göre */
    public static function aciliyet(?int $gun): array
    {
        if ($gun === null)  return ['sinif' => 'badge-neutral', 'metin' => 'tarih yok'];
        if ($gun < 0)       return ['sinif' => 'badge-danger',  'metin' => abs($gun) . ' gün geçti'];
        if ($gun === 0)     return ['sinif' => 'badge-danger',  'metin' => 'bugün'];
        if ($gun <= 3)      return ['sinif' => 'badge-warning', 'metin' => $gun . ' gün kaldı'];
        if ($gun <= 7)      return ['sinif' => 'badge-info',    'metin' => $gun . ' gün kaldı'];

        return ['sinif' => 'badge-neutral', 'metin' => $gun . ' gün'];
    }

    private static function gunFarki($tarih): ?int
    {
        if (empty($tarih)) return null;
        try {
            return (int) Carbon::today()->diffInDays(Carbon::parse($tarih)->startOfDay(), false);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Önümüzdeki $gun gün içinde hatırlatma tetikleyecek kayıtlar — tek liste.
     *
     * @param  int         $gun  kaç gün ileriye bakılsın
     * @param  string|null $tur  tek tür filtresi (TURLER anahtarı)
     */
    public static function yaklasanlar(int $gun = 30, ?string $tur = null): Collection
    {
        $liste = collect();
        $bugun = Carbon::today()->toDateString();
        $son   = Carbon::today()->addDays($gun)->toDateString();
        // Geçmişte kalanları da göster (gecikmiş hatırlatmalar gözden kaçmasın)
        $bas   = Carbon::today()->subDays(30)->toDateString();

        /* ── DOMAIN / HOSTING ── */
        if ((!$tur || $tur === 'domain') && Schema::hasTable('satilanlar')) {
            try {
                $q = DB::table('satilanlar as s')
                    ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                    ->leftJoin('crm_customers as c', 'c.id', '=', 's.crm_musteri_id')
                    ->whereNotNull('s.bitis_tarih')->where('s.bitis_tarih', '<>', '')
                    ->whereTarihBetween('s.bitis_tarih', $bas, $son)
                    ->select(
                        's.id', 's.domain as baslik', 's.bitis_tarih as tarih', 's.tutar',
                        DB::raw('COALESCE(c.adi, CONCAT(COALESCE(u.ad,\'\'), \' \', COALESCE(u.soyad,\'\'))) as musteri'),
                        'u.email as email', 's.crm_musteri_id', 's.uyeid'
                    )
                    ->orderBy('s.bitis_tarih')->limit(300)->get();

                foreach ($q as $r) {
                    $liste->push(self::satir('domain', $r->id, $r->baslik, $r->musteri, $r->email, $r->tarih, $r->tutar, $r->crm_musteri_id, $r->uyeid));
                }
            } catch (\Throwable $e) {
            }
        }

        /* ── SÖZLEŞME ── */
        if ((!$tur || $tur === 'sozlesme') && Schema::hasTable('crm_sozlesmeler')
            && Schema::hasColumn('crm_sozlesmeler', 'bitis_tarihi')) {
            try {
                $q = DB::table('crm_sozlesmeler as z')
                    ->leftJoin('crm_customers as c', 'c.id', '=', 'z.musteri_id')
                    ->whereNotNull('z.bitis_tarihi')
                    ->whereTarihBetween('z.bitis_tarihi', $bas, $son)
                    ->when(Schema::hasColumn('crm_sozlesmeler', 'ana_sozlesme_id'), fn ($x) => $x)
                    ->select('z.id', 'z.baslik', 'z.bitis_tarihi as tarih', 'z.tutar',
                             DB::raw('COALESCE(c.adi, z.taraf_adi) as musteri'), 'c.email', 'z.musteri_id as crm_musteri_id')
                    ->orderBy('z.bitis_tarihi')->limit(300)->get();

                foreach ($q as $r) {
                    $liste->push(self::satir('sozlesme', $r->id, $r->baslik, $r->musteri, $r->email, $r->tarih, $r->tutar, $r->crm_musteri_id));
                }
            } catch (\Throwable $e) {
            }
        }

        /* ── FATURA (ödenmemiş, vadesi yaklaşan) ── */
        if ((!$tur || $tur === 'fatura') && Schema::hasTable('faturalar')) {
            try {
                $q = DB::table('faturalar as f')
                    ->leftJoin('uyeler as u', 'u.id', '=', 'f.uyeid')
                    ->where('f.durum', 0)
                    ->whereNotNull('f.bitis_tarih')->where('f.bitis_tarih', '<>', '')
                    ->whereTarihBetween('f.bitis_tarih', $bas, $son)
                    ->select('f.id', 'f.baslik', 'f.bitis_tarih as tarih', 'f.tutar',
                             DB::raw('CONCAT(COALESCE(u.ad,\'\'), \' \', COALESCE(u.soyad,\'\')) as musteri'),
                             'u.email', DB::raw('NULL as crm_musteri_id'), 'f.uyeid')
                    ->orderBy('f.bitis_tarih')->limit(300)->get();

                foreach ($q as $r) {
                    $liste->push(self::satir('fatura', $r->id, $r->baslik, $r->musteri, $r->email, $r->tarih, $r->tutar, null, $r->uyeid));
                }
            } catch (\Throwable $e) {
            }
        }

        /* ── AYLIK ALACAK ── */
        if ((!$tur || $tur === 'alacak') && Schema::hasTable('aylik_alacaklar')) {
            try {
                $q = DB::table('aylik_alacaklar as a')
                    ->leftJoin('crm_customers as c', 'c.id', '=', 'a.musteri_id')
                    ->where('a.durum', 'bekliyor')->where('a.aktif', 1)
                    ->whereNotNull('a.son_tahsil_tarihi')
                    ->whereTarihBetween('a.son_tahsil_tarihi', $bas, $son)
                    ->select('a.id', 'a.baslik', 'a.son_tahsil_tarihi as tarih', 'a.tutar',
                             DB::raw('COALESCE(c.adi, a.musteri_adi) as musteri'), 'c.email', 'a.musteri_id as crm_musteri_id')
                    ->orderBy('a.son_tahsil_tarihi')->limit(300)->get();

                foreach ($q as $r) {
                    $liste->push(self::satir('alacak', $r->id, $r->baslik, $r->musteri, $r->email, $r->tarih, $r->tutar, $r->crm_musteri_id));
                }
            } catch (\Throwable $e) {
            }
        }

        /* ── AYLIK ÖDEME (bizim ödeyeceklerimiz) ── */
        if ((!$tur || $tur === 'odeme') && Schema::hasTable('aylik_odemeler')) {
            try {
                $q = DB::table('aylik_odemeler')
                    ->where('durum', 'bekliyor')
                    ->whereNotNull('son_odeme_tarihi')
                    ->whereTarihBetween('son_odeme_tarihi', $bas, $son)
                    ->select('id', 'baslik', 'son_odeme_tarihi as tarih', 'tutar',
                             DB::raw('NULL as musteri'), DB::raw('NULL as email'), DB::raw('NULL as crm_musteri_id'))
                    ->orderBy('son_odeme_tarihi')->limit(200)->get();

                foreach ($q as $r) {
                    $liste->push(self::satir('odeme', $r->id, $r->baslik, $r->musteri, $r->email, $r->tarih, $r->tutar, null));
                }
            } catch (\Throwable $e) {
            }
        }

        return self::crmIdleriDoldur($liste)->sortBy('gun')->values();
    }

    /**
     * satilanlar/faturalar kayıtlarının çoğunda crm_musteri_id boş (sütun sonradan geldi).
     * Bu yüzden "Aç" sütunu hep boş kalıyordu. Eksikleri önce uye_id, sonra e-posta
     * üzerinden crm_customers ile eşleştirip tamamlıyoruz — CustomerController ile aynı mantık.
     * İki ek sorgu; satır başına sorgu YOK.
     */
    private static function crmIdleriDoldur(Collection $liste): Collection
    {
        if ($liste->isEmpty() || !Schema::hasTable('crm_customers')) return $liste;

        $eksik = $liste->filter(fn ($x) => empty($x['crm_id']));
        if ($eksik->isEmpty()) return $liste;

        $uyeHarita = [];
        $uyeIdler = $eksik->pluck('uye_id')->filter()->unique()->values();
        if ($uyeIdler->isNotEmpty() && Schema::hasColumn('crm_customers', 'uye_id')) {
            $uyeHarita = DB::table('crm_customers')->whereIn('uye_id', $uyeIdler)
                ->whereNull('birlesen_id')->pluck('id', 'uye_id')->all();
        }

        $mailHarita = [];
        $mailler = $eksik->pluck('email')->filter(fn ($e) => trim((string) $e) !== '')
            ->map(fn ($e) => mb_strtolower(trim($e)))->unique()->values();
        if ($mailler->isNotEmpty()) {
            $mailHarita = DB::table('crm_customers')->whereIn(DB::raw('LOWER(email)'), $mailler)
                ->whereNull('birlesen_id')
                ->select('id', DB::raw('LOWER(email) as e'))->pluck('id', 'e')->all();
        }

        return $liste->map(function ($x) use ($uyeHarita, $mailHarita) {
            if (!empty($x['crm_id'])) return $x;
            if (!empty($x['uye_id']) && isset($uyeHarita[$x['uye_id']])) {
                $x['crm_id'] = $uyeHarita[$x['uye_id']];
                return $x;
            }
            $e = mb_strtolower(trim((string) $x['email']));
            if ($e !== '' && isset($mailHarita[$e])) $x['crm_id'] = $mailHarita[$e];

            return $x;
        });
    }

    private static function satir(string $tur, $id, $baslik, $musteri, $email, $tarih, $tutar, $crmId, $uyeId = null): array
    {
        $gun = self::gunFarki($tarih);

        return [
            'tur'      => $tur,
            'tur_ad'   => self::TURLER[$tur][0] ?? $tur,
            'ikon'     => self::TURLER[$tur][1] ?? 'bell',
            'id'       => $id,
            'baslik'   => (string) ($baslik ?: '—'),
            'musteri'  => trim((string) $musteri) ?: '—',
            'email'    => (string) $email,
            'tarih'    => $tarih,
            'tutar'    => $tutar !== null && $tutar !== '' ? (float) $tutar : null,
            'gun'      => $gun,
            'aciliyet' => self::aciliyet($gun),
            'crm_id'   => $crmId ?: null,
            'uye_id'   => $uyeId ?: null,
            'link'     => self::urunLinki($tur, $id),
        ];
    }

    /**
     * "Aç" sütunu — hatırlatmanın ait olduğu ÜRÜNE/HİZMETE (fatura, domain
     * kaydı, sözleşme...) direkt götürür. Önceden burası hep CRM müşteri
     * kartına gidiyordu; Nesimi Bey'in isteğiyle (07.08.2026) değiştirildi.
     */
    private static function urunLinki(string $tur, $id): ?string
    {
        try {
            return match ($tur) {
                'fatura'   => \Illuminate\Support\Facades\Route::has('admin.faturalar.detay')
                    ? route('admin.faturalar.detay', $id) : null,
                'domain'   => \Illuminate\Support\Facades\Route::has('admin.crm.domains.show')
                    ? route('admin.crm.domains.show', ['kaynak' => 'manuel', 'id' => $id]) : null,
                'sozlesme' => \Illuminate\Support\Facades\Route::has('admin.crm.sozlesmeler.edit')
                    ? route('admin.crm.sozlesmeler.edit', $id) : null,
                'alacak'   => \Illuminate\Support\Facades\Route::has('admin.aylik-alacaklar.duzenle')
                    ? route('admin.aylik-alacaklar.duzenle', $id) : null,
                'odeme'    => \Illuminate\Support\Facades\Route::has('admin.aylik-odemeler.duzenle')
                    ? route('admin.aylik-odemeler.duzenle', $id) : null,
                default    => null,
            };
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Geçmişte gönderilen bildirimler — üç log tablosu birleşik */
    public static function gonderilenler(int $limit = 200, ?string $tur = null): Collection
    {
        $kaynaklar = [
            ['domain_yenileme_log',  'domain', 'domain_order_id'],
            ['odeme_hatirlatma_log', 'fatura', 'fatura_id'],
            ['hizmet_bitis_log',     'domain', 'satilanlar_id'],
        ];

        $liste = collect();

        foreach ($kaynaklar as [$tablo, $turAdi, $idKolonu]) {
            if ($tur && $tur !== $turAdi) continue;
            if (!Schema::hasTable($tablo)) continue;

            try {
                foreach (DB::table($tablo)->orderByDesc('id')->limit($limit)->get() as $r) {
                    $liste->push([
                        'tur'        => $turAdi,
                        'tur_ad'     => self::TURLER[$turAdi][0] ?? $turAdi,
                        'ikon'       => self::TURLER[$turAdi][1] ?? 'bell',
                        'kaynak'     => $tablo,
                        'ilgili_id'  => $r->$idKolonu ?? null,
                        'email'      => $r->email ?? '',
                        'kalan_gun'  => $r->kalan_gun ?? null,
                        'tarih'      => $r->gonderim_tarihi ?? $r->created_at ?? null,
                        'basarili'   => in_array((string) ($r->durum ?? ''), ['1', 'basarili', 'ok', 'gonderildi'], true),
                        'hata'       => $r->hata ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
            }
        }

        return $liste->sortByDesc('tarih')->take($limit)->values();
    }

    /** Zamanlanmış hatırlatmalar + son çalışma bilgisi */
    public static function zamanlama(): array
    {
        $sonlar = [];
        foreach ([
            'domain' => 'domain_yenileme_log',
            'fatura' => 'odeme_hatirlatma_log',
        ] as $tur => $tablo) {
            if (!Schema::hasTable($tablo)) continue;
            try {
                $sonlar[$tur] = DB::table($tablo)->max('gonderim_tarihi');
            } catch (\Throwable $e) {
            }
        }

        $satirlar = [];
        foreach (self::TURLER as $anahtar => [$etiket, $ikon, $cron, $saat]) {
            $satirlar[] = [
                'tur'    => $anahtar,
                'etiket' => $etiket,
                'ikon'   => $ikon,
                'cron'   => $cron,
                'saat'   => $saat,
                'son'    => $sonlar[$anahtar] ?? null,
            ];
        }

        return $satirlar;
    }

    /** Üst kutular için özet */
    public static function ozet(Collection $yaklasanlar): array
    {
        return [
            'gecikmis' => $yaklasanlar->filter(fn ($x) => $x['gun'] !== null && $x['gun'] < 0)->count(),
            'bugun'    => $yaklasanlar->filter(fn ($x) => $x['gun'] === 0)->count(),
            'hafta'    => $yaklasanlar->filter(fn ($x) => $x['gun'] !== null && $x['gun'] > 0 && $x['gun'] <= 7)->count(),
            'toplam'   => $yaklasanlar->count(),
            'tutar'    => $yaklasanlar->sum(fn ($x) => (float) ($x['tutar'] ?? 0)),
        ];
    }
}
