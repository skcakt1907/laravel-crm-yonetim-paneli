<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Madde 6: Ayın İş Ortakları.
 * "Kim bize ne kadar kazandırdı" — faturalardan müşteri başına ciro raporu.
 * Tahsil edilen (durum=1) + Bekleyen (durum=0) ayrı. İptal (2) hariç.
 * Dönem: aylık / 3 aylık / yıllık. Liste + grafik.
 */
class IsOrtaklariController extends Controller
{
    public function index(Request $request)
    {
        // Tablo yoksa boş ekran
        if (!Schema::hasTable('faturalar')) {
            return view('admin.is-ortaklari.index', [
                'donem' => 'ay',
                'liste' => collect(),
                'ozet' => $this->bosOzet(),
                'grafik' => [],
                'aralikMetni' => '',
                'tabloYok' => true,
            ]);
        }

        // Dönem seçimi: ay | uc_ay | yil
        $donem = $request->get('donem', 'ay');
        if (!in_array($donem, ['ay', 'uc_ay', 'yil'])) {
            $donem = 'ay';
        }

        [$bas, $bit, $aralikMetni] = $this->donemAraligi($donem);

        // Müşteri (uyeid) başına ciro topla. Tarih kolonu üzerinden filtre.
        // tutar varchar ama numerik; SUM otomatik cast eder.
        $base = DB::table('faturalar as f')
            ->join('uyeler as u', 'f.uyeid', '=', 'u.id')
            ->whereTarihBetween('f.tarih', $bas, $bit)
            ->where('f.durum', '!=', 2); // iptal hariç

        $liste = (clone $base)
            ->select(
                'u.id as uye_id',
                'u.ad', 'u.soyad', 'u.firmaadi', 'u.email',
                DB::raw('COUNT(f.id) as fatura_adet'),
                DB::raw('SUM(CASE WHEN f.durum = 1 THEN f.tutar ELSE 0 END) as tahsil_edilen'),
                DB::raw('SUM(CASE WHEN f.durum = 0 THEN f.tutar ELSE 0 END) as bekleyen'),
                DB::raw('SUM(f.tutar) as toplam')
            )
            ->groupBy('u.id', 'u.ad', 'u.soyad', 'u.firmaadi', 'u.email')
            ->orderByDesc('toplam')
            ->get()
            ->map(function ($r) {
                // Görünen ad: firma varsa firma, yoksa ad soyad
                $ad = trim((string) ($r->firmaadi ?? ''));
                if ($ad === '') {
                    $ad = trim(($r->ad ?? '') . ' ' . ($r->soyad ?? ''));
                }
                if ($ad === '') {
                    $ad = $r->email ?: ('#' . $r->uye_id);
                }
                $r->gorunen_ad = $ad;
                $r->tahsil_edilen = (float) $r->tahsil_edilen;
                $r->bekleyen = (float) $r->bekleyen;
                $r->toplam = (float) $r->toplam;
                return $r;
            });

        // Özet
        $ozet = [
            'toplam_ciro' => (float) $liste->sum('toplam'),
            'tahsil_edilen' => (float) $liste->sum('tahsil_edilen'),
            'bekleyen' => (float) $liste->sum('bekleyen'),
            'musteri_sayisi' => $liste->count(),
        ];

        // Grafik için ilk 8 müşteri (toplam ciroya göre)
        $grafik = $liste->take(8)->map(function ($r) {
            return [
                'ad' => mb_strlen($r->gorunen_ad) > 22 ? mb_substr($r->gorunen_ad, 0, 22) . '…' : $r->gorunen_ad,
                'tahsil' => round($r->tahsil_edilen, 2),
                'bekleyen' => round($r->bekleyen, 2),
                'toplam' => round($r->toplam, 2),
            ];
        })->values()->all();

        return view('admin.is-ortaklari.index', [
            'donem' => $donem,
            'liste' => $liste,
            'ozet' => $ozet,
            'grafik' => $grafik,
            'aralikMetni' => $aralikMetni,
        ]);
    }

    /**
     * Seçilen döneme göre [başlangıç, bitiş, görünen metin].
     */
    private function donemAraligi(string $donem): array
    {
        $bugun = Carbon::today();
        switch ($donem) {
            case 'uc_ay':
                $bas = $bugun->copy()->subMonths(3)->startOfDay();
                $bit = $bugun->copy()->endOfDay();
                return [$bas->toDateTimeString(), $bit->toDateTimeString(), 'Son 3 Ay (' . $bas->format('d.m.Y') . ' — ' . $bit->format('d.m.Y') . ')'];
            case 'yil':
                $bas = $bugun->copy()->startOfYear();
                $bit = $bugun->copy()->endOfYear();
                return [$bas->toDateTimeString(), $bit->toDateTimeString(), $bugun->format('Y') . ' Yılı'];
            case 'ay':
            default:
                $bas = $bugun->copy()->startOfMonth();
                $bit = $bugun->copy()->endOfMonth();
                return [$bas->toDateTimeString(), $bit->toDateTimeString(), $bugun->translatedFormat('F Y')];
        }
    }

    private function bosOzet(): array
    {
        return ['toplam_ciro' => 0, 'tahsil_edilen' => 0, 'bekleyen' => 0, 'musteri_sayisi' => 0];
    }
}