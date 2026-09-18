<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Admin panelindeki route'lari tarar, kategori + okunabilir ad ile listeler.
 * Yetki atama ekraninda kullanilir.
 */
class AdminSayfaKatalogu
{
    /**
     * @return array<string, array<int, array{route:string, adi:string, kategori:string}>>
     *         kategori => sayfalar
     */
    public static function kategorilereGore(): array
    {
        $liste = self::liste();
        $grup = [];
        foreach ($liste as $s) {
            $grup[$s['kategori']][] = $s;
        }
        ksort($grup);
        return $grup;
    }

    /**
     * @return array<int, array{route:string, adi:string, kategori:string}>
     */
    public static function liste(): array
    {
        return Cache::remember('admin_sayfa_katalogu_v3', 600, function () {
            $sayfalar = [];
            $gorulen = [];

            foreach (Route::getRoutes() as $route) {
                $name = $route->getName();
                if (!$name || !Str::startsWith($name, 'admin.')) {
                    continue;
                }

                // 'admin.' (bos ad) veya yonlendirme route'larini haric tut
                if ($name === 'admin.' || Str::endsWith($name, '.home.redirect') || Str::contains($name, ['.index-redirect', '.redirect'])) {
                    continue;
                }

                // Giris/logout/auth route'larini haric tut
                if (Str::contains($name, ['giris', 'login', 'logout', 'cikis'])) {
                    continue;
                }

                // Sadece GET route'lari izin kontrolunde kullanilir (listeleme/form sayfalari)
                $methods = $route->methods();
                if (!in_array('GET', $methods, true)) {
                    continue;
                }

                // Ayni base name'in ekle/duzenle/sil varyantlarini tek satira indirme:
                // Her name'i ayri gosteriyoruz; kullanici istedigi granulariteyi secer.
                if (isset($gorulen[$name])) {
                    continue;
                }
                $gorulen[$name] = true;

                $sayfalar[] = [
                    'route'    => $name,
                    'adi'      => self::okunabilirAd($name),
                    'kategori' => self::kategoriCikar($name),
                ];
            }

            usort($sayfalar, function ($a, $b) {
                return [$a['kategori'], $a['adi']] <=> [$b['kategori'], $b['adi']];
            });

            return $sayfalar;
        });
    }

    public static function cacheTemizle(): void
    {
        Cache::forget('admin_sayfa_katalogu_v3');
    }

    private static function kategoriCikar(string $name): string
    {
        // admin.crm.musteriler.index -> CRM
        // admin.bayi.dashboard       -> Bayi
        // admin.faturalar.index      -> Faturalar
        $parcalar = explode('.', $name);
        array_shift($parcalar); // "admin"
        if (empty($parcalar)) {
            return 'Genel';
        }
        $kok = $parcalar[0];

        $harita = [
            'dashboard'        => 'Genel',
            'crm'              => 'CRM',
            'bayi'             => 'Bayi Paneli',
            'bayiler'          => 'Bayi Yonetimi',
            'bayi-ayar'        => 'Bayi Yonetimi',
            'bayi-pazarlama'   => 'Bayi Yonetimi',
            'bayi-rapor'       => 'Bayi Yonetimi',
            'bayi-destek'      => 'Bayi Yonetimi',
            'bayi-odeme'       => 'Bayi Yonetimi',
            'bayi-referans'    => 'Bayi Yonetimi',
            'bayi-bildirim'    => 'Bayi Yonetimi',
            'bayilik-satis'    => 'Bayi Yonetimi',
            'faturalar'        => 'Finans',
            'banka'            => 'Finans',
            'odeme-bildirim'   => 'Finans',
            'kuponlar'         => 'Finans',
            'satis'            => 'Finans',
            'tickets'          => 'Destek',
            'destek'           => 'Destek',
            'blog'             => 'Icerik',
            'sayfalar'         => 'Icerik',
            'slider'           => 'Icerik',
            'menu'             => 'Icerik',
            'referanslar'      => 'Icerik',
            'kampanyalar'      => 'Icerik',
            'yorumlar'         => 'Icerik',
            'ebulten'          => 'Icerik',
            'hizmetler'        => 'Urunler',
            'hosting'          => 'Urunler',
            'domain'           => 'Urunler',
            'paketler'         => 'Urunler',
            'package'          => 'Urunler',
            'paket-teklif'     => 'Urunler',
            'promo-kod-onay'   => 'Urunler',
            'urunler'          => 'Urunler',
            'yazilimlar'       => 'Urunler',
            'kategoriler'      => 'Urunler',
            'uyeler'           => 'Musteri Yonetimi',
            'yoneticiler'      => 'Ayarlar',
            'roller'           => 'Ayarlar',
            'ayarlar'          => 'Ayarlar',
            'dil'              => 'Ayarlar',
            'profil'           => 'Ayarlar',
            'mesajlar'         => 'Iletisim',
            'iletisim'         => 'Iletisim',
            'bildirim'         => 'Iletisim',
            'communication'    => 'Iletisim',
            'not-defteri'      => 'Arac',
            'import-export'    => 'Arac',
        ];

        return $harita[$kok] ?? ucfirst(str_replace('-', ' ', $kok));
    }

    private static function okunabilirAd(string $name): string
    {
        // admin.crm.musteriler.index -> CRM Musteriler (Liste)
        $parcalar = explode('.', $name);
        array_shift($parcalar); // "admin"

        $son = end($parcalar);
        $eylem = '';
        if (in_array($son, ['index', 'ekle', 'eklePost', 'duzenle', 'duzenlePost', 'sil', 'store', 'update', 'detay', 'goster', 'liste'], true)) {
            $eylemHarita = [
                'index'       => 'Liste',
                'ekle'        => 'Ekle',
                'eklePost'    => 'Ekle (Kayit)',
                'duzenle'     => 'Duzenle',
                'duzenlePost' => 'Duzenle (Kayit)',
                'sil'         => 'Sil',
                'store'       => 'Kayit',
                'update'      => 'Guncelle',
                'detay'       => 'Detay',
                'goster'      => 'Goster',
                'liste'       => 'Liste',
            ];
            $eylem = ' (' . ($eylemHarita[$son] ?? ucfirst($son)) . ')';
            array_pop($parcalar);
        }

        $ad = implode(' / ', array_map(function ($p) {
            return ucwords(str_replace(['-', '_'], ' ', $p));
        }, $parcalar));

        return ($ad ?: 'Dashboard') . $eylem;
    }
}