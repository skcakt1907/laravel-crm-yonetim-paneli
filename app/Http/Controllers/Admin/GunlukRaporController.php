<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmailNotificationService;
use App\Services\FinansRaporu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * GÜNLÜK RAPORLAR — Tüm Hareketler ekranı.
 * Seçilen günün gelir/gider hareketlerini gösterir; aynı raporu elle
 * muhasebeye mail olarak da gönderebilir (otomatiği her akşam 23:30).
 */
class GunlukRaporController extends Controller
{
    public function index(Request $request)
    {
        $tarih = $request->filled('tarih')
            ? Carbon::parse($request->query('tarih'))
            : Carbon::today();

        $veri = FinansRaporu::gunlukVeri($tarih);
        $alicilar = FinansRaporu::alicilar();

        return view('admin.raporlar.gunluk', compact('veri', 'alicilar'));
    }

    /** Raporu şimdi mail olarak gönder */
    public function gonder(Request $request)
    {
        $tarih = $request->filled('tarih')
            ? Carbon::parse($request->input('tarih'))
            : Carbon::today();

        $veri     = FinansRaporu::gunlukVeri($tarih);
        $alicilar = FinansRaporu::alicilar();

        if (empty($alicilar)) {
            return back()->with('error', 'Rapor gönderilecek yönetici e-postası bulunamadı.');
        }

        $konu  = '📊 Günlük Finans Raporu — ' . $veri['tarih']->format('d.m.Y')
               . ' (net ' . number_format($veri['net'], 2, ',', '.') . ' TL)';
        $govde = FinansRaporu::gunlukMailGovdesi($veri);

        $sayac = 0;
        foreach ($alicilar as $mail) {
            try {
                EmailNotificationService::send($mail, $konu, $govde);
                $sayac++;
            } catch (\Throwable $e) {
                \Log::warning('Günlük rapor elle gönderilemedi', ['mail' => $mail, 'hata' => $e->getMessage()]);
            }
        }

        return back()->with(
            $sayac ? 'success' : 'error',
            $sayac
                ? $veri['tarih']->format('d.m.Y') . ' raporu ' . $sayac . ' kişiye gönderildi.'
                : 'Rapor gönderilemedi.'
        );
    }
}
