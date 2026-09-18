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
 * AYLIK BİLDİRİMLİ ÖDEMELER (Muhasebe).
 * Periyodik ödemeler (kira, abonelik, vergi vb.) — periyot 1/3/6/12 ay seçilir.
 * Vadesi yaklaşınca (1 hafta / 3 gün / 1 gün önce) patron + muhasebe rollerine
 * MAİL + uygulama içi bildirim gider. Ödendi işaretlenip vadesi geçince otomatik
 * bir sonraki döneme taşınır. Harcamalar/giderler'den tamamen bağımsızdır.
 */
class AylikOdemeController extends Controller
{
    /** Bildirim/mail gidecek roller: 1=patron, 5=muhasebe */
    private const ALICI_ROLLER = [1, 5];

    /** Vade öncesi hatırlatma eşikleri (gün) — büyükten küçüğe kontrol edilir */
    // 7 = 1 hafta önce, 3 = 3 gün önce, 1 = 1 gün önce

    public function index(Request $request)
    {
        if (!Schema::hasTable('aylik_odemeler')) {
            return view('admin.aylik-odemeler.index', [
                'odemeler' => collect(), 'kategoriler' => collect(),
                'ozet' => ['toplam' => 0, 'bekleyen' => 0, 'odenen' => 0, 'yaklasan' => 0],
                'filtre' => $request->all(), 'tabloYok' => true,
            ]);
        }

        $kategoriler = Schema::hasTable('gider_kategorileri')
            ? DB::table('gider_kategorileri')->where('durum', 1)->orderBy('ad')->get()
            : collect();

        $arama = trim((string) $request->get('q'));
        $durum = $request->get('durum');

        $query = DB::table('aylik_odemeler as o')
            ->leftJoin('gider_kategorileri as k', 'k.id', '=', 'o.kategori_id')
            ->select('o.*', 'k.ad as kategori_adi', 'k.renk as kategori_renk', 'k.ikon as kategori_ikon');

        if ($arama !== '') {
            $query->where(function ($w) use ($arama) {
                $w->where('o.baslik', 'like', "%{$arama}%")->orWhere('o.aciklama', 'like', "%{$arama}%");
            });
        }
        if (in_array($durum, ['bekliyor', 'odendi'], true)) {
            $query->where('o.durum', $durum);
        }

        // Vadeye en yakın önce
        $odemeler = $query->orderByRaw('CASE WHEN o.durum = "bekliyor" THEN 0 ELSE 1 END')
            ->orderBy('o.son_odeme_tarihi')->get();

        foreach ($odemeler as $o) {
            $o->aciliyet = $this->aciliyet($o->son_odeme_tarihi, $o->durum);
        }

        $ozet = [
            'toplam'   => (float) DB::table('aylik_odemeler')->sum('tutar'),
            'bekleyen' => (float) DB::table('aylik_odemeler')->where('durum', 'bekliyor')->sum('tutar'),
            'odenen'   => (float) DB::table('aylik_odemeler')->where('durum', 'odendi')->sum('tutar'),
            'yaklasan' => DB::table('aylik_odemeler')->where('durum', 'bekliyor')
                ->whereNotNull('son_odeme_tarihi')
                ->whereTarihBetween('son_odeme_tarihi', Carbon::today()->toDateString(), Carbon::today()->addDays(7)->toDateString())
                ->count(),
        ];

        return view('admin.aylik-odemeler.index', [
            'odemeler' => $odemeler, 'kategoriler' => $kategoriler, 'ozet' => $ozet,
            'filtre' => ['arama' => $arama, 'durum' => $durum], 'tabloYok' => false,
        ]);
    }

    public function olustur()
    {
        $kategoriler = Schema::hasTable('gider_kategorileri')
            ? DB::table('gider_kategorileri')->where('durum', 1)->orderBy('ad')->get()
            : collect();
        return view('admin.aylik-odemeler.olustur', compact('kategoriler'));
    }

    public function store(Request $request)
    {
        $v = $this->dogrula($request);

        if (!Schema::hasTable('aylik_odemeler')) {
            return back()->withInput()->with('error', 'Tablo bulunamadı. Lütfen SQL kurulumunu çalıştırın.');
        }

        try {
            DB::table('aylik_odemeler')->insert([
                'baslik'           => $v['baslik'],
                'kategori_id'      => $v['kategori_id'] ?? null,
                'kategori_diger'   => $v['kategori_diger'] ?? null,
                'tutar'            => $v['tutar'],
                'para_birimi'      => $v['para_birimi'] ?? 'TRY',
                'periyot_ay'       => (int) $v['periyot_ay'],
                'son_odeme_tarihi' => $v['son_odeme_tarihi'],
                'durum'            => $v['durum'],
                'odeme_yontemi'    => $v['odeme_yontemi'] ?? null,
                'son_odendi_tarihi'=> $v['durum'] === 'odendi' ? now()->toDateString() : null,
                'aciklama'         => $v['aciklama'] ?? null,
                'aktif'            => $request->boolean('aktif', true) ? 1 : 0,
                'olusturan_id'     => session('admin_id'),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Aylık ödeme kaydı hatası', ['err' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Kaydedilemedi: ' . $e->getMessage());
        }

        return redirect()->route('admin.aylik-odemeler.index')->with('success', 'Aylık ödeme eklendi.');
    }

    public function duzenle($id)
    {
        $odeme = DB::table('aylik_odemeler')->where('id', $id)->first();
        if (!$odeme) {
            return redirect()->route('admin.aylik-odemeler.index')->with('error', 'Kayıt bulunamadı.');
        }
        $kategoriler = Schema::hasTable('gider_kategorileri')
            ? DB::table('gider_kategorileri')->where('durum', 1)->orderBy('ad')->get()
            : collect();
        return view('admin.aylik-odemeler.duzenle', compact('odeme', 'kategoriler'));
    }

    public function guncelle(Request $request, $id)
    {
        $v = $this->dogrula($request);

        DB::table('aylik_odemeler')->where('id', $id)->update([
            'baslik'           => $v['baslik'],
            'kategori_id'      => $v['kategori_id'] ?? null,
            'kategori_diger'   => $v['kategori_diger'] ?? null,
            'tutar'            => $v['tutar'],
            'para_birimi'      => $v['para_birimi'] ?? 'TRY',
            'periyot_ay'       => (int) $v['periyot_ay'],
            'son_odeme_tarihi' => $v['son_odeme_tarihi'],
            'durum'            => $v['durum'],
            'odeme_yontemi'    => $v['odeme_yontemi'] ?? null,
            'aciklama'         => $v['aciklama'] ?? null,
            'aktif'            => $request->boolean('aktif', true) ? 1 : 0,
            'updated_at'       => now(),
        ]);

        return redirect()->route('admin.aylik-odemeler.index')->with('success', 'Aylık ödeme güncellendi.');
    }

    /** Ödendi / Bekliyor hızlı geçiş. */
    public function durumDegistir(Request $request, $id)
    {
        $durum = $request->get('durum');
        if (!in_array($durum, ['odendi', 'bekliyor'], true)) {
            return back()->with('error', 'Geçersiz durum.');
        }
        DB::table('aylik_odemeler')->where('id', $id)->update([
            'durum' => $durum,
            'son_odendi_tarihi' => $durum === 'odendi' ? now()->toDateString() : null,
            'updated_at' => now(),
        ]);
        return back()->with('success', $durum === 'odendi' ? 'Ödendi olarak işaretlendi.' : 'Bekliyor olarak işaretlendi.');
    }

    public function sil($id)
    {
        DB::table('aylik_odemeler')->where('id', $id)->delete();
        if (Schema::hasTable('aylik_odeme_bildirim_log')) {
            DB::table('aylik_odeme_bildirim_log')->where('odeme_id', $id)->delete();
        }
        return redirect()->route('admin.aylik-odemeler.index')->with('success', 'Aylık ödeme silindi.');
    }

    // ════════════════════════════════════════════════════════════
    // HATIRLATMA (cron) — URL veya artisan komutu ile tetiklenir
    // ════════════════════════════════════════════════════════════

    /** URL-cron girişi: /admin/cron/aylik-odeme-hatirlatma?key=<CRON_KEY> */
    public function hatirlatmalariGonder(Request $request)
    {
        if (! hash_equals((string) env('CRON_KEY'), (string) $request->get('key'))) {
            abort(403, 'Yetkisiz.');
        }
        return response()->json($this->hatirlatmaCekirdek());
    }

    /**
     * Çekirdek: (1) vadesi geçmiş 'ödendi'leri sonraki döneme taşır,
     * (2) 1 hafta / 3 gün / 1 gün kala + gecikmiş için mail + bildirim üretir.
     * Hem URL-cron hem artisan komutu bunu çağırır.
     */
    public function hatirlatmaCekirdek(bool $kuru = false): array
    {
        if (!Schema::hasTable('aylik_odemeler') || !Schema::hasTable('admin_bildirimler')) {
            return ['ok' => false, 'mesaj' => 'Tablo yok'];
        }

        // KURU ÇALIŞTIRMA ($kuru = true): hiç mail atılmaz, hiçbir kayıt değişmez/eklenmez.
        // Sadece "bugün ne olurdu" listesi üretilir. Gerçek çalıştırmayla AYNI çekirdeği
        // kullanır — ayrı bir kopya mantık olmadığı için sonuç birebir aynıdır.
        $plan = [];

        $bugun = Carbon::today();
        $bugunStr = $bugun->toDateString();
        $logVar = Schema::hasTable('aylik_odeme_bildirim_log');
        $hasYoneticiId = Schema::hasColumn('admin_bildirimler', 'yonetici_id');

        // Alıcılar: patron + muhasebe (id + email)
        $aliciSatirlar = DB::table('yoneticiler')
            ->whereIn('rol', self::ALICI_ROLLER)->where('durum', 1)
            ->get(['id', 'adi', 'email']);
        $aliciIdler  = $aliciSatirlar->pluck('id')->all();
        $aliciMailler = $aliciSatirlar->filter(fn ($y) => filter_var($y->email ?? '', FILTER_VALIDATE_EMAIL))
            ->pluck('email')->unique()->values()->all();

        // (1) Vadesi geçmiş 'ödendi' kayıtları bir sonraki döneme taşı (otomatik yenile)
        $yenilenen = 0;
        $odenmisGecmis = DB::table('aylik_odemeler')
            ->where('durum', 'odendi')->where('aktif', 1)
            ->whereNotNull('son_odeme_tarihi')
            ->whereDate('son_odeme_tarihi', '<', $bugunStr)->get();
        foreach ($odenmisGecmis as $o) {
            $yeni = Carbon::parse($o->son_odeme_tarihi);
            $periyot = max(1, (int) $o->periyot_ay);
            while ($yeni->lt($bugun)) {
                $yeni->addMonthsNoOverflow($periyot);
            }
            if ($kuru) {
                $plan[] = [
                    'tur'    => 'yenileme',
                    'baslik' => $o->baslik,
                    'detay'  => 'Vadesi geçmiş "ödendi" kaydı — yeni vade ' . $yeni->format('d.m.Y') . ' olarak taşınırdı',
                    'mail'   => [],
                ];
                $yenilenen++;
                continue;
            }

            DB::table('aylik_odemeler')->where('id', $o->id)->update([
                'son_odeme_tarihi' => $yeni->toDateString(),
                'durum' => 'bekliyor',
                'updated_at' => now(),
            ]);
            if ($logVar) {
                DB::table('aylik_odeme_bildirim_log')->where('odeme_id', $o->id)->delete();
            }
            $yenilenen++;
        }

        // (2) Bekleyenler için hatırlatma
        $uretilen = 0;
        $odemeler = DB::table('aylik_odemeler')
            ->where('durum', 'bekliyor')->where('aktif', 1)
            ->whereNotNull('son_odeme_tarihi')->get();

        foreach ($odemeler as $o) {
            try {
                $vade = Carbon::parse($o->son_odeme_tarihi)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }
            $gun = $bugun->diffInDays($vade, false); // negatif = gecikmiş

            [$tip, $mesajEk] = $this->esikBelirle($gun);
            if ($tip === null) {
                continue;
            }

            // Tekrar koruması: gecikti her gün, diğerleri tip başına 1 kez
            if ($logVar) {
                $q = DB::table('aylik_odeme_bildirim_log')->where('odeme_id', $o->id)->where('tip', $tip);
                if ($tip === 'gecikti') {
                    $q->where('bildirim_tarihi', $bugunStr);
                }
                if ($q->exists()) {
                    continue;
                }
            }

            $tutar = number_format((float) $o->tutar, 2, ',', '.');
            $baslik = 'Ödeme Hatırlatması';
            $mesaj = $o->baslik . ' (' . $tutar . ' ' . ($o->para_birimi ?: 'TRY') . ') — ' . $mesajEk . '.';

            if ($kuru) {
                $plan[] = [
                    'tur'    => 'hatirlatma',
                    'baslik' => $o->baslik,
                    'detay'  => $tutar . ' ' . ($o->para_birimi ?: 'TRY') . ' — ' . $mesajEk
                                . ' (vade ' . Carbon::parse($o->son_odeme_tarihi)->format('d.m.Y') . ', eşik: ' . $tip . ')',
                    'mail'   => $aliciMailler,
                ];
                $uretilen++;
                continue;
            }

            // ── MUKERRER MAIL KORUMASI ─────────────────────────────
            // ONCE LOG SATIRINI YAZ, SONRA GONDER.
            //
            // Bu hatirlatma IKI ayri yoldan tetiklenebiliyor:
            //   1) zamanlayici  : mail:aylik-odeme-hatirlat (09:30)
            //   2) URL-cron ucu : /cron/aylik-odeme-hatirlatma?key=...
            // Ikisi ayni dakikada calisirsa yukaridaki exists() kontrolu
            // ikisinde de "gonderilmemis" donuyor ve AYNI ANDA IKI MAIL
            // gidiyordu. Yeri once kapinca ikinci calisma 0 alip atlar.
            if ($logVar) {
                $kapildi = DB::table('aylik_odeme_bildirim_log')->insertOrIgnore([
                    'odeme_id'        => $o->id,
                    'tip'             => $tip,
                    'bildirim_tarihi' => $bugunStr,
                    'created_at'      => now(),
                ]);
                if (!$kapildi) {
                    continue;   // baska bir calisma bu bildirimi zaten ustlendi
                }
            }

            // Uygulama içi bildirim (patron + muhasebe)
            if ($hasYoneticiId && !empty($aliciIdler)) {
                foreach ($aliciIdler as $aid) {
                    DB::table('admin_bildirimler')->insert([
                        'yonetici_id' => $aid, 'tip' => 'aylik_odeme_hatirlatma',
                        'baslik' => $baslik, 'mesaj' => $mesaj,
                        'ilgili_id' => $o->id, 'ilgili_tablo' => 'aylik_odemeler',
                        'okundu' => 0, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            } else {
                DB::table('admin_bildirimler')->insert([
                    'tip' => 'aylik_odeme_hatirlatma', 'baslik' => $baslik, 'mesaj' => $mesaj,
                    'ilgili_id' => $o->id, 'ilgili_tablo' => 'aylik_odemeler',
                    'okundu' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // Mail (patron + muhasebe)
            foreach ($aliciMailler as $email) {
                try {
                    EmailNotificationService::send($email, '💳 ' . $baslik . ' — ' . $o->baslik, $this->mailGovdesi($o, $mesajEk, $tutar));
                } catch (\Throwable $e) {
                    Log::warning('Aylık ödeme hatırlatma maili gönderilemedi (' . $email . '): ' . $e->getMessage());
                }
            }

            // NOT: log satiri yukarida, gonderimden ONCE yazildi.
            $uretilen++;
        }

        return [
            'ok'                => true,
            'kuru'              => $kuru,
            'uretilen_bildirim' => $uretilen,
            'yenilenen_donem'   => $yenilenen,
            'tarih'             => $bugunStr,
            'alici_mailler'     => $aliciMailler,
            'plan'              => $plan,
        ];
    }

    /** Kalan güne göre hatırlatma tipi + metin. null = henüz zamanı değil. */
    private function esikBelirle(int $gun): array
    {
        if ($gun < 0)  return ['gecikti', abs($gun) . ' gün gecikti'];
        if ($gun === 0) return ['bugun', 'bugün son ödeme günü'];
        if ($gun <= 1) return ['1gun', '1 gün kaldı'];
        if ($gun <= 3) return ['3gun', $gun . ' gün kaldı'];
        if ($gun <= 7) return ['7gun', $gun . ' gün kaldı'];
        return [null, ''];
    }

    /**
     * Jeton (aciliyet) rengi: ödendi=yeşil; >7 gün=yeşil; 4-7 gün + ≤3 gün=turuncu(sarı);
     * ≤1 gün / bugün / gecikmiş=kırmızı.
     */
    private function aciliyet($tarih, $durum): array
    {
        if ($durum === 'odendi') {
            return ['renk' => 'yesil', 'etiket' => 'Ödendi'];
        }
        if (empty($tarih)) {
            return ['renk' => 'notr', 'etiket' => '—'];
        }
        $gun = Carbon::today()->diffInDays(Carbon::parse($tarih)->startOfDay(), false);
        if ($gun < 0)   return ['renk' => 'kirmizi', 'etiket' => abs($gun) . ' gün gecikti'];
        if ($gun === 0) return ['renk' => 'kirmizi', 'etiket' => 'Bugün son gün'];
        if ($gun <= 1)  return ['renk' => 'kirmizi', 'etiket' => '1 gün kaldı'];
        if ($gun <= 7)  return ['renk' => 'sari', 'etiket' => $gun . ' gün kaldı'];
        return ['renk' => 'yesil', 'etiket' => $gun . ' gün kaldı'];
    }

    /** Ortak doğrulama. */
    private function dogrula(Request $request): array
    {
        return $request->validate([
            'baslik'           => 'required|string|max:255',
            'kategori_id'      => 'nullable|integer',
            'kategori_diger'   => 'nullable|string|max:150',
            'tutar'            => 'required|numeric|min:0',
            'para_birimi'      => 'nullable|string|max:5',
            'periyot_ay'       => 'required|integer|min:1|max:60',
            'son_odeme_tarihi' => 'required|date',
            'durum'            => 'required|in:bekliyor,odendi',
            'odeme_yontemi'    => 'nullable|string|max:50',
            'aciklama'         => 'nullable|string',
        ], [
            'baslik.required'           => 'Başlık zorunludur.',
            'tutar.required'            => 'Tutar zorunludur.',
            'periyot_ay.required'       => 'Periyot (kaç ayda bir) seçiniz.',
            'son_odeme_tarihi.required' => 'Son ödeme tarihi zorunludur.',
        ]);
    }

    /** Basit markalı mail gövdesi. */
    private function mailGovdesi($o, string $mesajEk, string $tutar): string
    {
        $vade = $o->son_odeme_tarihi ? Carbon::parse($o->son_odeme_tarihi)->format('d.m.Y') : '—';
        $baslik = htmlspecialchars($o->baslik ?? '');
        return '<div style="font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#eef0e8;padding:28px 14px">'
            . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(31,36,25,.10)">'
            . '<div style="background:#1a2332;padding:22px 30px;color:#fff"><div style="font-size:13px;color:#c7cbd6;font-weight:600">İş Ortağım · Muhasebe</div>'
            . '<div style="font-size:20px;font-weight:800;margin-top:4px">💳 Ödeme Hatırlatması</div></div>'
            . '<div style="height:4px;background:#b8b62e"></div>'
            . '<div style="padding:26px 30px;color:#2b2b1f">'
            . '<p style="margin:0 0 14px;font-size:15px"><strong>' . $baslik . '</strong> ödemesi için <strong>' . htmlspecialchars($mesajEk) . '</strong>.</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px">'
            . '<tr><td style="padding:8px 0;color:#7a7768">Tutar</td><td style="padding:8px 0;text-align:right;font-weight:800">' . $tutar . ' ' . htmlspecialchars($o->para_birimi ?: 'TRY') . '</td></tr>'
            . '<tr><td style="padding:8px 0;color:#7a7768;border-top:1px solid #f0efe6">Son Ödeme</td><td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #f0efe6">' . $vade . '</td></tr>'
            . '</table>'
            . '<p style="margin:18px 0 0;font-size:12.5px;color:#9aa08e">Bu otomatik bir hatırlatmadır. Panelden "Aylık Bildirimli Ödemeler" sayfasından yönetebilirsiniz.</p>'
            . '</div></div></div>';
    }
}
