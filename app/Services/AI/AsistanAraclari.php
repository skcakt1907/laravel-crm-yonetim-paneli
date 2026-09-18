<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;

/**
 * AI Asistan — ARAÇ SETİ (Faz 1a: SADECE OKUMA).
 *
 * Claude'a "şu fonksiyonları çağırabilirsin" diye bu liste verilir. Claude ham SQL
 * YAZAMAZ; sadece buradaki adları ve tanımlı parametreleri kullanabilir. Sorguyu
 * her zaman BİZİM sunucumuz çalıştırır.
 *
 * Faz 1b (yazma aksiyonları) eklenirken: 'yazma' => true olan araçlar doğrudan
 * çalıştırılmaz; önce ÖNİZLEME üretilir, kullanıcı onaylar, sonra IslemGecmisi'ne
 * yazılarak uygulanır (geri alınabilir).
 */
class AsistanAraclari
{
    /** Tek seferde döndürülecek azami satır (token + KVKK koruması). */
    private const AZAMI_SATIR = 25;

    /**
     * Claude'a gönderilecek araç tanımları (Anthropic tool schema).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tanimlar(): array
    {
        return [
            [
                'name' => 'panel_ozet',
                'description' => 'Paneldeki genel sayıları verir: üye sayısı, CRM müşteri sayısı, '
                    . 'aktif/pasif paket sayısı, referans sayısı, toplam satış ve fatura adedi. '
                    . '"Kaç müşterimiz var", "genel durum nedir" gibi sorularda kullan.',
                'inputSchema' => ['type' => 'object', 'properties' => (object) [], 'required' => []],
            ],
            [
                'name' => 'paket_listele',
                'description' => 'Satıştaki hizmet/yazılım paketlerini listeler (ad, kategori, fiyat, durum). '
                    . 'Kategoriye göre veya isimde geçen kelimeye göre filtrelenebilir.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'kategori' => ['type' => 'string', 'description' => 'Kategori adı, örn: "Sosyal Medya". Boş bırakılırsa tüm kategoriler.'],
                        'ara'      => ['type' => 'string', 'description' => 'Paket adında aranacak kelime.'],
                        'sadece_aktif' => ['type' => 'boolean', 'description' => 'true ise sadece yayında olan paketler (varsayılan true).'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'kategori_listele',
                'description' => 'Hizmet/paket kategorilerinin listesini ve her birindeki paket sayısını verir.',
                'inputSchema' => ['type' => 'object', 'properties' => (object) [], 'required' => []],
            ],
            [
                'name' => 'musteri_ara',
                'description' => 'CRM müşterilerinde arama yapar (firma adı, unvan, e-posta, telefon, şehir). '
                    . 'Kişisel veriler maskelenerek döner.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'sorgu' => ['type' => 'string', 'description' => 'Aranacak kelime: firma adı, isim veya e-posta parçası.'],
                        'sehir' => ['type' => 'string', 'description' => 'İl filtresi (opsiyonel).'],
                    ],
                    'required' => ['sorgu'],
                ],
            ],
            [
                'name' => 'satis_ozet',
                'description' => 'Belirtilen son N gündeki satışların adedini ve toplam tutarını verir. '
                    . '"Bu ay ne kadar satış yaptık" gibi sorularda kullan.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'gun' => ['type' => 'integer', 'description' => 'Kaç günlük geriye bakılacak (varsayılan 30, azami 365).'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'fatura_ozet',
                'description' => 'Faturaların durumuna göre adet ve toplam tutar özetini verir '
                    . '(ödenen / bekleyen ayrımı dâhil).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'gun' => ['type' => 'integer', 'description' => 'Son kaç güne bakılacak (varsayılan 90).'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'referans_listele',
                'description' => 'Referans (yapılan iş) kayıtlarını listeler. İsimde arama yapılabilir.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'ara' => ['type' => 'string', 'description' => 'Referans adında aranacak kelime.'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'son_islemler',
                'description' => 'Panelde son yapılan değişiklikleri (İşlem Geçmişi) listeler; hangisi geri alınmış görünür.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'adet' => ['type' => 'integer', 'description' => 'Kaç kayıt (varsayılan 10, azami 25).'],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    /** Bu araç veriyi DEĞİŞTİRİR mi? (Faz 1a'da hepsi false.) */
    public static function yazmaAraci(string $ad): bool
    {
        return in_array($ad, [], true);
    }

    /**
     * Claude'un istediği aracı çalıştırır ve sonucu metin olarak döndürür.
     * Bilinmeyen araç adı sessizce çalışmaz — hata metni döner.
     */
    public static function calistir(string $ad, array $girdi): string
    {
        try {
            $sonuc = match ($ad) {
                'panel_ozet'       => self::panelOzet(),
                'paket_listele'    => self::paketListele($girdi),
                'kategori_listele' => self::kategoriListele(),
                'musteri_ara'      => self::musteriAra($girdi),
                'satis_ozet'       => self::satisOzet($girdi),
                'fatura_ozet'      => self::faturaOzet($girdi),
                'referans_listele' => self::referansListele($girdi),
                'son_islemler'     => self::sonIslemler($girdi),
                default            => ['hata' => "Bilinmeyen araç: {$ad}"],
            };
        } catch (\Throwable $e) {
            \Log::warning('AI aracı hatası', ['arac' => $ad, 'mesaj' => $e->getMessage()]);
            $sonuc = ['hata' => 'Veri okunurken bir sorun oluştu.'];
        }

        return json_encode($sonuc, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // ───────────────────────── araçların gövdesi ─────────────────────────

    private static function panelOzet(): array
    {
        return [
            'uye_sayisi'          => (int) DB::table('uyeler')->count(),
            'crm_musteri_sayisi'  => (int) DB::table('crm_customers')->count(),
            'paket_toplam'        => (int) DB::table('yazilimlar')->count(),
            'paket_yayinda'       => (int) DB::table('yazilimlar')->where('durum', 1)->count(),
            'kategori_sayisi'     => (int) DB::table('web_kategori')->where('durum', 1)->count(),
            'referans_sayisi'     => (int) DB::table('referanslar')->count(),
            'satis_kaydi'         => (int) DB::table('satilanlar')->count(),
            'fatura_kaydi'        => (int) DB::table('faturalar')->count(),
        ];
    }

    private static function paketListele(array $g): array
    {
        $q = DB::table('yazilimlar as y')
            ->leftJoin('web_kategori as k', 'k.id', '=', 'y.kategori')
            ->select('y.id', 'y.adi', 'y.tutar', 'y.durum', 'k.adi as kategori_adi');

        if (($g['sadece_aktif'] ?? true) !== false) {
            $q->where('y.durum', 1);
        }
        if (!empty($g['kategori'])) {
            $q->where('k.adi', 'like', '%' . $g['kategori'] . '%');
        }
        if (!empty($g['ara'])) {
            $q->where('y.adi', 'like', '%' . $g['ara'] . '%');
        }

        $toplam = (clone $q)->count();
        $satir  = $q->orderBy('y.adi')->limit(self::AZAMI_SATIR)->get();

        return [
            'toplam_bulunan' => $toplam,
            'gosterilen'     => $satir->count(),
            'paketler'       => $satir->map(fn ($p) => [
                'id'       => $p->id,
                'ad'       => $p->adi,
                'kategori' => $p->kategori_adi ?? '-',
                'fiyat'    => (float) $p->tutar,
                'yayinda'  => (bool) $p->durum,
            ])->all(),
        ];
    }

    private static function kategoriListele(): array
    {
        $satir = DB::table('web_kategori as k')
            ->leftJoin('yazilimlar as y', 'y.kategori', '=', 'k.id')
            ->where('k.durum', 1)
            ->groupBy('k.id', 'k.adi')
            ->orderBy('k.adi')
            ->select('k.id', 'k.adi', DB::raw('COUNT(y.id) as paket_sayisi'))
            ->get();

        return ['kategoriler' => $satir->map(fn ($k) => [
            'id' => $k->id, 'ad' => $k->adi, 'paket_sayisi' => (int) $k->paket_sayisi,
        ])->all()];
    }

    private static function musteriAra(array $g): array
    {
        $s = trim((string) ($g['sorgu'] ?? ''));
        if ($s === '') {
            return ['hata' => 'Arama kelimesi boş olamaz.'];
        }

        $q = DB::table('crm_customers')
            ->where(function ($w) use ($s) {
                $w->where('adi', 'like', "%{$s}%")
                  ->orWhere('unvan', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });

        if (!empty($g['sehir'])) {
            $q->where('il', 'like', '%' . $g['sehir'] . '%');
        }

        $toplam = (clone $q)->count();
        $satir  = $q->orderBy('adi')->limit(self::AZAMI_SATIR)
            ->get(['id', 'adi', 'unvan', 'email', 'telefon', 'il', 'sektor', 'durum']);

        return [
            'toplam_bulunan' => $toplam,
            'gosterilen'     => $satir->count(),
            'musteriler'     => $satir->map(fn ($m) => [
                'id'      => $m->id,
                'ad'      => $m->adi,
                'unvan'   => $m->unvan,
                'email'   => self::maskeEposta($m->email),
                'telefon' => self::maskeTelefon($m->telefon),
                'il'      => $m->il,
                'sektor'  => $m->sektor,
            ])->all(),
            'not' => 'Kişisel veriler KVKK gereği maskelenmiştir; tam bilgi panelde görülür.',
        ];
    }

    private static function satisOzet(array $g): array
    {
        $gun = max(1, min(365, (int) ($g['gun'] ?? 30)));
        $bas = date('Y-m-d 00:00:00', strtotime("-{$gun} days"));

        $q = DB::table('satilanlar')->where('tarih', '>=', $bas);

        return [
            'donem'         => "son {$gun} gün ({$bas} sonrası)",
            'satis_adedi'   => (int) (clone $q)->count(),
            'toplam_tutar'  => round((float) (clone $q)->sum('tutar'), 2),
            'onayli_adet'   => (int) (clone $q)->where('durum', 1)->count(),
            'para_birimi'   => 'TL',
        ];
    }

    private static function faturaOzet(array $g): array
    {
        $gun = max(1, min(365, (int) ($g['gun'] ?? 90)));
        $bas = date('Y-m-d 00:00:00', strtotime("-{$gun} days"));

        $satir = DB::table('faturalar')
            ->where('tarih', '>=', $bas)
            ->groupBy('durum')
            ->select('durum', DB::raw('COUNT(*) as adet'), DB::raw('SUM(tutar) as toplam'))
            ->get();

        return [
            'donem'    => "son {$gun} gün",
            'durumlar' => $satir->map(fn ($f) => [
                'durum_kodu' => $f->durum,
                'adet'       => (int) $f->adet,
                'toplam'     => round((float) $f->toplam, 2),
            ])->all(),
            'genel_toplam' => round((float) DB::table('faturalar')->where('tarih', '>=', $bas)->sum('tutar'), 2),
        ];
    }

    private static function referansListele(array $g): array
    {
        $q = DB::table('referanslar')->where('durum', 1);
        if (!empty($g['ara'])) {
            $q->where('adi', 'like', '%' . $g['ara'] . '%');
        }

        $toplam = (clone $q)->count();
        $satir  = $q->orderBy('sira')->limit(self::AZAMI_SATIR)->get(['id', 'adi', 'kisa']);

        return [
            'toplam_bulunan' => $toplam,
            'referanslar'    => $satir->map(fn ($r) => [
                'id' => $r->id, 'ad' => $r->adi,
                'ozet' => mb_substr(strip_tags((string) $r->kisa), 0, 140),
            ])->all(),
        ];
    }

    private static function sonIslemler(array $g): array
    {
        $adet = max(1, min(self::AZAMI_SATIR, (int) ($g['adet'] ?? 10)));

        $satir = DB::table('islem_gecmisi')->orderByDesc('id')->limit($adet)->get();

        return ['islemler' => $satir->map(fn ($i) => [
            'id'          => $i->id,
            'tarih'       => $i->tarih,
            'kullanici'   => $i->kullanici_adi,
            'islem'       => $i->aciklama,
            'kaynak'      => $i->kaynak,
            'geri_alindi' => (bool) $i->geri_alindi,
        ])->all()];
    }

    // ───────────────────────── KVKK maskeleme ─────────────────────────

    private static function maskeEposta(?string $e): string
    {
        if (!$e || !str_contains($e, '@')) {
            return '-';
        }
        [$ad, $alan] = explode('@', $e, 2);
        $gorunen = mb_substr($ad, 0, 2);

        return $gorunen . str_repeat('*', max(1, mb_strlen($ad) - 2)) . '@' . $alan;
    }

    private static function maskeTelefon(?string $t): string
    {
        $rakam = preg_replace('/\D/', '', (string) $t);
        if (strlen($rakam) < 7) {
            return '-';
        }

        return substr($rakam, 0, 3) . str_repeat('*', strlen($rakam) - 5) . substr($rakam, -2);
    }
}
