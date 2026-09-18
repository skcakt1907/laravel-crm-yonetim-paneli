<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * KATEGORİYE TOPLU İNDİRİM (kart #236).
 *
 * Bir kategorideki tüm paketlere tek seferde yüzde veya sabit tutar indirimi uygular.
 * Orijinal fiyat `yazilimlar.eski_tutar` alanında saklanır; indirim geri alınabilir.
 *
 * NOT: `yazilimlar` MyISAM olduğu için transaction YOK. Bu yüzden önce ne olacağı
 * hesaplanıp ekranda gösteriliyor (önizleme), onay sonrası tek UPDATE ile yazılıyor.
 */
class TopluIndirimController extends Controller
{
    /** Fiyat alanı sayıya çevrilebiliyor mu (266 paketin 2'si "Teklif sürecinde..." gibi metin). */
    private function sayiMi(?string $tutar): bool
    {
        $t = trim((string) $tutar);
        return $t !== '' && preg_match('/^\d+(?:[.,]\d+)?$/', $t) === 1;
    }

    private function sayiyaCevir(?string $tutar): float
    {
        return (float) str_replace(',', '.', trim((string) $tutar));
    }

    /** Fiyatı veritabanındaki biçimde (ondalık yoksa tam sayı) yazıya çevirir. */
    private function yaziyaCevir(float $deger): string
    {
        $yuvarlak = round($deger, 2);
        return floor($yuvarlak) == $yuvarlak
            ? (string) (int) $yuvarlak
            : rtrim(rtrim(number_format($yuvarlak, 2, '.', ''), '0'), '.');
    }

    /** Kategorileri paket sayısı + indirimli paket sayısıyla birlikte getirir. */
    private function kategoriler(): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('web_kategori') || !Schema::hasTable('yazilimlar')) {
            return collect([]);
        }

        $indirimVar = Schema::hasColumn('yazilimlar', 'eski_tutar');

        return DB::table('web_kategori as k')
            ->leftJoin('yazilimlar as y', DB::raw('CAST(y.kategori AS UNSIGNED)'), '=', 'k.id')
            ->select('k.id', 'k.adi')
            ->selectRaw('COUNT(y.id) as paket_sayisi')
            ->selectRaw($indirimVar
                ? "SUM(CASE WHEN y.eski_tutar IS NOT NULL AND y.eski_tutar <> '' THEN 1 ELSE 0 END) as indirimli"
                : '0 as indirimli')
            ->groupBy('k.id', 'k.adi')
            ->having('paket_sayisi', '>', 0)
            ->orderBy('k.adi')
            ->get();
    }

    /** Seçili kategorinin paketleri + indirim uygulanınca oluşacak fiyatlar. */
    private function onizleme(?int $kategoriId, string $tip, float $deger): array
    {
        $satirlar = collect([]);
        $atlanan  = collect([]);

        if (!$kategoriId) {
            return ['satirlar' => $satirlar, 'atlanan' => $atlanan];
        }

        $paketler = DB::table('yazilimlar')
            ->whereRaw('CAST(kategori AS UNSIGNED) = ?', [$kategoriId])
            ->select('id', 'adi', 'tutar', 'eski_tutar', 'durum')
            ->orderBy('adi')
            ->get();

        foreach ($paketler as $p) {
            // İndirim her zaman ORİJİNAL fiyat üzerinden hesaplanır
            $temel = ($p->eski_tutar !== null && $p->eski_tutar !== '') ? $p->eski_tutar : $p->tutar;

            if (!$this->sayiMi($temel)) {
                $atlanan->push($p);
                continue;
            }

            $eski = $this->sayiyaCevir($temel);
            $yeni = $tip === 'yuzde' ? $eski - ($eski * $deger / 100) : $eski - $deger;
            if ($yeni < 0) $yeni = 0;

            $p->hesap_eski = $eski;
            $p->hesap_yeni = $yeni;
            $p->hesap_fark = $eski - $yeni;
            $satirlar->push($p);
        }

        return ['satirlar' => $satirlar, 'atlanan' => $atlanan];
    }

    public function index(Request $request)
    {
        $kategoriId = (int) $request->query('kategori_id', 0) ?: null;
        $tip        = $request->query('tip') === 'tutar' ? 'tutar' : 'yuzde';
        $deger      = (float) str_replace(',', '.', (string) $request->query('deger', 0));
        if ($deger < 0) $deger = 0;
        if ($tip === 'yuzde' && $deger > 100) $deger = 100;

        $onizleme = ($kategoriId && $deger > 0)
            ? $this->onizleme($kategoriId, $tip, $deger)
            : ['satirlar' => collect([]), 'atlanan' => collect([])];

        return view('admin.paketler.toplu-indirim', [
            'kategoriler'   => $this->kategoriler(),
            'kategoriId'    => $kategoriId,
            'tip'           => $tip,
            'deger'         => $deger,
            'satirlar'      => $onizleme['satirlar'],
            'atlanan'       => $onizleme['atlanan'],
            'kolonYok'      => !Schema::hasColumn('yazilimlar', 'eski_tutar'),
        ]);
    }

    public function uygula(Request $request)
    {
        if (!Schema::hasColumn('yazilimlar', 'eski_tutar')) {
            return back()->with('error', 'eski_tutar kolonu yok — önce migration çalıştırılmalı.');
        }

        $data = $request->validate([
            'kategori_id' => 'required|integer|min:1',
            'tip'         => 'required|in:yuzde,tutar',
            'deger'       => 'required|numeric|min:0.01',
        ], [], [
            'kategori_id' => 'kategori',
            'tip'         => 'indirim tipi',
            'deger'       => 'indirim değeri',
        ]);

        $deger = (float) $data['deger'];
        if ($data['tip'] === 'yuzde' && $deger > 100) {
            return back()->with('error', 'Yüzde indirim 100\'den büyük olamaz.');
        }

        $onizleme = $this->onizleme((int) $data['kategori_id'], $data['tip'], $deger);
        $satirlar = $onizleme['satirlar'];

        if ($satirlar->isEmpty()) {
            return back()->with('error', 'Bu kategoride fiyatı sayısal olan paket bulunamadı; hiçbir şey değiştirilmedi.');
        }

        $guncellenen = 0;
        foreach ($satirlar as $p) {
            $guncel = ['tutar' => $this->yaziyaCevir($p->hesap_yeni)];

            // Orijinal fiyat SADECE ilk indirimde yazılır; sonraki indirimlerde korunur
            if ($p->eski_tutar === null || $p->eski_tutar === '') {
                $guncel['eski_tutar'] = (string) $p->tutar;
            }

            DB::table('yazilimlar')->where('id', $p->id)->update($guncel);
            $guncellenen++;
        }

        $kategoriAdi = DB::table('web_kategori')->where('id', $data['kategori_id'])->value('adi');

        Log::info('Kategoriye toplu indirim uygulandı', [
            'kategori'    => $kategoriAdi,
            'tip'         => $data['tip'],
            'deger'       => $deger,
            'guncellenen' => $guncellenen,
            'atlanan'     => $onizleme['atlanan']->count(),
            'yonetici'    => session('admin_id'),
        ]);

        $mesaj = "\"{$kategoriAdi}\" kategorisinde {$guncellenen} paketin fiyatı güncellendi.";
        if ($onizleme['atlanan']->count() > 0) {
            $mesaj .= ' ' . $onizleme['atlanan']->count() . ' paket atlandı (fiyatı sayı değil).';
        }

        return redirect()
            ->route('admin.paketler.toplu-indirim', ['kategori_id' => $data['kategori_id']])
            ->with('success', $mesaj);
    }

    public function kaldir(Request $request)
    {
        if (!Schema::hasColumn('yazilimlar', 'eski_tutar')) {
            return back()->with('error', 'eski_tutar kolonu yok.');
        }

        $data = $request->validate(['kategori_id' => 'required|integer|min:1']);

        $indirimliler = DB::table('yazilimlar')
            ->whereRaw('CAST(kategori AS UNSIGNED) = ?', [$data['kategori_id']])
            ->whereNotNull('eski_tutar')
            ->where('eski_tutar', '<>', '')
            ->select('id', 'eski_tutar')
            ->get();

        if ($indirimliler->isEmpty()) {
            return back()->with('error', 'Bu kategoride geri alınacak indirim yok.');
        }

        foreach ($indirimliler as $p) {
            DB::table('yazilimlar')->where('id', $p->id)->update([
                'tutar'      => $p->eski_tutar,
                'eski_tutar' => null,
            ]);
        }

        $kategoriAdi = DB::table('web_kategori')->where('id', $data['kategori_id'])->value('adi');

        Log::info('Kategoriye toplu indirim geri alındı', [
            'kategori' => $kategoriAdi,
            'adet'     => $indirimliler->count(),
            'yonetici' => session('admin_id'),
        ]);

        return redirect()
            ->route('admin.paketler.toplu-indirim', ['kategori_id' => $data['kategori_id']])
            ->with('success', "\"{$kategoriAdi}\" kategorisinde {$indirimliler->count()} paketin fiyatı eski hâline döndürüldü.");
    }
}
