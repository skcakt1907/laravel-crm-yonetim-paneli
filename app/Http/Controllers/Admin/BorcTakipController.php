<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Borç Takip — SGK / Vergi / Krediler.
 * Her kayıt tip='borc' (+) veya tip='odeme' (-).
 * Net (kalan borç) = toplam borç - toplam ödeme.
 * Muhasebe ve patron erişir.
 */
class BorcTakipController extends Controller
{
    /** Geçerli kategoriler ve görünen adları */
    private array $kategoriler = [
        'sgk'   => ['ad' => 'SGK',       'ikon' => '🏛️', 'renk' => '#0ea5e9'],
        'vergi' => ['ad' => 'Vergi',     'ikon' => '🧾', 'renk' => '#f59e0b'],
        'kredi' => ['ad' => 'Krediler',  'ikon' => '🏦', 'renk' => '#8b5cf6'],
    ];

    public function index(Request $request)
    {
        if (!Schema::hasTable('borc_takip')) {
            return view('admin.borc-takip.index', [
                'kurulumGerekli' => true,
                'kategoriler'    => $this->kategoriler,
                'veriler'        => [],
                'ozet'           => [],
                'genelToplam'    => ['borc' => 0, 'odeme' => 0, 'net' => 0],
            ]);
        }

        $veriler = [];
        $ozet = [];
        $genelBorc = 0;
        $genelOdeme = 0;

        foreach ($this->kategoriler as $key => $bilgi) {
            $kayitlar = DB::table('borc_takip')
                ->where('kategori', $key)
                ->orderByDesc('tarih')
                ->orderByDesc('id')
                ->get();

            $borcToplam  = (float) $kayitlar->where('tip', 'borc')->sum('tutar');
            $odemeToplam = (float) $kayitlar->where('tip', 'odeme')->sum('tutar');
            $net = $borcToplam - $odemeToplam;

            $veriler[$key] = $kayitlar;
            $ozet[$key] = [
                'borc'  => $borcToplam,
                'odeme' => $odemeToplam,
                'net'   => $net,
                'adet'  => $kayitlar->count(),
            ];

            $genelBorc  += $borcToplam;
            $genelOdeme += $odemeToplam;
        }

        return view('admin.borc-takip.index', [
            'kurulumGerekli' => false,
            'kategoriler'    => $this->kategoriler,
            'veriler'        => $veriler,
            'ozet'           => $ozet,
            'genelToplam'    => [
                'borc'  => $genelBorc,
                'odeme' => $genelOdeme,
                'net'   => $genelBorc - $genelOdeme,
            ],
        ]);
    }

    /**
     * Yeni borç/ödeme kaydı ekle.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kategori' => 'required|string|in:sgk,vergi,kredi',
            'tip'      => 'required|string|in:borc,odeme',
            'baslik'   => 'required|string|max:200',
            'tutar'    => 'required|numeric|min:0',
            'tarih'    => 'required|date',
            'aciklama' => 'nullable|string',
        ]);

        DB::table('borc_takip')->insert([
            'kategori'     => $request->kategori,
            'tip'          => $request->tip,
            'baslik'       => $request->baslik,
            'aciklama'     => $request->aciklama,
            'tutar'        => (float) $request->tutar,
            'tarih'        => $request->tarih,
            'olusturan_id' => session('admin_id'),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $tipAd = $request->tip === 'borc' ? 'Borç' : 'Ödeme';
        return redirect()->route('admin.borc-takip.index')
            ->with('success', $tipAd . ' kaydı eklendi.');
    }

    /**
     * Kaydı düzenle.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tip'      => 'required|string|in:borc,odeme',
            'baslik'   => 'required|string|max:200',
            'tutar'    => 'required|numeric|min:0',
            'tarih'    => 'required|date',
            'aciklama' => 'nullable|string',
        ]);

        $kayit = DB::table('borc_takip')->where('id', $id)->first();
        if (!$kayit) {
            return redirect()->route('admin.borc-takip.index')
                ->with('error', 'Kayıt bulunamadı.');
        }

        DB::table('borc_takip')->where('id', $id)->update([
            'tip'        => $request->tip,
            'baslik'     => $request->baslik,
            'aciklama'   => $request->aciklama,
            'tutar'      => (float) $request->tutar,
            'tarih'      => $request->tarih,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.borc-takip.index')
            ->with('success', 'Kayıt güncellendi.');
    }

    /**
     * Kaydı sil.
     */
    public function destroy($id)
    {
        DB::table('borc_takip')->where('id', $id)->delete();

        return redirect()->route('admin.borc-takip.index')
            ->with('success', 'Kayıt silindi.');
    }
}