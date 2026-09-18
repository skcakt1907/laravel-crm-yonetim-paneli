<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GÜN SONU BİLDİRİMİ — CRM aktivitesi + personel işlem özeti.
 *
 * Her akşam finans raporuyla aynı saatte gider (gun:sonu-bildirimi).
 * Aynı alıcı listesini kullanır (FinansRaporu::ALICILAR).
 */
class GunSonuRaporu
{
    /**
     * Bir günün CRM + personel aktivite özeti.
     */
    public static function veri(?Carbon $gun = null): array
    {
        $gun = ($gun ?? Carbon::today())->copy()->startOfDay();

        $yeniMusteriler = collect();
        try {
            if (Schema::hasTable('crm_customers')) {
                $yeniMusteriler = DB::table('crm_customers as c')
                    ->leftJoin('yoneticiler as s', 's.id', '=', 'c.sorumlu_id')
                    ->whereDate('c.created_at', $gun)
                    ->select('c.id', 'c.adi', 'c.unvan', 'c.kaynak', 'c.created_at', 's.adi as sorumlu')
                    ->orderBy('c.created_at')
                    ->get();
            }
        } catch (\Throwable $e) {}

        $yeniNotlar = collect();
        try {
            if (Schema::hasTable('crm_notes')) {
                $yeniNotlar = DB::table('crm_notes as n')
                    ->leftJoin('crm_customers as c', 'c.id', '=', 'n.musteri_id')
                    ->leftJoin('yoneticiler as y', 'y.id', '=', 'n.olusturan_id')
                    ->whereDate('n.created_at', $gun)
                    ->select('n.id', 'n.baslik', 'n.icerik', 'n.created_at', 'c.adi as musteri', 'y.adi as yazan')
                    ->orderBy('n.created_at')
                    ->get();
            }
        } catch (\Throwable $e) {}

        $yeniGorevler = collect();
        $tamamlananGorevler = collect();
        try {
            if (Schema::hasTable('crm_tasks')) {
                $yeniGorevler = DB::table('crm_tasks as t')
                    ->leftJoin('crm_customers as c', 'c.id', '=', 't.musteri_id')
                    ->leftJoin('yoneticiler as y', 'y.id', '=', 't.atanan_id')
                    ->whereDate('t.created_at', $gun)
                    ->select('t.id', 't.konu', 't.tip', 't.son_tarih', 't.created_at', 'c.adi as musteri', 'y.adi as atanan')
                    ->orderBy('t.created_at')
                    ->get();

                $tamamlananGorevler = DB::table('crm_tasks as t')
                    ->leftJoin('crm_customers as c', 'c.id', '=', 't.musteri_id')
                    ->leftJoin('yoneticiler as y', 'y.id', '=', 't.atanan_id')
                    ->whereDate('t.tamamlandi_at', $gun)
                    ->select('t.id', 't.konu', 't.tamamlandi_at', 'c.adi as musteri', 'y.adi as atanan')
                    ->orderBy('t.tamamlandi_at')
                    ->get();
            }
        } catch (\Throwable $e) {}

        $personelIslemleri = collect();
        try {
            if (Schema::hasTable('islem_gecmisi')) {
                $personelIslemleri = DB::table('islem_gecmisi')
                    ->whereDate('tarih', $gun)
                    ->select('kullanici_adi', 'aksiyon', 'aciklama', 'tarih')
                    ->orderBy('tarih')
                    ->get();
            }
        } catch (\Throwable $e) {}

        $personelOzet = $personelIslemleri
            ->groupBy(fn ($i) => $i->kullanici_adi ?: 'Sistem')
            ->map->count()
            ->sortDesc();

        /*
         * TICKET ÖZETİ (calisan_tickets — çalışanlar arası iş talepleri).
         * durum: 0=Bekliyor, 1=Çözüldü, 2=İptal (TicketController'daki gerçek
         * eşleme budur; modeldeki 4'lü DURUM_* sabitleri artık kullanılmıyor).
         * Tabloda "ne zaman çözüldü" bilgisi tutulmadığı için "bugün kapatılan"
         * yerine iki ayrı, veriyle tutarlı ölçü veriyoruz:
         *   - bugün açılan taleplerin şu anki durumu (kişi bazında)
         *   - şu an bekleyen toplam talepler (kişi bazında, tarihten bağımsız)
         */
        $ticketOzet = collect();
        try {
            if (Schema::hasTable('calisan_tickets')) {
                $bugunAcilan = DB::table('calisan_tickets')
                    ->whereDate('tarih', $gun)
                    ->select('atanan_id', 'atanan_adi', 'durum', DB::raw('count(*) as adet'))
                    ->groupBy('atanan_id', 'atanan_adi', 'durum')
                    ->get();

                $toplamBekleyen = DB::table('calisan_tickets')
                    ->where('durum', 0)
                    ->select('atanan_id', 'atanan_adi', DB::raw('count(*) as adet'))
                    ->groupBy('atanan_id', 'atanan_adi')
                    ->get()
                    ->keyBy(fn ($r) => $r->atanan_id ?? 0);

                $kisiler = $bugunAcilan->pluck('atanan_id')->merge($toplamBekleyen->keys())->unique();

                foreach ($kisiler as $atananId) {
                    $satirlar = $bugunAcilan->where('atanan_id', $atananId);
                    $ad = $satirlar->first()->atanan_adi ?? ($toplamBekleyen[$atananId]->atanan_adi ?? null);

                    $ticketOzet->push((object) [
                        'alici'              => $ad ?: 'Atanmamış',
                        'bugun_cozulen'      => (int) $satirlar->where('durum', 1)->sum('adet'),
                        'bugun_bekleyen'     => (int) $satirlar->where('durum', 0)->sum('adet'),
                        'bugun_iptal'        => (int) $satirlar->where('durum', 2)->sum('adet'),
                        'toplam_bekleyen'    => (int) ($toplamBekleyen[$atananId]->adet ?? 0),
                    ]);
                }
                $ticketOzet = $ticketOzet->sortByDesc('toplam_bekleyen')->values();
            }
        } catch (\Throwable $e) {}

        return [
            'tarih'                => $gun,
            'yeni_musteriler'      => $yeniMusteriler,
            'yeni_notlar'          => $yeniNotlar,
            'ticket_ozet'          => $ticketOzet,
            'yeni_gorevler'        => $yeniGorevler,
            'tamamlanan_gorevler'  => $tamamlananGorevler,
            'personel_islemleri'   => $personelIslemleri,
            'personel_ozet'        => $personelOzet,
        ];
    }

    /* ═══════════════════ MAİL GÖVDESİ ═══════════════════ */

    public static function mailGovdesi(array $v): string
    {
        $stat = function (string $baslik, int $adet, string $renk, string $zemin) {
            return '<td style="padding:14px 8px;background:' . $zemin . ';border-radius:10px;text-align:center">'
                 . '<div style="font-size:11px;font-weight:800;color:' . $renk . ';text-transform:uppercase">' . $baslik . '</div>'
                 . '<div style="font-size:19px;font-weight:800;color:' . $renk . ';margin-top:3px">' . $adet . '</div>'
                 . '</td>';
        };

        $ozetTablo = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px"><tr>'
            . $stat('Yeni Müşteri', $v['yeni_musteriler']->count(), '#1E6B2F', '#E2EFDA') . '<td style="width:8px"></td>'
            . $stat('Yeni Not', $v['yeni_notlar']->count(), '#1F4E78', '#D9E1F2') . '<td style="width:8px"></td>'
            . $stat('Yeni Görev', $v['yeni_gorevler']->count(), '#8A6200', '#FFF2CC') . '<td style="width:8px"></td>'
            . $stat('Tamamlanan Görev', $v['tamamlanan_gorevler']->count(), '#1E6B2F', '#E2EFDA') . '<td style="width:8px"></td>'
            . $stat('Personel İşlemi', $v['personel_islemleri']->count(), '#5b3ea6', '#EAE3F7') . '<td style="width:8px"></td>'
            . $stat('Bekleyen Ticket', (int) $v['ticket_ozet']->sum('toplam_bekleyen'), '#9C2B2B', '#F8D7DA')
            . '</tr></table>';

        $musteriSat = '';
        foreach ($v['yeni_musteriler'] as $m) {
            $musteriSat .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($m->adi ?: '—')
                . ($m->unvan ? '<div style="font-size:11px;color:#9aa0a6">' . htmlspecialchars($m->unvan) . '</div>' : '') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . htmlspecialchars($m->kaynak ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . htmlspecialchars($m->sorumlu ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168;text-align:right">' . Carbon::parse($m->created_at)->format('H:i') . '</td>'
                . '</tr>';
        }
        if ($musteriSat === '') $musteriSat = '<tr><td colspan="4" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bugün yeni müşteri eklenmedi.</td></tr>';

        $notSat = '';
        foreach ($v['yeni_notlar'] as $n) {
            $notSat .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($n->musteri ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . htmlspecialchars($n->baslik ?: mb_substr((string) $n->icerik, 0, 60)) . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . htmlspecialchars($n->yazan ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168;text-align:right">' . Carbon::parse($n->created_at)->format('H:i') . '</td>'
                . '</tr>';
        }
        if ($notSat === '') $notSat = '<tr><td colspan="4" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bugün yeni not eklenmedi.</td></tr>';

        $gorevSat = '';
        foreach ($v['yeni_gorevler'] as $g) {
            $gorevSat .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($g->konu ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . htmlspecialchars($g->musteri ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . htmlspecialchars($g->atanan ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168;text-align:right">' . Carbon::parse($g->created_at)->format('H:i') . '</td>'
                . '</tr>';
        }
        if ($gorevSat === '') $gorevSat = '<tr><td colspan="4" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bugün yeni görev eklenmedi.</td></tr>';

        $tamamSat = '';
        foreach ($v['tamamlanan_gorevler'] as $g) {
            $tamamSat .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px">' . htmlspecialchars($g->konu ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . htmlspecialchars($g->musteri ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . htmlspecialchars($g->atanan ?: '—') . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168;text-align:right">' . Carbon::parse($g->tamamlandi_at)->format('H:i') . '</td>'
                . '</tr>';
        }
        if ($tamamSat === '') $tamamSat = '<tr><td colspan="4" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bugün tamamlanan görev yok.</td></tr>';

        $ticketSat = '';
        foreach ($v['ticket_ozet'] as $t) {
            $ticketSat .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px;font-weight:700">' . htmlspecialchars($t->alici) . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#1E6B2F;text-align:center">' . $t->bugun_cozulen . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168;text-align:center">' . $t->bugun_bekleyen . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#9C2B2B;text-align:center;font-weight:700">' . $t->toplam_bekleyen . '</td>'
                . '</tr>';
        }
        if ($ticketSat === '') $ticketSat = '<tr><td colspan="4" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bugün ticket hareketi yok.</td></tr>';

        $ozetSat = '';
        foreach ($v['personel_ozet'] as $kisi => $adet) {
            $ozetSat .= '<tr>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px;font-weight:700">' . htmlspecialchars($kisi) . '</td>'
                . '<td style="padding:7px 10px;border-bottom:1px solid #f0efe6;font-size:13px;text-align:right">' . $adet . ' işlem</td>'
                . '</tr>';
        }
        if ($ozetSat === '') $ozetSat = '<tr><td colspan="2" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bugün kayıtlı işlem yok.</td></tr>';

        $islemSat = '';
        foreach ($v['personel_islemleri']->take(150) as $i) {
            $islemSat .= '<tr>'
                . '<td style="padding:6px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">' . Carbon::parse($i->tarih)->format('H:i') . '</td>'
                . '<td style="padding:6px 10px;border-bottom:1px solid #f0efe6;font-size:12px;font-weight:700">' . htmlspecialchars($i->kullanici_adi ?: 'Sistem') . '</td>'
                . '<td style="padding:6px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#3a4133">' . htmlspecialchars($i->aciklama ?: '—') . '</td>'
                . '</tr>';
        }
        if ($islemSat === '') $islemSat = '<tr><td colspan="3" style="padding:12px;color:#9aa0a6;font-size:13px;text-align:center">Bugün kayıtlı işlem yok.</td></tr>';
        $eksikNot = $v['personel_islemleri']->count() > 150
            ? '<p style="margin:6px 0 0;color:#9aa0a6;font-size:11px">+ ' . ($v['personel_islemleri']->count() - 150) . ' işlem daha (liste ilk 150 ile sınırlı).</p>'
            : '';

        return self::sablon(
            '📋 Gün Sonu Bildirimi',
            $v['tarih']->format('d.m.Y') . ' — CRM ve personel aktivite özeti',
            '
            ' . $ozetTablo . '

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Yeni Müşteriler</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px;margin-bottom:20px">' . $musteriSat . '</table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Yeni Notlar</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px;margin-bottom:20px">' . $notSat . '</table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Yeni Görevler</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px;margin-bottom:20px">' . $gorevSat . '</table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Tamamlanan Görevler</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px;margin-bottom:20px">' . $tamamSat . '</table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Ticket Özeti (Kişi Bazında)</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px;margin-bottom:6px">
              <tr style="background:#f7f8f3">
                <td style="padding:6px 10px;font-size:11px;font-weight:800;color:#5b6168">KİŞİ</td>
                <td style="padding:6px 10px;font-size:11px;font-weight:800;color:#5b6168;text-align:center">BUGÜN ÇÖZÜLEN</td>
                <td style="padding:6px 10px;font-size:11px;font-weight:800;color:#5b6168;text-align:center">BUGÜN AÇILAN (BEKLEYEN)</td>
                <td style="padding:6px 10px;font-size:11px;font-weight:800;color:#5b6168;text-align:center">TOPLAM BEKLEYEN</td>
              </tr>' . $ticketSat . '</table>
            <p style="margin:0 0 20px;color:#9aa0a6;font-size:11px">"Toplam Bekleyen" tarihe bakmaz — o kişinin şu an cevap bekleyen tüm ticket\'ları.</p>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Personel Özeti</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px;margin-bottom:20px">' . $ozetSat . '</table>

            <div style="font-size:13px;font-weight:800;color:#1a2332;text-transform:uppercase;margin:0 0 8px">Tüm Personel İşlemleri</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px">' . $islemSat . '</table>
            ' . $eksikNot . '
            '
        );
    }

    /** Ortak kurumsal mail iskeleti (FinansRaporu::sablon ile aynı görsel dil) */
    private static function sablon(string $baslik, string $altBaslik, string $icerik): string
    {
        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:Arial,Helvetica,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0e8;padding:32px 12px"><tr><td align="center">
  <table role="presentation" width="700" cellpadding="0" cellspacing="0" style="max-width:700px;width:100%;background:#fff;border-radius:18px;overflow:hidden">
    <tr><td style="background:#1a2332;padding:24px 32px;text-align:center">
      <img src="https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png" width="150" alt="DN Kreatif" style="display:block;margin:0 auto 10px">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Gün Sonu Bildirimi</div>
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
