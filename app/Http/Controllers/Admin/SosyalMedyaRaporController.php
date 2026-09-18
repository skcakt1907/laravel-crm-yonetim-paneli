<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CRM\SosyalMedyaHesap;
use App\Models\Yonetici;
use App\Services\SosyalMedyaTakip;
use App\Support\SosyalMedyaYetki;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * SOSYAL MEDYA TAKİP — raporlar
 *
 * Plan ve işaretleme ekranlarından AYRI: burası yalnızca OKUMA. Hiçbir
 * metodu veri değiştirmez, hepsi GET. Plan controller'ının içinde
 * duruyordu; adı "Plan" olan bir sınıfta rapor aramak kimsenin aklına
 * gelmeyeceği için ayrıldı.
 *
 * Yetki de bu yüzden ayrı sayfa üzerinden okunuyor: raporu görmesi gereken
 * herkesin planı değiştirebilmesi gerekmiyor (ör. patron rapora bakar,
 * planı sosyal medya uzmanı kurar).
 */
class SosyalMedyaRaporController extends Controller
{
    private const SAYFA = 'admin.crm.sosyal-medya-takip.aylik';

    /**
     * Aylık rapor.
     *
     * Hesap servis içinde toplu yapılır (aylikVeri); burada yalnızca ay
     * seçimi ve uzman filtresi çözülür.
     */
    public function aylik(Request $request, SosyalMedyaTakip $takip)
    {
        SosyalMedyaYetki::zorunlu(self::SAYFA);

        $ay        = $this->ayCoz($request->string('ay')->toString());
        $sorumluId = $request->integer('sorumlu') ?: null;

        return view('admin.sosyal-medya.aylik', $takip->aylikVeri($ay, $sorumluId) + [
            'uzmanlar'  => $this->sorumluOlanlar(),
            'sorumluId' => $sorumluId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────

    /**
     * "2026-09" -> ayın ilk günü.
     *
     * Bozuk parametre ekranı kırmasın diye try/catch: Carbon geçersiz
     * biçimde InvalidFormatException atıyor. Gelecek ay da bu aya düşer —
     * henüz yaşanmamış ayın raporu olmaz.
     */
    private function ayCoz(string $ham): Carbon
    {
        $buAy = Carbon::today()->startOfMonth();

        if ($ham === '') {
            return $buAy;
        }

        try {
            $ay = Carbon::createFromFormat('Y-m', $ham)->startOfMonth();
        } catch (\Throwable $e) {
            return $buAy;
        }

        return $ay->gt($buAy) ? $buAy : $ay;
    }

    /** Filtre listesi: yalnızca gerçekten bir hesaba atanmış uzmanlar */
    private function sorumluOlanlar()
    {
        return Yonetici::where('durum', 1)
            ->whereIn('id', SosyalMedyaHesap::whereNotNull('sorumlu_id')->distinct()->pluck('sorumlu_id'))
            ->orderBy('adi')
            ->get(['id', 'adi', 'kullaniciadi']);
    }
}
