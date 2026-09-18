<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * AYLIK BİLDİRİMLİ ALACAKLAR (Muhasebe) — görev #217/6.
 *
 * "Aylık Bildirimli Ödemeler" ekranının ALACAK tarafı: bizim tahsil edeceğimiz
 * periyodik tutarlar (aylık bakım, sosyal medya yönetimi, kira geliri vb.).
 * Periyot 1/3/6/12 ay seçilir. Tahsil tarihi yaklaşınca (1 hafta / 3 gün / 1 gün
 * önce) yönetime mail gider. Tahsil edildi işaretlenip tarihi geçince kayıt
 * otomatik bir sonraki döneme taşınır.
 */
class AylikAlacakController extends Controller
{
    /** Hatırlatma gidecek yöneticiler (kullaniciadi) */
    private const ALICILAR = ['nurselinan', 'dilanatescom', 'NesimiAtes'];

    /* ═══════════════════ LİSTE ═══════════════════ */

    public function index(Request $request)
    {
        if (!Schema::hasTable('aylik_alacaklar')) {
            return view('admin.aylik-alacaklar.index', [
                'alacaklar' => collect(), 'kategoriler' => collect(), 'musteriler' => collect(),
                'ozet' => ['toplam' => 0, 'bekleyen' => 0, 'tahsil' => 0, 'yaklasan' => 0],
                'filtre' => ['arama' => '', 'durum' => null], 'tabloYok' => true,
            ]);
        }

        $kategoriler = Schema::hasTable('gider_kategorileri')
            ? DB::table('gider_kategorileri')->where('durum', 1)->orderBy('ad')->get()
            : collect();

        $arama = trim((string) $request->get('q'));
        $durum = $request->get('durum');

        $query = DB::table('aylik_alacaklar as a')
            ->leftJoin('gider_kategorileri as k', 'k.id', '=', 'a.kategori_id')
            ->leftJoin('crm_customers as c', 'c.id', '=', 'a.musteri_id')
            ->select('a.*', 'k.ad as kategori_adi', 'k.renk as kategori_renk',
                     DB::raw('COALESCE(c.adi, a.musteri_adi) as musteri'));

        if ($arama !== '') {
            $query->where(function ($w) use ($arama) {
                $w->where('a.baslik', 'like', "%{$arama}%")
                  ->orWhere('a.aciklama', 'like', "%{$arama}%")
                  ->orWhere('a.musteri_adi', 'like', "%{$arama}%")
                  ->orWhere('c.adi', 'like', "%{$arama}%");
            });
        }
        if (in_array($durum, ['bekliyor', 'tahsil_edildi'], true)) {
            $query->where('a.durum', $durum);
        }

        // Tahsil tarihi en yakın önce, bekleyenler üstte
        $alacaklar = $query->orderByRaw('CASE WHEN a.durum = "bekliyor" THEN 0 ELSE 1 END')
            ->orderBy('a.son_tahsil_tarihi')->get();

        foreach ($alacaklar as $a) {
            $a->aciliyet = $this->aciliyet($a->son_tahsil_tarihi, $a->durum);
        }

        $ozet = [
            'toplam'   => (float) DB::table('aylik_alacaklar')->sum('tutar'),
            'bekleyen' => (float) DB::table('aylik_alacaklar')->where('durum', 'bekliyor')->sum('tutar'),
            'tahsil'   => (float) DB::table('aylik_alacaklar')->where('durum', 'tahsil_edildi')->sum('tutar'),
            'yaklasan' => DB::table('aylik_alacaklar')->where('durum', 'bekliyor')
                ->whereNotNull('son_tahsil_tarihi')
                ->whereTarihBetween('son_tahsil_tarihi', Carbon::today()->toDateString(), Carbon::today()->addDays(7)->toDateString())
                ->count(),
        ];

        return view('admin.aylik-alacaklar.index', [
            'alacaklar' => $alacaklar, 'kategoriler' => $kategoriler,
            'musteriler' => $this->musteriler(), 'ozet' => $ozet,
            'filtre' => ['arama' => $arama, 'durum' => $durum], 'tabloYok' => false,
        ]);
    }

    /* ═══════════════════ CRUD ═══════════════════ */

    public function olustur()
    {
        return view('admin.aylik-alacaklar.olustur', [
            'kategoriler' => $this->kategoriler(),
            'musteriler'  => $this->musteriler(),
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->dogrula($request);

        if (!Schema::hasTable('aylik_alacaklar')) {
            return back()->withInput()->with('error', 'Tablo bulunamadı. Lütfen migration çalıştırın.');
        }

        try {
            DB::table('aylik_alacaklar')->insert($this->veriHazirla($v, $request) + [
                'son_tahsil_edildi_tarihi' => $v['durum'] === 'tahsil_edildi' ? now()->toDateString() : null,
                'olusturan_id' => session('admin_id'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Aylık alacak kaydı hatası', ['err' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Kaydedilemedi: ' . $e->getMessage());
        }

        return redirect()->route('admin.aylik-alacaklar.index')->with('success', 'Aylık alacak eklendi.');
    }

    public function duzenle($id)
    {
        $alacak = DB::table('aylik_alacaklar')->where('id', $id)->first();
        if (!$alacak) {
            return redirect()->route('admin.aylik-alacaklar.index')->with('error', 'Kayıt bulunamadı.');
        }

        return view('admin.aylik-alacaklar.duzenle', [
            'alacak' => $alacak,
            'kategoriler' => $this->kategoriler(),
            'musteriler'  => $this->musteriler(),
        ]);
    }

    public function guncelle(Request $request, $id)
    {
        $v = $this->dogrula($request);

        DB::table('aylik_alacaklar')->where('id', $id)
            ->update($this->veriHazirla($v, $request) + ['updated_at' => now()]);

        return redirect()->route('admin.aylik-alacaklar.index')->with('success', 'Aylık alacak güncellendi.');
    }

    /** Tahsil edildi / Bekliyor hızlı geçiş */
    public function durumDegistir(Request $request, $id)
    {
        $durum = $request->get('durum');
        if (!in_array($durum, ['tahsil_edildi', 'bekliyor'], true)) {
            return back()->with('error', 'Geçersiz durum.');
        }

        DB::table('aylik_alacaklar')->where('id', $id)->update([
            'durum' => $durum,
            'son_tahsil_edildi_tarihi' => $durum === 'tahsil_edildi' ? now()->toDateString() : null,
            'updated_at' => now(),
        ]);

        return back()->with('success', $durum === 'tahsil_edildi'
            ? 'Tahsil edildi olarak işaretlendi.' : 'Bekliyor olarak işaretlendi.');
    }

    public function sil($id)
    {
        DB::table('aylik_alacaklar')->where('id', $id)->delete();

        return redirect()->route('admin.aylik-alacaklar.index')->with('success', 'Aylık alacak silindi.');
    }

    /* ═══════════════════ HATIRLATMA ═══════════════════ */

    /**
     * (1) Tarihi geçmiş 'tahsil edildi' kayıtları sonraki döneme taşır,
     * (2) 1 hafta / 3 gün / 1 gün kala + gecikmiş alacaklar için mail gönderir.
     */
    public function hatirlatmaCekirdek(bool $kuru = false): array
    {
        if (!Schema::hasTable('aylik_alacaklar')) {
            return ['durum' => 'tablo_yok'];
        }

        $bugun = Carbon::today();
        $tasinan = 0;

        /* 1) Tahsil edilmiş ve tarihi geçmiş kayıtları sonraki döneme taşı */
        $gecmisler = DB::table('aylik_alacaklar')
            ->where('durum', 'tahsil_edildi')->where('aktif', 1)
            ->whereNotNull('son_tahsil_tarihi')
            ->whereDate('son_tahsil_tarihi', '<', $bugun)
            ->get();

        foreach ($gecmisler as $a) {
            $periyot = max(1, (int) ($a->periyot_ay ?: 1));
            $yeni = Carbon::parse($a->son_tahsil_tarihi)->addMonthsNoOverflow($periyot);
            while ($yeni->lt($bugun)) {
                $yeni->addMonthsNoOverflow($periyot);
            }

            if (!$kuru) {
                DB::table('aylik_alacaklar')->where('id', $a->id)->update([
                    'son_tahsil_tarihi' => $yeni->toDateString(),
                    'durum'             => 'bekliyor',
                    'updated_at'        => now(),
                ]);
            }
            $tasinan++;
        }

        /* 2) Yaklaşan ve gecikmiş alacaklar */
        $liste = DB::table('aylik_alacaklar as a')
            ->leftJoin('crm_customers as c', 'c.id', '=', 'a.musteri_id')
            ->where('a.durum', 'bekliyor')->where('a.aktif', 1)
            ->whereNotNull('a.son_tahsil_tarihi')
            ->whereDate('a.son_tahsil_tarihi', '<=', $bugun->copy()->addDays(7))
            ->select('a.*', DB::raw('COALESCE(c.adi, a.musteri_adi) as musteri'))
            ->orderBy('a.son_tahsil_tarihi')->get();

        if ($liste->isEmpty()) {
            return ['durum' => 'ok', 'tasinan' => $tasinan, 'bildirilen' => 0];
        }

        $alicilar = $this->alicilar();
        $bildirilen = 0;

        if (!$kuru && !empty($alicilar)) {
            $govde = $this->mailGovdesi($liste, $bugun);
            $konu  = '💰 Yaklaşan Alacaklar — ' . $liste->count() . ' kayıt';

            foreach ($alicilar as $mail) {
                try {
                    EmailNotificationService::send($mail, $konu, $govde);
                    $bildirilen++;
                } catch (\Throwable $e) {
                    Log::warning('Alacak hatırlatma maili gönderilemedi', ['mail' => $mail, 'hata' => $e->getMessage()]);
                }
            }
        }

        Log::info('Aylık alacak hatırlatması', [
            'tasinan' => $tasinan, 'kayit' => $liste->count(), 'bildirilen' => $bildirilen,
        ]);

        return ['durum' => 'ok', 'tasinan' => $tasinan, 'kayit' => $liste->count(), 'bildirilen' => $bildirilen];
    }

    /* ═══════════════════ yardımcılar ═══════════════════ */

    private function kategoriler()
    {
        return Schema::hasTable('gider_kategorileri')
            ? DB::table('gider_kategorileri')->where('durum', 1)->orderBy('ad')->get()
            : collect();
    }

    private function musteriler()
    {
        try {
            return DB::table('crm_customers')->orderBy('adi')->limit(1000)->get(['id', 'adi']);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function alicilar(): array
    {
        try {
            return DB::table('yoneticiler')
                ->whereIn('kullaniciadi', self::ALICILAR)->where('durum', 1)
                ->whereNotNull('email')->where('email', '!=', '')
                ->pluck('email')->unique()->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function dogrula(Request $request): array
    {
        return $request->validate([
            'baslik'            => 'required|string|max:191',
            'musteri_id'        => 'nullable|integer|exists:crm_customers,id',
            'musteri_adi'       => 'nullable|string|max:191',
            'kategori_id'       => 'nullable|integer',
            'kategori_diger'    => 'nullable|string|max:150',
            'tutar'             => 'required|numeric|min:0',
            'para_birimi'       => 'nullable|string|max:5',
            'periyot_ay'        => 'required|in:1,3,6,12',
            'son_tahsil_tarihi' => 'required|date',
            'durum'             => 'required|in:bekliyor,tahsil_edildi',
            'tahsil_yontemi'    => 'nullable|string|max:50',
            'aciklama'          => 'nullable|string',
        ], [], [
            'son_tahsil_tarihi' => 'son tahsil tarihi',
            'periyot_ay'        => 'periyot',
        ]);
    }

    private function veriHazirla(array $v, Request $request): array
    {
        return [
            'baslik'            => $v['baslik'],
            'musteri_id'        => $v['musteri_id'] ?? null,
            'musteri_adi'       => $v['musteri_adi'] ?? null,
            'kategori_id'       => $v['kategori_id'] ?? null,
            'kategori_diger'    => $v['kategori_diger'] ?? null,
            'tutar'             => $v['tutar'],
            'para_birimi'       => $v['para_birimi'] ?? 'TL',
            'periyot_ay'        => (int) $v['periyot_ay'],
            'son_tahsil_tarihi' => $v['son_tahsil_tarihi'],
            'durum'             => $v['durum'],
            'tahsil_yontemi'    => $v['tahsil_yontemi'] ?? null,
            'aciklama'          => $v['aciklama'] ?? null,
            'aktif'             => $request->boolean('aktif', true) ? 1 : 0,
        ];
    }

    /**
     * Tahsil tarihine göre aciliyet etiketi.
     * renk/zemin = e-posta gövdesi için inline hex (mailde CSS değişkeni çalışmaz),
     * sinif      = panel arayüzünde .badge-* sistem sınıfı (light/dark uyumlu).
     */
    private function aciliyet(?string $tarih, ?string $durum): array
    {
        if ($durum === 'tahsil_edildi') {
            return ['renk' => '#1E6B2F', 'zemin' => '#E2EFDA', 'sinif' => 'badge-success', 'metin' => '✓ Tahsil edildi', 'gun' => null];
        }
        if (!$tarih) {
            return ['renk' => '#666666', 'zemin' => '#f3f4f6', 'sinif' => 'badge-neutral', 'metin' => 'Tarih yok', 'gun' => null];
        }

        $gun = (int) Carbon::today()->diffInDays(Carbon::parse($tarih)->startOfDay(), false);

        if ($gun < 0)  return ['renk' => '#9C2B2B', 'zemin' => '#F8D7DA', 'sinif' => 'badge-danger',  'metin' => abs($gun) . ' gün gecikti', 'gun' => $gun];
        if ($gun === 0) return ['renk' => '#9C2B2B', 'zemin' => '#F8D7DA', 'sinif' => 'badge-danger',  'metin' => 'Bugün', 'gun' => 0];
        if ($gun <= 3)  return ['renk' => '#8A6200', 'zemin' => '#FFF2CC', 'sinif' => 'badge-warning', 'metin' => $gun . ' gün kaldı', 'gun' => $gun];
        if ($gun <= 7)  return ['renk' => '#1F4E78', 'zemin' => '#D9E1F2', 'sinif' => 'badge-info',    'metin' => $gun . ' gün kaldı', 'gun' => $gun];

        return ['renk' => '#666666', 'zemin' => '#f3f4f6', 'sinif' => 'badge-neutral', 'metin' => $gun . ' gün', 'gun' => $gun];
    }

    private function mailGovdesi($liste, Carbon $bugun): string
    {
        $satirlar = '';
        $toplam = 0;

        foreach ($liste as $a) {
            $ac = $this->aciliyet($a->son_tahsil_tarihi, $a->durum);
            $toplam += (float) $a->tutar;

            $satirlar .= '<tr>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #f0efe6;font-size:13px">'
                . '<strong>' . htmlspecialchars($a->baslik) . '</strong>'
                . ($a->musteri ? '<div style="font-size:11.5px;color:#9aa0a6">' . htmlspecialchars($a->musteri) . '</div>' : '')
                . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #f0efe6;font-size:12px;color:#5b6168">'
                . Carbon::parse($a->son_tahsil_tarihi)->format('d.m.Y') . '</td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #f0efe6;font-size:12px">'
                . '<span style="color:' . $ac['renk'] . ';background:' . $ac['zemin'] . ';padding:2px 8px;border-radius:99px;font-weight:700">'
                . $ac['metin'] . '</span></td>'
                . '<td style="padding:8px 10px;border-bottom:1px solid #f0efe6;font-size:13px;text-align:right;font-weight:700">'
                . number_format((float) $a->tutar, 2, ',', '.') . ' ' . htmlspecialchars($a->para_birimi ?: 'TL') . '</td>'
                . '</tr>';
        }

        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:Arial,Helvetica,sans-serif;color:#1f2419">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0e8;padding:32px 12px"><tr><td align="center">
  <table role="presentation" width="680" cellpadding="0" cellspacing="0" style="max-width:680px;width:100%;background:#fff;border-radius:18px;overflow:hidden">
    <tr><td style="background:#1a2332;padding:24px 32px;text-align:center">
      <img src="https://crm.ornek.com/tema/uploads/logo/dn-kreatif-logo.png" width="150" alt="DN Kreatif" style="display:block;margin:0 auto 10px">
      <div style="color:#c7cbd6;font-size:13px;font-weight:600">Muhasebe · Aylık Alacaklar</div>
    </td></tr>
    <tr><td style="height:5px;background:#b8b62e;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td style="padding:28px 32px 14px">
      <h2 style="margin:0 0 6px;font-size:21px;color:#1a1a0e">💰 Yaklaşan alacaklar</h2>
      <p style="margin:0 0 20px;color:#6b6f63;font-size:13.5px">'
      . $bugun->format('d.m.Y') . ' itibarıyla tahsil tarihi yaklaşan veya gecikmiş '
      . $liste->count() . ' alacak kaydı — toplam <strong>' . number_format($toplam, 2, ',', '.') . ' TL</strong>.</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0e6;border-radius:8px">' . $satirlar . '</table>
    </td></tr>
    <tr><td style="background:#1a2332;padding:18px 32px;text-align:center">
      <p style="margin:0;color:#8b93a5;font-size:11px">&copy; ' . date('Y') . ' DN Kreatif &middot; İş Ortağım &middot; Otomatik hatırlatma</p>
    </td></tr>
  </table>
</td></tr></table></body></html>';
    }
}
