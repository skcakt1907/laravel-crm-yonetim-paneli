<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Madde 9: Gelir-Gider raporu.
 * Gelir  = faturalar (tahsil edilen durum=1 + bekleyen durum=0, iptal=2 hariç)
 * Gider  = giderler tablosu (ödenen + bekleyen)
 * Net    = tahsil edilen gelir - ödenen gider
 * Dönem: aylık / 3 aylık / yıllık. Aylık kırılım grafiği + özet.
 */
class GelirGiderController extends Controller
{
    public function index(Request $request)
    {
        $donem = $request->get('donem', 'ay');
        if (!in_array($donem, ['ay', 'uc_ay', 'yil'])) {
            $donem = 'ay';
        }

        [$bas, $bit, $aralikMetni] = $this->donemAraligi($donem);

        $gelir = $this->gelirHesapla($bas, $bit);
        $gider = $this->giderHesapla($bas, $bit);

        // Net durum (tahsil edilen gelir - ödenen gider)
        $netNakit = $gelir['tahsil'] - $gider['odenen'];
        // Tahakkuk (tüm gelir - tüm gider)
        $netTahakkuk = $gelir['toplam'] - $gider['toplam'];

        // Aylık kırılım (grafik için) - dönem içindeki her ay
        $aylikKirilim = $this->aylikKirilim($bas, $bit);

        // Detay listeleri (açılır-kapanır dropdown'lar)
        $onaylananFaturalar = $this->onaylananFaturalar($bas, $bit);
        $bekleyenFaturalar   = $this->bekleyenFaturalar($bas, $bit);
        $harcamalar         = $this->harcamaListesi($bas, $bit);
        $hareketler         = $this->birlesikHareketler($onaylananFaturalar, $harcamalar);

        // Standart aylık kalemler (manuel) — BAĞIMSIZ: yukarıdaki hesaplara/toplamlara
        // dahil DEĞİLDİR, sadece kayıt + görüntüleme amaçlıdır.
        $ggStdVar = Schema::hasTable('gg_standart_kalemler');
        $standartGelir = $ggStdVar
            ? DB::table('gg_standart_kalemler')->where('tip', 'gelir')->orderByDesc('id')->get()
            : collect();
        $standartGider = $ggStdVar
            ? DB::table('gg_standart_kalemler')->where('tip', 'gider')->orderByDesc('id')->get()
            : collect();

        return view('admin.gelir-gider.index', [
            'donem' => $donem,
            'aralikMetni' => $aralikMetni,
            'gelir' => $gelir,
            'gider' => $gider,
            'netNakit' => $netNakit,
            'netTahakkuk' => $netTahakkuk,
            'aylikKirilim' => $aylikKirilim,
            'onaylananFaturalar' => $onaylananFaturalar,
            'bekleyenFaturalar' => $bekleyenFaturalar,
            'harcamalar' => $harcamalar,
            'hareketler' => $hareketler,
            'faturaVar' => Schema::hasTable('faturalar'),
            'giderVar' => Schema::hasTable('giderler'),
            'standartGelir' => $standartGelir,
            'standartGider' => $standartGider,
            'ggStdVar' => $ggStdVar,
        ]);
    }

    /**
     * Standart aylık kalem EKLE (manuel gelir/gider).
     * Bağımsızdır — mevcut fatura/gider hesaplarını ve net toplamları ETKİLEMEZ.
     */
    public function standartEkle(Request $request)
    {
        $data = $request->validate([
            'tip'      => 'required|in:gelir,gider',
            'baslik'   => 'required|string|max:190',
            'tutar'    => 'required|numeric|min:0',
            'aciklama' => 'nullable|string|max:255',
        ]);

        if (!Schema::hasTable('gg_standart_kalemler')) {
            return back()->with('error', 'gg_standart_kalemler tablosu yok — migration/SQL çalıştırılmalı.');
        }

        DB::table('gg_standart_kalemler')->insert([
            'tip'        => $data['tip'],
            'baslik'     => trim($data['baslik']),
            'tutar'      => $data['tutar'],
            'aciklama'   => $data['aciklama'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $etiket = $data['tip'] === 'gelir' ? 'Standart aylık gelir' : 'Standart aylık gider';
        return back()->with('success', $etiket . ' eklendi.');
    }

    /**
     * Standart aylık kalem SİL.
     */
    public function standartSil(int $id)
    {
        if (Schema::hasTable('gg_standart_kalemler')) {
            DB::table('gg_standart_kalemler')->where('id', $id)->delete();
        }

        return back()->with('success', 'Kalem silindi.');
    }

    /**
     * Dropdown 1: Onaylanan (tahsil edilen) faturalar — dönem içinde durum=1.
     */
    private function onaylananFaturalar($bas, $bit)
    {
        if (!Schema::hasTable('faturalar')) {
            return collect();
        }

        return DB::table('faturalar')
            ->leftJoin('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->whereTarihBetween('faturalar.tarih', $bas, $bit)
            ->where('faturalar.durum', 1)
            ->orderByDesc('faturalar.tarih')
            ->select(
                'faturalar.id',
                'faturalar.fatura_no',
                'faturalar.baslik',
                'faturalar.hizmet',
                'faturalar.tutar',
                'faturalar.tarih',
                'uyeler.ad',
                'uyeler.soyad',
                'uyeler.firmaadi'
            )
            ->get();
    }

    /**
     * Dropdown: Bekleyen (tahsil edilmemiş) faturalar — dönem içinde durum=0.
     */
    private function bekleyenFaturalar($bas, $bit)
    {
        if (!Schema::hasTable('faturalar')) {
            return collect();
        }

        return DB::table('faturalar')
            ->leftJoin('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->whereTarihBetween('faturalar.tarih', $bas, $bit)
            ->where('faturalar.durum', 0)
            ->orderByDesc('faturalar.tarih')
            ->select(
                'faturalar.id',
                'faturalar.fatura_no',
                'faturalar.baslik',
                'faturalar.hizmet',
                'faturalar.tutar',
                'faturalar.tarih',
                'uyeler.ad',
                'uyeler.soyad',
                'uyeler.firmaadi'
            )
            ->get();
    }

    /**
     * Dropdown 2: Harcamalar (giderler) — dönem içindeki tüm gider kalemleri.
     */
    private function harcamaListesi($bas, $bit)
    {
        if (!Schema::hasTable('giderler')) {
            return collect();
        }

        $basT = Carbon::parse($bas)->toDateString();
        $bitT = Carbon::parse($bit)->toDateString();

        $q = DB::table('giderler as g')
            ->whereTarihBetween('g.gider_tarihi', $basT, $bitT)
            ->orderByDesc('g.gider_tarihi');

        if (Schema::hasTable('gider_kategorileri')) {
            $q->leftJoin('gider_kategorileri as k', 'k.id', '=', 'g.kategori_id')
              ->select('g.id', 'g.baslik', 'g.tutar', 'g.gider_tarihi', 'g.durum',
                       'k.ad as kategori_adi', 'k.renk as kategori_renk', 'k.ikon as kategori_ikon');
        } else {
            $q->select('g.id', 'g.baslik', 'g.tutar', 'g.gider_tarihi', 'g.durum');
        }

        return $q->get();
    }

    /**
     * Dropdown 3: Birleşik hareketler — gelir (onaylanan fatura) + gider (harcama)
     * tek bir kronolojik akışta. Her satır tip='gelir'|'gider', işaretli tutar.
     */
    private function birlesikHareketler($faturalar, $harcamalar)
    {
        $hareketler = collect();

        foreach ($faturalar as $f) {
            $musteri = $f->firmaadi ?: trim(($f->ad ?? '') . ' ' . ($f->soyad ?? ''));
            $hareketler->push((object) [
                'tip'      => 'gelir',
                'tarih'    => $f->tarih,
                'baslik'   => $f->baslik ?: ($f->hizmet ?: ('Fatura #' . ($f->fatura_no ?: $f->id))),
                'detay'    => $musteri !== '' ? $musteri : '—',
                'tutar'    => (float) $f->tutar,
            ]);
        }

        foreach ($harcamalar as $h) {
            $hareketler->push((object) [
                'tip'      => 'gider',
                'tarih'    => $h->gider_tarihi,
                'baslik'   => $h->baslik,
                'detay'    => $h->kategori_adi ?? '—',
                'tutar'    => (float) $h->tutar,
            ]);
        }

        // Tarihe göre (yeni → eski) sırala
        return $hareketler->sortByDesc(function ($x) {
            return $x->tarih ? Carbon::parse($x->tarih)->timestamp : 0;
        })->values();
    }

    /**
     * Gelir (faturalar): tahsil edilen (durum=1), bekleyen (durum=0), toplam.
     */
    private function gelirHesapla($bas, $bit): array
    {
        if (!Schema::hasTable('faturalar')) {
            return ['tahsil' => 0, 'bekleyen' => 0, 'toplam' => 0, 'adet' => 0];
        }

        $q = DB::table('faturalar')
            ->whereTarihBetween('tarih', $bas, $bit)
            ->where('durum', '!=', 2);

        $tahsil = (float) (clone $q)->where('durum', 1)->sum('tutar');
        $bekleyen = (float) (clone $q)->where('durum', 0)->sum('tutar');
        $adet = (int) (clone $q)->count();

        return [
            'tahsil' => $tahsil,
            'bekleyen' => $bekleyen,
            'toplam' => $tahsil + $bekleyen,
            'adet' => $adet,
        ];
    }

    /**
     * Gider (giderler): ödenen, bekleyen, toplam.
     */
    private function giderHesapla($bas, $bit): array
    {
        if (!Schema::hasTable('giderler')) {
            return ['odenen' => 0, 'bekleyen' => 0, 'toplam' => 0, 'adet' => 0];
        }

        $basT = Carbon::parse($bas)->toDateString();
        $bitT = Carbon::parse($bit)->toDateString();

        $q = DB::table('giderler')->whereTarihBetween('gider_tarihi', $basT, $bitT);

        $odenen = (float) (clone $q)->where('durum', 'odendi')->sum('tutar');
        $bekleyen = (float) (clone $q)->whereIn('durum', ['bekliyor', 'gecikti'])->sum('tutar');
        $adet = (int) (clone $q)->count();

        return [
            'odenen' => $odenen,
            'bekleyen' => $bekleyen,
            'toplam' => $odenen + $bekleyen,
            'adet' => $adet,
        ];
    }

    /**
     * Dönem içindeki her ay için gelir/gider kırılımı (grafik).
     */
    private function aylikKirilim($bas, $bit): array
    {
        $basC = Carbon::parse($bas)->startOfMonth();
        $bitC = Carbon::parse($bit)->endOfMonth();

        $aylar = [];
        $cursor = $basC->copy();
        $guvenlik = 0;
        while ($cursor->lte($bitC) && $guvenlik < 24) {
            $ayBas = $cursor->copy()->startOfMonth();
            $ayBit = $cursor->copy()->endOfMonth();

            $gelir = 0;
            if (Schema::hasTable('faturalar')) {
                $gelir = (float) DB::table('faturalar')
                    ->whereTarihBetween('tarih', $ayBas->toDateTimeString(), $ayBit->toDateTimeString())
                    ->where('durum', 1)
                    ->sum('tutar');
            }

            $gider = 0;
            if (Schema::hasTable('giderler')) {
                $gider = (float) DB::table('giderler')
                    ->whereTarihBetween('gider_tarihi', $ayBas->toDateString(), $ayBit->toDateString())
                    ->where('durum', 'odendi')
                    ->sum('tutar');
            }

            $aylar[] = [
                'etiket' => $cursor->translatedFormat('M Y'),
                'gelir' => round($gelir, 2),
                'gider' => round($gider, 2),
                'net' => round($gelir - $gider, 2),
            ];

            $cursor->addMonth();
            $guvenlik++;
        }

        return $aylar;
    }

    /**
     * Dönem aralığı [başlangıç, bitiş, metin].
     */
    private function donemAraligi(string $donem): array
    {
        $bugun = Carbon::today();
        switch ($donem) {
            case 'uc_ay':
                $bas = $bugun->copy()->subMonths(3)->startOfMonth()->startOfDay();
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
}