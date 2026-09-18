<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FİNANS RAPORLARI — günlük gelir-gider ve aylık borç takip özeti.
 *
 * Günlük rapor her akşam muhasebeye mail olarak gider (finans:gunluk-rapor).
 * Borç takip raporu her ayın 28'inde yönetime gider (finans:borc-raporu).
 * Aynı veriler "Günlük Raporlar" ekranında da görüntülenir.
 */
class FinansRaporu
{
    /** Rapor gidecek yöneticiler (kullaniciadi) */
    public const ALICILAR = ['nurselinan', 'dilanatescom', 'NesimiAtes'];

    /* ═══════════════════ VERİ ═══════════════════ */

    /**
     * Bir günün tüm finans hareketleri.
     * @return array{tarih:Carbon, gelir:array, gider:array, gelir_toplam:float, gider_toplam:float, net:float}
     */
    public static function gunlukVeri(?Carbon $gun = null): array
    {
        $gun = ($gun ?? Carbon::today())->copy()->startOfDay();
        $son = $gun->copy()->endOfDay();

        /* ── GELİR: o gün ödenmiş faturalar ── */
        $gelir = collect();
        try {
            $gelir = DB::table('faturalar')
                ->leftJoin('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
                ->where('faturalar.durum', 1)
                // odenen_tarih VARCHAR: bazi satirlar '2026-07-03', bazilari
                // '2026-05-20 00:00:00'. Metin karsilastirmasinda saatsiz deger
                // gun basi sinirindan KUCUK sayilip araliga girmiyordu -> rapor 0.
                // DATE() ile karsilastirinca iki bicim de dogru eslesiyor.
                ->whereRaw('DATE(faturalar.odenen_tarih) = ?', [$gun->toDateString()])
                ->select(
                    'faturalar.id', 'faturalar.fatura_no', 'faturalar.baslik',
                    'faturalar.tutar', 'faturalar.odeme_yontemi', 'faturalar.odenen_tarih',
                    DB::raw("TRIM(CONCAT(COALESCE(uyeler.ad,''),' ',COALESCE(uyeler.soyad,''))) as musteri")
                )
                ->orderBy('faturalar.odenen_tarih')
                ->get();
        } catch (\Throwable $e) {}

        /* ── GİDER: o gün yapılan giderler ── */
        $gider = collect();
        try {
            if (Schema::hasTable('giderler')) {
                $gider = DB::table('giderler')
                    ->leftJoin('gider_kategorileri as k', 'k.id', '=', 'giderler.kategori_id')
                    ->whereTarihBetween('giderler.gider_tarihi', $gun->toDateString(), $son->toDateString())
                    ->select('giderler.id', 'giderler.baslik', 'giderler.tutar',
                             'giderler.odeme_yontemi', 'giderler.gider_tarihi', 'k.ad as kategori')
                    ->orderBy('giderler.id')
                    ->get();
            }
        } catch (\Throwable $e) {}

        $gelirToplam = (float) $gelir->sum('tutar');
        $giderToplam = (float) $gider->sum('tutar');

        return [
            'tarih'        => $gun,
            'gelir'        => $gelir,
            'gider'        => $gider,
            'gelir_toplam' => $gelirToplam,
            'gider_toplam' => $giderToplam,
            'net'          => round($gelirToplam - $giderToplam, 2),
        ];
    }

    /** Borç takip özeti (şirket borçları + müşteri borçları) */
    public static function borcVerisi(): array
    {
        $sirket = collect();
        $musteri = collect();

        try {
            if (Schema::hasTable('borc_takip')) {
                $sirket = DB::table('borc_takip')->orderByDesc('tarih')->get();
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('musteri_borc_takip')) {
                $musteri = DB::table('musteri_borc_takip as m')
                    ->leftJoin('crm_customers as c', 'c.id', '=', 'm.musteri_id')
                    ->select('m.*', 'c.adi as musteri_adi')
                    ->orderByDesc('m.tarih')->get();
            }
        } catch (\Throwable $e) {}

        return [
            'sirket'         => $sirket,
            'musteri'        => $musteri,
            'sirket_toplam'  => (float) $sirket->sum('tutar'),
            'musteri_toplam' => (float) $musteri->sum('tutar'),
        ];
    }

    /* ═══════════════════ ALICILAR ═══════════════════ */

    public static function alicilar(): array
    {
        try {
            return DB::table('yoneticiler')
                ->whereIn('kullaniciadi', self::ALICILAR)
                ->where('durum', 1)
                ->whereNotNull('email')->where('email', '!=', '')
                ->pluck('email')->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* ═══════════════════ MAİL GÖVDELERİ ═══════════════════ */

    public static function gunlukMailGovdesi(array $v): string
    {
        $satirlar = '';
        foreach ($v['gelir'] as $g) {
            $satirlar .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($g->fatura_no ?: ('#' . $g->id)) . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($g->musteri ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($g->odeme_yontemi ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px;text-align:right;color:#1E6B2F;font-weight:700">+' . number_format((float) $g->tutar, 2, ',', '.') . '</td>'
                . '</tr>';
        }
        if ($satirlar === '') {
            $satirlar = '<tr><td colspan="4" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bu gün tahsilat yok.</td></tr>';
        }

        $gsat = '';
        foreach ($v['gider'] as $g) {
            $gsat .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($g->baslik ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($g->kategori ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($g->odeme_yontemi ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px;text-align:right;color:#9C2B2B;font-weight:700">-' . number_format((float) $g->tutar, 2, ',', '.') . '</td>'
                . '</tr>';
        }
        if ($gsat === '') {
            $gsat = '<tr><td colspan="4" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bu gün gider yok.</td></tr>';
        }

        $netRenk = $v['net'] >= 0 ? '#1E6B2F' : '#9C2B2B';

        return self::sablon(
            '📊 Günlük Finans Raporu',
            $v['tarih']->format('d.m.Y') . ' — tüm hareketler',
            '
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px">
              <tr>
                <td style="padding:14px;background:#E2EFDA;border-radius:10px;text-align:center;width:33%">
                  <div style="font-size:11px;font-weight:800;color:#1E6B2F;text-transform:uppercase">Gelir</div>
                  <div style="font-size:19px;font-weight:800;color:#1E6B2F;margin-top:3px">' . number_format($v['gelir_toplam'], 2, ',', '.') . ' TL</div>
                  <div style="font-size:11px;color:#5b6168;margin-top:2px">' . $v['gelir']->count() . ' tahsilat</div>
                </td>
                <td style="width:10px"></td>
                <td style="padding:14px;background:#F8D7DA;border-radius:10px;text-align:center;width:33%">
                  <div style="font-size:11px;font-weight:800;color:#9C2B2B;text-transform:uppercase">Gider</div>
                  <div style="font-size:19px;font-weight:800;color:#9C2B2B;margin-top:3px">' . number_format($v['gider_toplam'], 2, ',', '.') . ' TL</div>
                  <div style="font-size:11px;color:#5b6168;margin-top:2px">' . $v['gider']->count() . ' kalem</div>
                </td>
                <td style="width:10px"></td>
                <td style="padding:14px;background:#fbfcf7;border:1px solid #eef0e6;border-radius:10px;text-align:center;width:33%">
                  <div style="font-size:11px;font-weight:800;color:#8a8718;text-transform:uppercase">Net</div>
                  <div style="font-size:19px;font-weight:800;color:' . $netRenk . ';margin-top:3px">' . number_format($v['net'], 2, ',', '.') . ' TL</div>
                </td>
              </tr>
            </table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Tahsilatlar</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px;margin-bottom:20px">' . $satirlar . '</table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Giderler</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px">' . $gsat . '</table>
            '
        );
    }

    public static function borcMailGovdesi(array $v): string
    {
        $sat = function ($baslik, $tutar, $tarih, $ek = null) {
            return '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($baslik ?: '—')
                . ($ek ? '<div style="font-size:11px;color:#9aa0a6">' . htmlspecialchars($ek) . '</div>' : '') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . ($tarih ? Carbon::parse($tarih)->format('d.m.Y') : '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px;text-align:right;font-weight:700">' . number_format((float) $tutar, 2, ',', '.') . '</td>'
                . '</tr>';
        };

        $s1 = '';
        foreach ($v['sirket'] as $b) {
            $s1 .= $sat($b->baslik, $b->tutar, $b->tarih, trim(($b->kategori ?? '') . ' ' . ($b->tip ?? '')));
        }
        if ($s1 === '') $s1 = '<tr><td colspan="3" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Kayıt yok.</td></tr>';

        $s2 = '';
        foreach ($v['musteri'] as $b) {
            $s2 .= $sat($b->musteri_adi ?: $b->baslik, $b->tutar, $b->tarih, $b->baslik);
        }
        if ($s2 === '') $s2 = '<tr><td colspan="3" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Kayıt yok.</td></tr>';

        return self::sablon(
            '💼 Aylık Borç Takip Raporu',
            Carbon::now()->format('F Y') . ' — ayın 28\'i özeti',
            '
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px"><tr>
              <td style="padding:14px;background:#FFF2CC;border-radius:10px;text-align:center;width:49%">
                <div style="font-size:11px;font-weight:800;color:#8A6200;text-transform:uppercase">Şirket Borçları</div>
                <div style="font-size:19px;font-weight:800;color:#8A6200;margin-top:3px">' . number_format($v['sirket_toplam'], 2, ',', '.') . ' TL</div>
                <div style="font-size:11px;color:#5b6168;margin-top:2px">' . $v['sirket']->count() . ' kayıt</div>
              </td>
              <td style="width:2%"></td>
              <td style="padding:14px;background:#D9E1F2;border-radius:10px;text-align:center;width:49%">
                <div style="font-size:11px;font-weight:800;color:#1F4E78;text-transform:uppercase">Müşteri Borçları</div>
                <div style="font-size:19px;font-weight:800;color:#1F4E78;margin-top:3px">' . number_format($v['musteri_toplam'], 2, ',', '.') . ' TL</div>
                <div style="font-size:11px;color:#5b6168;margin-top:2px">' . $v['musteri']->count() . ' kayıt</div>
              </td>
            </tr></table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Şirket Borçları</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px;margin-bottom:20px">' . $s1 . '</table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Müşteri Borçları</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px">' . $s2 . '</table>
            '
        );
    }

    /** Ortak kurumsal mail iskeleti */
    private static function sablon(string $baslik, string $altBaslik, string $icerik): string
    {
        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:Arial,Helvetica,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0e8;padding:32px 12px"><tr><td align="center">
  <table role="presentation" width="700" cellpadding="0" cellspacing="0" style="max-width:700px;width:100%;background:#fff;border-radius:18px;overflow:hidden">
    <tr><td style="background:#1a2332;padding:24px 32px;text-align:center">
      <img src="https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png" width="150" alt="DN Kreatif" style="display:block;margin:0 auto 10px">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Muhasebe Raporu</div>
    </td></tr>
    <tr><td style="height:5px;background:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:28px 32px 14px">
      <h2 style="margin:0 0 4px;font-size:21px;color:#1a1a0e">' . $baslik . '</h2>
      <p style="margin:0 0 22px;color:#6b6f63;font-size:13.5px">' . htmlspecialchars($altBaslik) . '</p>
      ' . $icerik . '
    </td></tr>
    <tr><td style="background:#1a2332;padding:18px 32px;text-align:center">
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . date('Y') . ' DN Kreatif &middot; İş Ortağım &middot; Otomatik rapor</p>
    </td></tr>
  </table>
</td></tr></table></body></html>';
    }
}
