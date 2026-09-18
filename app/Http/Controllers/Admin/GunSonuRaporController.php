<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmailNotificationService;
use App\Services\FinansRaporu;
use App\Services\GunSonuRaporu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * GÜN SONU RAPORU — Panelden görüntüleme ekranı.
 * Aynı veriyi her akşam otomatik mail olarak da gönderiyoruz (gun:sonu-bildirimi).
 */
class GunSonuRaporController extends Controller
{
    public function index(Request $request)
    {
        $tarih = $request->filled('tarih')
            ? Carbon::parse($request->query('tarih'))
            : Carbon::today();

        $veri     = GunSonuRaporu::veri($tarih);
        $alicilar = FinansRaporu::alicilar();

        return view('admin.raporlar.gun-sonu', compact('veri', 'alicilar'));
    }

    /** Raporu şimdi mail olarak gönder */
    public function gonder(Request $request)
    {
        $tarih = $request->filled('tarih')
            ? Carbon::parse($request->input('tarih'))
            : Carbon::today();

        $veri     = GunSonuRaporu::veri($tarih);
        $alicilar = FinansRaporu::alicilar();

        if (empty($alicilar)) {
            return back()->with('error', 'Rapor gönderilecek yönetici e-postası bulunamadı.');
        }

        $toplamAktivite = $veri['yeni_musteriler']->count() + $veri['yeni_notlar']->count()
            + $veri['yeni_gorevler']->count() + $veri['tamamlanan_gorevler']->count()
            + $veri['personel_islemleri']->count();

        $konu  = '📋 Gün Sonu Bildirimi — ' . $veri['tarih']->format('d.m.Y')
               . ' (' . $toplamAktivite . ' aktivite)';
        $govde = GunSonuRaporu::mailGovdesi($veri);

        $sayac = 0;
        foreach ($alicilar as $mail) {
            try {
                EmailNotificationService::send($mail, $konu, $govde);
                $sayac++;
            } catch (\Throwable $e) {
                Log::warning('Gün sonu bildirimi elle gönderilemedi', ['mail' => $mail, 'hata' => $e->getMessage()]);
            }
        }

        return back()->with(
            $sayac ? 'success' : 'error',
            $sayac
                ? $veri['tarih']->format('d.m.Y') . ' gün sonu bildirimi ' . $sayac . ' kişiye gönderildi.'
                : 'Rapor gönderilemedi.'
        );
    }
}
