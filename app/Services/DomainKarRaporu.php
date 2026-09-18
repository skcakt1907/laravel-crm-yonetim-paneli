<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DOMAIN / HOSTING KÂR RAPORU (5. öncelik).
 *
 * Kâr = satış tutarı − maliyet.
 *
 * MALİYET İKİ KAYNAKTAN GELİR (öncelik sırasıyla):
 *   1) satilanlar.maliyet            → o kayda özel alış fiyatı
 *   2) domain_fiyatlar.maliyet_fiyat → uzantı bazlı varsayılan (.com, .com.tr ...)
 *
 * Maliyeti bilinmeyen kayıtlar kâra DÂHİL EDİLMEZ, ayrıca raporlanır —
 * böylece "maliyet girilmedi" durumu kârı olduğundan yüksek göstermez.
 *
 * NOT: Tarih olarak `satilanlar.tarih` (varchar, ISO biçimli) kullanılıyor;
 * tablodaki 886 kaydın tamamında dolu. `baslangic_tarihi`/`bitis_tarihi` DATE
 * kolonları neredeyse boş (16 kayıt), onlara güvenilmiyor.
 */
class DomainKarRaporu
{
    /** Uzantı listesi + varsayılan maliyetleri (uzun uzantı önce: .com.tr, .com) */
    public static function uzantilar(): array
    {
        if (!Schema::hasTable('domain_fiyatlar')) return [];

        $kolonVar = Schema::hasColumn('domain_fiyatlar', 'maliyet_fiyat');

        $liste = DB::table('domain_fiyatlar')
            ->get(['uzanti', 'yenileme_fiyat', $kolonVar ? 'maliyet_fiyat' : DB::raw('NULL as maliyet_fiyat')])
            ->mapWithKeys(fn ($u) => [
                strtolower(ltrim(trim($u->uzanti), '.')) => [
                    'maliyet'  => $u->maliyet_fiyat !== null ? (float) $u->maliyet_fiyat : null,
                    'yenileme' => (float) ($u->yenileme_fiyat ?? 0),
                ],
            ])
            ->toArray();

        // En uzun uzantı önce eşleşsin: "x.com.tr" → com.tr (com değil)
        uksort($liste, fn ($a, $b) => strlen($b) <=> strlen($a));

        return $liste;
    }

    /** Domain adından uzantıyı bulur ('abc.com.tr' → 'com.tr'), bulunamazsa null */
    public static function uzantiBul(?string $domain, array $uzantilar): ?string
    {
        $d = strtolower(trim((string) $domain));
        if ($d === '') return null;

        foreach (array_keys($uzantilar) as $u) {
            if (str_ends_with($d, '.' . $u)) return $u;
        }

        return null;
    }

    /** Sayıya çevrilebilir mi (tutar varchar tutuluyor) */
    private static function sayi(?string $deger): ?float
    {
        $t = trim((string) $deger);
        if ($t === '' || !preg_match('/^\d+(?:[.,]\d+)?$/', $t)) return null;

        return (float) str_replace(',', '.', $t);
    }

    /**
     * Kâr verisi.
     *
     * @param  int|null  $yil  Boşsa içinde bulunulan yıl
     * @return array{
     *   yil:int, yillar:array, ciro:float, maliyet:float, kar:float, marj:float,
     *   adet:int, maliyetsiz_adet:int, maliyetsiz_ciro:float, tutarsiz_adet:int,
     *   aylar:array, uzantilar:array, eksik_uzantilar:array
     * }
     */
    public static function veri(?int $yil = null): array
    {
        $yil = $yil ?: (int) now()->year;
        $uzantilar = self::uzantilar();

        $bos = [
            'yil' => $yil, 'yillar' => [], 'ciro' => 0.0, 'maliyet' => 0.0, 'kar' => 0.0,
            'marj' => 0.0, 'adet' => 0, 'maliyetsiz_adet' => 0, 'maliyetsiz_ciro' => 0.0,
            'tutarsiz_adet' => 0, 'tahmini_adet' => 0, 'tahmini_ciro' => 0.0,
            'aylar' => [], 'uzantilar' => [], 'eksik_uzantilar' => [],
        ];

        if (!Schema::hasTable('satilanlar')) return $bos;

        $maliyetKolonu = Schema::hasColumn('satilanlar', 'maliyet');

        // Veri setindeki yıllar (filtre kutusu için)
        $yillar = DB::table('satilanlar')
            ->whereNotNull('domain')->where('domain', '<>', '')
            ->selectRaw('DISTINCT LEFT(tarih, 4) as y')
            ->orderByDesc('y')->pluck('y')
            ->filter(fn ($y) => preg_match('/^\d{4}$/', (string) $y))
            ->map(fn ($y) => (int) $y)->values()->all();

        $kolonlar = ['id', 'domain', 'tipi', 'tutar', 'tarih'];
        if ($maliyetKolonu) $kolonlar[] = 'maliyet';

        $kayitlar = DB::table('satilanlar')
            ->whereNotNull('domain')->where('domain', '<>', '')
            ->where('tarih', 'like', $yil . '-%')
            ->get($kolonlar);

        /*
         * METUNIC'E ÖZEL KAYITLAR (16.09.2026)
         *
         * Domain listesi iki tabloya ayrıldı. Bizde karşılığı olmayan
         * Metunic domainleri bu rapora hiç giremiyordu; artık panelden
         * tutar girilebiliyor ve girilenler ciroya katılıyor.
         *
         * TARİH: `satis_tarihi` kullanılır, `date_added` DEĞİL — ikincisi
         * domainin ilk tescil tarihi (veride 2020-2026'ya yayılıyor) ve
         * ciroyu yanlış yıllara dağıtır.
         *
         * ÇİFT SAYMA KORUMASI: `satilanlar`da aynı domain varsa Metunic
         * kaydı sayılmaz; o domain zaten yukarıdaki sorguda.
         */
        $kayitlar = $kayitlar->concat(self::metunicKayitlari($yil));

        $aylar = [];
        for ($a = 1; $a <= 12; $a++) {
            $aylar[$a] = ['ay' => $a, 'etiket' => Carbon::create($yil, $a, 1)->translatedFormat('F'),
                          'ciro' => 0.0, 'maliyet' => 0.0, 'kar' => 0.0, 'adet' => 0];
        }

        $uzantiOzet     = [];
        $eksikUzantilar = [];
        $ciro = $maliyetTop = 0.0;
        $adet = $maliyetsiz = $tutarsiz = 0;
        $maliyetsizCiro = 0.0;

        $tahminiAdet = 0;
        $tahminiCiro = 0.0;

        foreach ($kayitlar as $k) {
            $uzanti = self::uzantiBul($k->domain, $uzantilar);

            /*
             * SATIŞ TUTARI: kayıttaki `tutar`. Ancak veride bu alan çoğu kayıtta 0 —
             * bu yüzden 0/boş olanlarda uzantının LİSTE (yenileme) fiyatı TAHMİNİ
             * satış olarak kullanılır ve ayrıca sayılır. Rapor ikisini ayrı gösterir,
             * böylece "gerçek" ile "tahmini" karışmaz.
             */
            $satis    = self::sayi($k->tutar);
            $tahminMi = false;

            if ($satis === null || $satis <= 0) {
                $listeFiyat = $uzanti !== null ? ($uzantilar[$uzanti]['yenileme'] ?? 0) : 0;
                if ($listeFiyat > 0) {
                    $satis = $listeFiyat;
                    $tahminMi = true;
                } else {
                    $tutarsiz++;
                    continue;   // ne tutarı ne liste fiyatı var — hesaba katılamaz
                }
            }

            if ($tahminMi) { $tahminiAdet++; $tahminiCiro += $satis; }

            // Maliyet: önce kayda özel, yoksa uzantı varsayılanı
            $maliyet = $maliyetKolonu && $k->maliyet !== null ? (float) $k->maliyet : null;
            if ($maliyet === null && $uzanti !== null) {
                $maliyet = $uzantilar[$uzanti]['maliyet'];
            }

            $ay = (int) substr((string) $k->tarih, 5, 2);
            if ($ay < 1 || $ay > 12) $ay = 1;

            $adet++;
            $ciro += $satis;
            $aylar[$ay]['ciro'] += $satis;
            $aylar[$ay]['adet']++;

            $anahtar = $uzanti ?? 'bilinmiyor';
            $uzantiOzet[$anahtar] ??= ['uzanti' => $anahtar, 'adet' => 0, 'ciro' => 0.0,
                                        'maliyet' => 0.0, 'kar' => 0.0, 'maliyetsiz' => 0];
            $uzantiOzet[$anahtar]['adet']++;
            $uzantiOzet[$anahtar]['ciro'] += $satis;

            if ($maliyet === null) {
                $maliyetsiz++;
                $maliyetsizCiro += $satis;
                $uzantiOzet[$anahtar]['maliyetsiz']++;
                if ($uzanti !== null) $eksikUzantilar[$uzanti] = true;
                continue;   // maliyeti bilinmeyeni kâra katma
            }

            $maliyetTop += $maliyet;
            $aylar[$ay]['maliyet'] += $maliyet;
            $aylar[$ay]['kar']     += $satis - $maliyet;
            $uzantiOzet[$anahtar]['maliyet'] += $maliyet;
            $uzantiOzet[$anahtar]['kar']     += $satis - $maliyet;
        }

        // Kâr yalnızca maliyeti BİLİNEN kayıtlar üzerinden
        $kar  = ($ciro - $maliyetsizCiro) - $maliyetTop;
        $baz  = $ciro - $maliyetsizCiro;
        $marj = $baz > 0 ? ($kar / $baz) * 100 : 0.0;

        uasort($uzantiOzet, fn ($a, $b) => $b['ciro'] <=> $a['ciro']);

        return [
            'yil'             => $yil,
            'yillar'          => $yillar,
            'ciro'            => $ciro,
            'maliyet'         => $maliyetTop,
            'kar'             => $kar,
            'marj'            => $marj,
            'adet'            => $adet,
            'maliyetsiz_adet' => $maliyetsiz,
            'maliyetsiz_ciro' => $maliyetsizCiro,
            'tutarsiz_adet'   => $tutarsiz,
            'tahmini_adet'    => $tahminiAdet,
            'tahmini_ciro'    => $tahminiCiro,
            'aylar'           => array_values($aylar),
            'uzantilar'       => array_values($uzantiOzet),
            'eksik_uzantilar' => array_keys($eksikUzantilar),
        ];
    }

    /**
     * Metunic tarafında elle tutar girilmiş domainleri rapor biçimine çevirir.
     *
     * Tutarı girilmemiş kayıtlar DIŞARIDA bırakılır: tahmini rakamla
     * ciroyu şişirmek yerine "bilinmiyor" kalması tercih edildi.
     *
     * @return \Illuminate\Support\Collection
     */
    private static function metunicKayitlari(int $yil)
    {
        if (! Schema::hasTable('metunic_domains')
            || ! Schema::hasColumn('metunic_domains', 'tutar')
            || ! Schema::hasColumn('metunic_domains', 'satis_tarihi')) {
            return collect();
        }

        $maliyetVar = Schema::hasColumn('metunic_domains', 'maliyet');

        $bizdekiler = Schema::hasTable('satilanlar')
            ? DB::table('satilanlar')
                ->whereNotNull('domain')->where('domain', '<>', '')
                ->pluck('domain')
                ->map(fn ($d) => \App\Support\DomainAnahtari::of($d))
                ->flip()
            : collect();

        return DB::table('metunic_domains')
            ->whereNotNull('domain')->where('domain', '<>', '')
            ->where('tutar', '>', 0)
            ->whereNotNull('satis_tarihi')
            ->whereYear('satis_tarihi', $yil)
            ->get($maliyetVar
                ? ['id', 'domain', 'tutar', 'maliyet', 'satis_tarihi']
                : ['id', 'domain', 'tutar', 'satis_tarihi'])
            ->reject(fn ($m) => $bizdekiler->has(\App\Support\DomainAnahtari::of($m->domain)))
            ->map(fn ($m) => (object) [
                'id'      => $m->id,
                'domain'  => $m->domain,
                'tipi'    => '3',
                'tutar'   => $m->tutar,
                'maliyet' => $maliyetVar ? ($m->maliyet ?? null) : null,
                'tarih'   => substr((string) $m->satis_tarihi, 0, 10),
            ])
            ->values();
    }
}
