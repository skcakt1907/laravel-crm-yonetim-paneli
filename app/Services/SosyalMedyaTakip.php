<?php

namespace App\Services;

use App\Models\CRM\SosyalMedyaGunNotu;
use App\Models\CRM\SosyalMedyaHesap;
use App\Models\CRM\SosyalMedyaPaylasim;
use App\Models\CRM\SosyalMedyaPlan;
use App\Models\CRM\SosyalMedyaPlanIstisna;
use App\Models\CRM\SosyalMedyaPlanKalem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * SOSYAL MEDYA TAKİP — çekirdek mantık
 *
 * Hedef hesaplama ve gün durumu BİLEREK burada, controller'da değil:
 * aynı hesap hem uzman ekranında, hem gün sonu raporunda, hem aylık
 * raporda kullanılıyor. Üç yere ayrı ayrı yazılsaydı biri düzeltilip
 * diğeri unutulurdu.
 */
class SosyalMedyaTakip
{
    /** Geçmiş kaç güne kadar işaretleme yapılabilir */
    public const GERIYE_GUN = 3;

    /**
     * Bir hesabın belirli bir tarihteki paylaşım HEDEFİ.
     *
     * Öncelik sırası (üstteki alttakini EZER):
     *   1. Tarihe özel KALEMLER  -> hedef = kalem sayısı
     *      ("Reels: klinik tanıtım" + "Story: hasta yorumu" girildiyse hedef 2)
     *   2. Tarihe özel İSTİSNA   -> hedef = istisna adedi
     *   3. Haftalık ŞABLON       -> hedef = şablon adedi
     *
     * İstisna 0 da olabilir ("tatil, paylaşım yok") — bu yüzden "istisna var mı"
     * kontrolü null karşılaştırmasıyla yapılır, doğruluk (truthy) kontrolüyle
     * değil. Kalemlerde böyle bir sorun yok: kalem yoksa sayı zaten 0 olur ve
     * bir alt kurala düşeriz.
     */
    public function hedef(int $hesapId, Carbon $tarih): int
    {
        $kalemSayisi = SosyalMedyaPlanKalem::where('hesap_id', $hesapId)
            ->whereDate('tarih', $tarih->toDateString())
            ->count();

        if ($kalemSayisi > 0) {
            return $kalemSayisi;
        }

        $istisna = SosyalMedyaPlanIstisna::where('hesap_id', $hesapId)
            ->whereDate('tarih', $tarih->toDateString())
            ->value('hedef_adet');

        if ($istisna !== null) {
            return (int) $istisna;
        }

        $plan = SosyalMedyaPlan::where('hesap_id', $hesapId)
            ->where('gun', $tarih->dayOfWeekIso)
            ->where('aktif', true)
            ->value('hedef_adet');

        return (int) ($plan ?? 0);
    }

    /** O tarihe girilmiş isimli paylaşım kalemleri (sırasıyla) */
    public function kalemler(int $hesapId, Carbon $tarih): Collection
    {
        return SosyalMedyaPlanKalem::with('paylasim')
            ->where('hesap_id', $hesapId)
            ->whereDate('tarih', $tarih->toDateString())
            ->orderBy('sira')->orderBy('id')
            ->get();
    }

    /**
     * Bir hesabın o günkü tam durumu.
     *
     * @return array{hedef:int, yapilan:int, eksik:int, tamam:bool,
     *               paylasimlar:Collection, not:?SosyalMedyaGunNotu}
     */
    public function gunDurumu(int $hesapId, Carbon $tarih): array
    {
        $hedef = $this->hedef($hesapId, $tarih);

        $paylasimlar = SosyalMedyaPaylasim::where('hesap_id', $hesapId)
            ->whereDate('tarih', $tarih->toDateString())
            ->orderBy('isaretlendi_at')
            ->get();

        $not = SosyalMedyaGunNotu::where('hesap_id', $hesapId)
            ->whereDate('tarih', $tarih->toDateString())
            ->first();

        $yapilan = $paylasimlar->count();

        // Ertelenmiş veya iptal edilmiş gün EKSİK SAYILMAZ; raporda kendi
        // bölümünde görünür. Aksi hâlde meşru bir karar hata gibi okunurdu.
        $eksik = ($not || $hedef === 0) ? 0 : max(0, $hedef - $yapilan);

        return [
            'hedef'       => $hedef,
            'yapilan'     => $yapilan,
            'eksik'       => $eksik,
            'tamam'       => $eksik === 0,
            'paylasimlar' => $paylasimlar,
            'not'         => $not,
            // Kalem girilmişse ekran sayaç yerine CHECKLIST gösterir
            'kalemler'    => $this->kalemler($hesapId, $tarih),
        ];
    }

    /**
     * Belirli bir günde paylaşım planı OLAN hesaplar.
     *
     * Uzman ekranı ve gün sonu raporu bunu kullanır — hedefi 0 olan
     * (o gün planı olmayan) hesaplar listeye hiç girmez.
     *
     * @param  int|null  $sorumluId  verilirse yalnızca o uzmanın hesapları
     */
    public function gununHesaplari(Carbon $tarih, ?int $sorumluId = null): Collection
    {
        $sorgu = SosyalMedyaHesap::with('kayit');

        if ($sorumluId) {
            $sorgu->where('sorumlu_id', $sorumluId);
        }

        return $sorgu->get()->filter(
            fn ($hesap) => $this->hedef($hesap->id, $tarih) > 0
        )->values();
    }

    /**
     * O güne ERTELENMİŞ olan kayıtlar.
     *
     * Ertelenen paylaşım hedef sayıya EKLENMEZ (plan mantığı bozulmasın
     * diye); raporda ayrı satır olarak gösterilir.
     */
    public function bugueErtelenenler(Carbon $tarih): Collection
    {
        return SosyalMedyaGunNotu::with('hesap.kayit')
            ->where('tip', SosyalMedyaGunNotu::TIP_ERTELENDI)
            ->whereDate('ertelendi_tarih', $tarih->toDateString())
            ->get();
    }

    /** Geçmişe işaretleme sınırı içinde mi */
    public function isaretlenebilirMi(Carbon $tarih): bool
    {
        $bugun = Carbon::today();

        if ($tarih->gt($bugun)) {
            return false;                       // gelecek işaretlenemez
        }

        return $tarih->diffInDays($bugun) <= self::GERIYE_GUN;
    }

    /**
     * Gün sonu raporunun ham verisi.
     *
     * @return array{tamamlanan:array, eksik:array, ertelenen:array,
     *               iptal:array, bugune_ertelenen:array}
     */
    public function gunSonuVerisi(Carbon $tarih): array
    {
        $sonuc = ['tamamlanan' => [], 'eksik' => [], 'ertelenen' => [],
                  'iptal' => [], 'bugune_ertelenen' => []];

        foreach ($this->gununHesaplari($tarih) as $hesap) {
            $d = $this->gunDurumu($hesap->id, $tarih);

            $satir = [
                'marka'    => $hesap->kayit->baslik ?? $hesap->kayit->firma ?? '—',
                'platform' => $hesap->platform,
                'hedef'    => $d['hedef'],
                'yapilan'  => $d['yapilan'],
                'sorumlu'  => optional($hesap->sorumlu)->adi ?? 'Atanmamış',
                'saatler'  => $d['paylasimlar']
                    ->pluck('isaretlendi_at')
                    ->filter()
                    ->map(fn ($t) => $t->format('H:i'))
                    ->all(),
                'gec'      => $d['paylasimlar']->contains(fn ($p) => $p->gecIsaretlendi()),

                // Kalem girilmişse eksiklerin ADI da yazılır ("Reels: klinik
                // tanıtım yapılmadı"). Yalnızca sayı vermek raporu okuyanın
                // neyin eksik olduğunu tahmin etmesini gerektiriyordu.
                'eksik_kalemler' => $d['kalemler']
                    ->filter(fn ($k) => $k->paylasim === null)
                    ->pluck('baslik')->values()->all(),
                'yapilan_kalemler' => $d['kalemler']
                    ->filter(fn ($k) => $k->paylasim !== null)
                    ->pluck('baslik')->values()->all(),
            ];

            if ($d['not']) {
                $satir['sebep'] = $d['not']->sebep;

                if ($d['not']->tip === SosyalMedyaGunNotu::TIP_ERTELENDI) {
                    $satir['yeni_tarih'] = optional($d['not']->ertelendi_tarih)->format('d.m.Y');
                    $sonuc['ertelenen'][] = $satir;
                } else {
                    $sonuc['iptal'][] = $satir;
                }
                continue;
            }

            $d['tamam'] ? $sonuc['tamamlanan'][] = $satir
                        : $sonuc['eksik'][]      = $satir;
        }

        foreach ($this->bugueErtelenenler($tarih) as $not) {
            $sonuc['bugune_ertelenen'][] = [
                'marka'      => $not->hesap->kayit->baslik ?? '—',
                'platform'   => $not->hesap->platform ?? '—',
                'eski_tarih' => optional($not->tarih)->format('d.m.Y'),
                'sebep'      => $not->sebep,
            ];
        }

        return $sonuc;
    }

    /**
     * AYLIK RAPOR — bir ayın tamamı, hesap bazında.
     *
     * NEDEN AYRI BİR METOT: gunDurumu() bir gün için 3 sorgu atıyor. Ay
     * raporunda 40 hesap × 30 gün = 3600 sorgu ederdi. Burada ayın TÜM
     * planı, istisnası, paylaşımı ve notu dört sorguyla çekilip hesap
     * bellekte yapılıyor.
     *
     * @return array{ay:Carbon, satirlar:array, toplam:array}
     */
    public function aylikVeri(Carbon $ay, ?int $sorumluId = null): array
    {
        $bas = $ay->copy()->startOfMonth();
        $son = $ay->copy()->endOfMonth();

        // Gelecek günler rapora girmez: henüz yapılmamış paylaşım "eksik"
        // sayılırsa ayın ortasında rapor hep kırmızı görünür.
        $bitis = $son->gt(Carbon::today()) ? Carbon::today() : $son;

        $hesapSorgu = SosyalMedyaHesap::with(['kayit', 'sorumlu'])
            ->where('bolum', SosyalMedyaHesap::BOLUM_SOSYAL);

        if ($sorumluId) {
            $hesapSorgu->where('sorumlu_id', $sorumluId);
        }

        $hesaplar = $hesapSorgu->get();
        $idler    = $hesaplar->pluck('id');

        // hesap_id => [gun => hedef]
        $planlar = SosyalMedyaPlan::whereIn('hesap_id', $idler)->where('aktif', true)
            ->get()->groupBy('hesap_id')
            ->map(fn ($g) => $g->pluck('hedef_adet', 'gun')->all());

        // hesap_id => [Y-m-d => hedef]
        $istisnalar = SosyalMedyaPlanIstisna::whereIn('hesap_id', $idler)
            ->whereBetween('tarih', [$bas->toDateString(), $son->toDateString()])
            ->get()->groupBy('hesap_id')
            ->map(fn ($g) => $g->mapWithKeys(fn ($i) => [
                Carbon::parse($i->tarih)->toDateString() => (int) $i->hedef_adet,
            ])->all());

        // hesap_id => [Y-m-d => adet]
        $paylasimlar = SosyalMedyaPaylasim::whereIn('hesap_id', $idler)
            ->whereBetween('tarih', [$bas->toDateString(), $son->toDateString()])
            ->get()->groupBy('hesap_id')
            ->map(fn ($g) => $g->groupBy(fn ($p) => Carbon::parse($p->tarih)->toDateString())
                                ->map->count()->all());

        // hesap_id => [Y-m-d => tip]
        $notlar = SosyalMedyaGunNotu::whereIn('hesap_id', $idler)
            ->whereBetween('tarih', [$bas->toDateString(), $son->toDateString()])
            ->get()->groupBy('hesap_id')
            ->map(fn ($g) => $g->mapWithKeys(fn ($n) => [
                Carbon::parse($n->tarih)->toDateString() => $n->tip,
            ])->all());

        $satirlar = [];

        foreach ($hesaplar as $hesap) {
            $plan    = $planlar[$hesap->id]     ?? [];
            $istisna = $istisnalar[$hesap->id]  ?? [];
            $yapilan = $paylasimlar[$hesap->id] ?? [];
            $not     = $notlar[$hesap->id]      ?? [];

            $satir = ['hedef' => 0, 'yapilan' => 0, 'eksik_gun' => 0, 'tam_gun' => 0,
                      'ertelenen' => 0, 'iptal' => 0, 'gunler' => []];

            for ($t = $bas->copy(); $t->lte($bitis); $t->addDay()) {
                $ymd = $t->toDateString();

                // İstisna şablonu ezer; 0 da geçerli bir hedef (tatil)
                $hedef = array_key_exists($ymd, $istisna)
                    ? $istisna[$ymd]
                    : (int) ($plan[$t->dayOfWeekIso] ?? 0);

                $adet = $yapilan[$ymd] ?? 0;
                $tip  = $not[$ymd] ?? null;

                if ($hedef === 0 && $adet === 0 && $tip === null) {
                    $satir['gunler'][$ymd] = 'plansiz';
                    continue;
                }

                $satir['hedef']   += $hedef;
                $satir['yapilan'] += $adet;

                if ($tip === SosyalMedyaGunNotu::TIP_ERTELENDI) {
                    $satir['ertelenen']++;
                    $satir['gunler'][$ymd] = 'ertelendi';
                } elseif ($tip === SosyalMedyaGunNotu::TIP_IPTAL) {
                    $satir['iptal']++;
                    $satir['gunler'][$ymd] = 'iptal';
                } elseif ($adet >= $hedef) {
                    $satir['tam_gun']++;
                    $satir['gunler'][$ymd] = 'tamam';
                } else {
                    $satir['eksik_gun']++;
                    $satir['gunler'][$ymd] = 'eksik';
                }
            }

            // Hiç planı olmayan hesap raporu şişirir — listeye alma
            if ($satir['hedef'] === 0 && $satir['yapilan'] === 0
                && $satir['ertelenen'] === 0 && $satir['iptal'] === 0) {
                continue;
            }

            $satir['hesap']   = $hesap;
            $satir['marka']   = $hesap->kayit->baslik ?? $hesap->kayit->firma ?? '—';
            $satir['sorumlu'] = optional($hesap->sorumlu)->adi ?? 'Atanmamış';
            $satir['oran']    = $satir['hedef'] > 0
                ? (int) round($satir['yapilan'] / $satir['hedef'] * 100)
                : 100;

            $satirlar[] = $satir;
        }

        // En sorunlu üstte: önce oranı düşük olan
        usort($satirlar, fn ($a, $b) => [$a['oran'], $a['marka']] <=> [$b['oran'], $b['marka']]);

        $toplam = [
            'hedef'     => array_sum(array_column($satirlar, 'hedef')),
            'yapilan'   => array_sum(array_column($satirlar, 'yapilan')),
            'eksik_gun' => array_sum(array_column($satirlar, 'eksik_gun')),
            'ertelenen' => array_sum(array_column($satirlar, 'ertelenen')),
            'iptal'     => array_sum(array_column($satirlar, 'iptal')),
        ];
        $toplam['oran'] = $toplam['hedef'] > 0
            ? (int) round($toplam['yapilan'] / $toplam['hedef'] * 100) : 100;

        return ['ay' => $bas, 'bitis' => $bitis, 'satirlar' => $satirlar, 'toplam' => $toplam];
    }
}
