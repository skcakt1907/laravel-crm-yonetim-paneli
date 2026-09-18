<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Spreadsheet;

/**
 * MADDE 10: Harcama / Gider Takibi.
 * Şirketin kendi giderleri (ofis, yazılım, maaş, vergi vb.).
 * Liste son ödemeye göre sıralı, renk kodlu (kırmızı/sarı/yeşil), filtreli, özet kartlı.
 */
class GiderController extends Controller
{
    /**
     * Gider listesi + özet + filtre.
     */
    public function index(Request $request)
    {
        if (!Schema::hasTable('giderler')) {
            return view('admin.giderler.index', [
                'giderler' => collect(),
                'kategoriler' => collect(),
                'ozet' => $this->bosOzet(),
                'kategoriToplam' => collect(),
                'filtre' => $request->all(),
                'tabloYok' => true,
            ]);
        }

        $kategoriler = Schema::hasTable('gider_kategorileri')
            ? DB::table('gider_kategorileri')->where('durum', 1)->orderBy('ad')->get()
            : collect();

        // --- Filtreler ---
        $arama   = trim((string) $request->get('q'));
        $katId   = $request->get('kategori');
        $durum   = $request->get('durum');
        $basTar  = $request->get('bas');
        $bitTar  = $request->get('bit');

        $query = DB::table('giderler as g')
            ->leftJoin('gider_kategorileri as k', 'k.id', '=', 'g.kategori_id')
            ->select('g.*', 'k.ad as kategori_adi', 'k.renk as kategori_renk', 'k.ikon as kategori_ikon');

        if ($arama !== '') {
            $query->where(function ($w) use ($arama) {
                $w->where('g.baslik', 'like', "%{$arama}%")
                  ->orWhere('g.aciklama', 'like', "%{$arama}%");
            });
        }
        if ($katId !== null && $katId !== '') {
            $query->where('g.kategori_id', (int) $katId);
        }
        if ($durum !== null && $durum !== '' && in_array($durum, ['odendi', 'bekliyor', 'gecikti'])) {
            $query->where('g.durum', $durum);
        }
        if ($basTar) {
            $query->whereDate('g.gider_tarihi', '>=', $basTar);
        }
        if ($bitTar) {
            $query->whereDate('g.gider_tarihi', '<=', $bitTar);
        }

        // Son ödemesi yakın olan EN ÜSTTE: önce bekleyen/gecikmiş + son_odeme_tarihi artan.
        // Ödenmişler en sona. NULL son_odeme en sona.
        $giderler = $query
            ->orderByRaw("CASE WHEN g.durum = 'odendi' THEN 1 ELSE 0 END ASC")
            ->orderByRaw("g.son_odeme_tarihi IS NULL ASC")
            ->orderBy('g.son_odeme_tarihi', 'asc')
            ->orderByDesc('g.id')
            ->paginate(25)
            ->withQueryString();

        // Her gidere renk/aciliyet bilgisi ekle (view'de kullanılacak)
        $bugun = Carbon::today();
        $giderler->getCollection()->transform(function ($g) use ($bugun) {
            $g->aciliyet = $this->aciliyetHesapla($g, $bugun);
            return $g;
        });

        // --- Özet (filtreden bağımsız, genel durum) ---
        $ozet = $this->ozetHesapla();

        // --- Kategori bazlı toplam ("neye ne kadar harcadın") ---
        $kategoriToplam = DB::table('giderler as g')
            ->leftJoin('gider_kategorileri as k', 'k.id', '=', 'g.kategori_id')
            ->select(
                'k.id', 'k.ad', 'k.renk', 'k.ikon',
                DB::raw('SUM(g.tutar) as toplam'),
                DB::raw('COUNT(g.id) as adet')
            )
            ->groupBy('k.id', 'k.ad', 'k.renk', 'k.ikon')
            ->orderByDesc('toplam')
            ->get();

        return view('admin.giderler.index', [
            'giderler' => $giderler,
            'kategoriler' => $kategoriler,
            'ozet' => $ozet,
            'kategoriToplam' => $kategoriToplam,
            'filtre' => compact('arama', 'katId', 'durum', 'basTar', 'bitTar'),
        ]);
    }

    /**
     * Bir giderin son ödemeye göre aciliyet/renk durumu.
     * Döner: ['renk' => 'kirmizi|sari|yesil|nötr', 'etiket' => '...', 'gun' => int|null]
     */
    private function aciliyetHesapla($g, Carbon $bugun): array
    {
        // Ödenmişse yeşil/nötr
        if (($g->durum ?? '') === 'odendi') {
            return ['renk' => 'yesil', 'etiket' => 'Ödendi', 'gun' => null];
        }

        if (empty($g->son_odeme_tarihi)) {
            return ['renk' => 'notr', 'etiket' => 'Tarihsiz', 'gun' => null];
        }

        try {
            $sonOdeme = Carbon::parse($g->son_odeme_tarihi)->startOfDay();
        } catch (\Throwable $e) {
            return ['renk' => 'notr', 'etiket' => '—', 'gun' => null];
        }

        $gun = $bugun->diffInDays($sonOdeme, false); // negatif = geçmiş

        // Renk eşikleri:
        //   geçti        -> kırmızı (uyarı)
        //   bugün         -> kırmızı
        //   1-3 gün       -> kırmızı
        //   4-7 gün       -> sarı
        //   7 günden fazla-> yeşil
        if ($gun < 0) {
            return ['renk' => 'kirmizi', 'etiket' => abs($gun) . ' gün gecikti', 'gun' => $gun];
        } elseif ($gun === 0) {
            return ['renk' => 'kirmizi', 'etiket' => 'Bugün son gün', 'gun' => 0];
        } elseif ($gun <= 3) {
            return ['renk' => 'kirmizi', 'etiket' => $gun . ' gün kaldı', 'gun' => $gun];
        } elseif ($gun <= 7) {
            return ['renk' => 'sari', 'etiket' => $gun . ' gün kaldı', 'gun' => $gun];
        }
        return ['renk' => 'yesil', 'etiket' => $gun . ' gün kaldı', 'gun' => $gun];
    }

    /**
     * Özet kartları için toplamlar.
     */
    private function ozetHesapla(): array
    {
        $bugun = Carbon::today();
        $ayBas = $bugun->copy()->startOfMonth()->toDateString();
        $ayBit = $bugun->copy()->endOfMonth()->toDateString();

        $toplam = (float) DB::table('giderler')->sum('tutar');

        $buAy = (float) DB::table('giderler')
            ->whereTarihBetween('gider_tarihi', $ayBas, $ayBit)
            ->sum('tutar');

        $bekleyen = (float) DB::table('giderler')
            ->where('durum', 'bekliyor')
            ->sum('tutar');

        // Gecikmiş: durum gecikti VEYA (bekliyor + son_odeme < bugün)
        $gecikmis = (float) DB::table('giderler')
            ->where(function ($w) use ($bugun) {
                $w->where('durum', 'gecikti')
                  ->orWhere(function ($q) use ($bugun) {
                      $q->where('durum', 'bekliyor')
                        ->whereNotNull('son_odeme_tarihi')
                        ->whereDate('son_odeme_tarihi', '<', $bugun->toDateString());
                  });
            })
            ->sum('tutar');

        return [
            'toplam' => $toplam,
            'buAy' => $buAy,
            'bekleyen' => $bekleyen,
            'gecikmis' => $gecikmis,
        ];
    }

    private function bosOzet(): array
    {
        return ['toplam' => 0, 'buAy' => 0, 'bekleyen' => 0, 'gecikmis' => 0];
    }

    /**
     * Yeni gider ekleme formu.
     */
    public function olustur()
    {
        $kategoriler = Schema::hasTable('gider_kategorileri')
            ? DB::table('gider_kategorileri')->where('durum', 1)->orderBy('ad')->get()
            : collect();

        return view('admin.giderler.olustur', compact('kategoriler'));
    }

    /**
     * Gideri kaydet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'kategori_id' => 'nullable|integer',
            'tutar' => 'required|numeric|min:0',
            'para_birimi' => 'nullable|string|max:5',
            'gider_tarihi' => 'nullable|date',
            'son_odeme_tarihi' => 'nullable|date',
            'durum' => 'required|in:odendi,bekliyor,gecikti',
            'odeme_yontemi' => 'nullable|string|max:50',
            'tekrar' => 'nullable|in:tek,aylik,yillik',
            'tekrar_eden' => 'nullable|boolean',
            'tekrar_ay' => 'nullable|integer|min:1|max:60',
            'aciklama' => 'nullable|string',
        ], [
            'baslik.required' => 'Başlık zorunludur.',
            'tutar.required' => 'Tutar zorunludur.',
            'tutar.numeric' => 'Tutar sayısal olmalıdır.',
            'durum.required' => 'Durum seçiniz.',
        ]);

        if (!Schema::hasTable('giderler')) {
            return back()->withInput()->with('error', 'Gider tablosu bulunamadı. Lütfen SQL kurulumunu çalıştırın.');
        }

        try {
            DB::table('giderler')->insert([
                'baslik' => $validated['baslik'],
                'kategori_id' => $validated['kategori_id'] ?? null,
                'tutar' => $validated['tutar'],
                'para_birimi' => $validated['para_birimi'] ?? 'TRY',
                'gider_tarihi' => $validated['gider_tarihi'] ?? now()->toDateString(),
                'son_odeme_tarihi' => $validated['son_odeme_tarihi'] ?? null,
                'durum' => $validated['durum'],
                'odeme_yontemi' => $validated['odeme_yontemi'] ?? null,
                'tekrar' => $validated['tekrar'] ?? 'tek',
                'tekrar_eden' => $request->boolean('tekrar_eden') ? 1 : 0,
                'tekrar_ay' => $request->boolean('tekrar_eden') ? (int) ($validated['tekrar_ay'] ?? 1) : 1,
                'sonraki_uretim_tarihi' => $request->boolean('tekrar_eden')
                    ? $this->sonrakiTarihHesapla($validated['son_odeme_tarihi'] ?? null, (int) ($validated['tekrar_ay'] ?? 1))
                    : null,
                'tekrar_aktif' => $request->boolean('tekrar_eden') ? 1 : 0,
                'aciklama' => $validated['aciklama'] ?? null,
                'olusturan_id' => session('admin_id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gider kaydı hatası', ['err' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Gider kaydedilemedi: ' . $e->getMessage());
        }

        return redirect()->route('admin.giderler.index')->with('success', 'Gider başarıyla eklendi.');
    }

    /**
     * Gider düzenleme formu.
     */
    public function duzenle($id)
    {
        $gider = DB::table('giderler')->where('id', $id)->first();
        if (!$gider) {
            return redirect()->route('admin.giderler.index')->with('error', 'Gider bulunamadı.');
        }
        $kategoriler = Schema::hasTable('gider_kategorileri')
            ? DB::table('gider_kategorileri')->where('durum', 1)->orderBy('ad')->get()
            : collect();

        return view('admin.giderler.duzenle', compact('gider', 'kategoriler'));
    }

    /**
     * Gideri güncelle.
     */
    public function guncelle(Request $request, $id)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'kategori_id' => 'nullable|integer',
            'tutar' => 'required|numeric|min:0',
            'para_birimi' => 'nullable|string|max:5',
            'gider_tarihi' => 'nullable|date',
            'son_odeme_tarihi' => 'nullable|date',
            'durum' => 'required|in:odendi,bekliyor,gecikti',
            'odeme_yontemi' => 'nullable|string|max:50',
            'tekrar' => 'nullable|in:tek,aylik,yillik',
            'tekrar_eden' => 'nullable|boolean',
            'tekrar_ay' => 'nullable|integer|min:1|max:60',
            'aciklama' => 'nullable|string',
        ]);

        DB::table('giderler')->where('id', $id)->update([
            'baslik' => $validated['baslik'],
            'kategori_id' => $validated['kategori_id'] ?? null,
            'tutar' => $validated['tutar'],
            'para_birimi' => $validated['para_birimi'] ?? 'TRY',
            'gider_tarihi' => $validated['gider_tarihi'] ?? null,
            'son_odeme_tarihi' => $validated['son_odeme_tarihi'] ?? null,
            'durum' => $validated['durum'],
            'odeme_yontemi' => $validated['odeme_yontemi'] ?? null,
            'tekrar' => $validated['tekrar'] ?? 'tek',
            'tekrar_eden' => $request->boolean('tekrar_eden') ? 1 : 0,
            'tekrar_ay' => $request->boolean('tekrar_eden') ? (int) ($validated['tekrar_ay'] ?? 1) : 1,
            'sonraki_uretim_tarihi' => $request->boolean('tekrar_eden')
                ? $this->sonrakiTarihHesapla($validated['son_odeme_tarihi'] ?? null, (int) ($validated['tekrar_ay'] ?? 1))
                : null,
            'tekrar_aktif' => $request->boolean('tekrar_eden') ? 1 : 0,
            'aciklama' => $validated['aciklama'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.giderler.index')->with('success', 'Gider güncellendi.');
    }

    /**
     * Hızlı durum değiştir (ödendi işaretle vb.).
     */
    public function durumDegistir(Request $request, $id)
    {
        $durum = $request->get('durum');
        if (!in_array($durum, ['odendi', 'bekliyor', 'gecikti'])) {
            return back()->with('error', 'Geçersiz durum.');
        }
        DB::table('giderler')->where('id', $id)->update([
            'durum' => $durum,
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Durum güncellendi.');
    }

    /**
     * Gideri sil.
     */
    public function sil($id)
    {
        if (!Schema::hasTable('giderler')) {
            return back();
        }
        DB::table('giderler')->where('id', $id)->delete();
        return redirect()->route('admin.giderler.index')->with('success', 'Gider silindi.');
    }

    /**
     * CRON: Vadesi yaklaşan / geçen giderler için uygulama-içi hatırlatma üretir.
     * Günde 1 kez çağrılmalı (cPanel cron job).
     * Kurallar:
     *   - 7 gün kala  -> 1 kez
     *   - 3 gün kala  -> 1 kez
     *   - günü geldi  -> 1 kez
     *   - geçti       -> ödenene kadar HER GÜN
     * Aynı bildirim tekrar gitmesin diye gider_bildirim_log kullanılır.
     *
     * Güvenlik: ?key=<CRON_KEY> ile çağrılır (cron URL).
     */
    public function hatirlatmalariGonder(Request $request)
    {
        // Basit anahtar koruması (cron URL'i tahmin edilmesin)
        if (! hash_equals((string) env('CRON_KEY'), (string) $request->get('key'))) {
            abort(403, 'Yetkisiz.');
        }

        if (!Schema::hasTable('giderler') || !Schema::hasTable('admin_bildirimler')) {
            return response()->json(['ok' => false, 'mesaj' => 'Tablo yok']);
        }

        $logVar = Schema::hasTable('gider_bildirim_log');
        $bugun = Carbon::today();
        $bugunStr = $bugun->toDateString();
        $uretilen = 0;

        // Ödenmemiş + son ödeme tarihi olan giderler
        $giderler = DB::table('giderler')
            ->whereIn('durum', ['bekliyor', 'gecikti'])
            ->whereNotNull('son_odeme_tarihi')
            ->get();

        $hasYoneticiId = Schema::hasColumn('admin_bildirimler', 'yonetici_id');

        // Hatırlatmaları kimlere gönderelim? Patron + Muhasebe (onay/finans yetkisi)
        $alicilar = DB::table('yoneticiler')
            ->whereIn('rol', [1, 5])
            ->where('durum', 1)
            ->pluck('id')
            ->toArray();

        foreach ($giderler as $g) {
            try {
                $sonOdeme = Carbon::parse($g->son_odeme_tarihi)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }
            $gun = $bugun->diffInDays($sonOdeme, false); // negatif = geçmiş

            // Hangi tip bildirim?
            $tip = null;
            $mesajEk = '';
            if ($gun < 0) {
                $tip = 'gecikti';
                $mesajEk = abs($gun) . ' gün gecikti';
            } elseif ($gun === 0) {
                $tip = 'bugun';
                $mesajEk = 'bugün son ödeme günü';
            } elseif ($gun <= 3) {
                $tip = '3gun';
                $mesajEk = $gun . ' gün kaldı';
            } elseif ($gun <= 7) {
                $tip = '7gun';
                $mesajEk = $gun . ' gün kaldı';
            }

            if ($tip === null) {
                continue; // 7 günden uzak, henüz hatırlatma yok
            }

            // Tekrar kontrolü:
            //  - gecikti: HER GÜN (gider_id+gecikti+bugün benzersizliği)
            //  - diğerleri: tip başına bir kez (herhangi bir tarihte gönderildiyse tekrar etme)
            if ($logVar) {
                if ($tip === 'gecikti') {
                    $zatenVar = DB::table('gider_bildirim_log')
                        ->where('gider_id', $g->id)->where('tip', 'gecikti')
                        ->where('bildirim_tarihi', $bugunStr)->exists();
                } else {
                    $zatenVar = DB::table('gider_bildirim_log')
                        ->where('gider_id', $g->id)->where('tip', $tip)->exists();
                }
                if ($zatenVar) {
                    continue;
                }
            }

            $tutar = number_format((float) $g->tutar, 2, ',', '.');
            $baslik = 'Gider Hatırlatması';
            $mesaj = $g->baslik . ' (' . $tutar . ' TL) — ' . $mesajEk . '.';

            // Alıcılara bildirim
            if ($hasYoneticiId && !empty($alicilar)) {
                foreach ($alicilar as $aid) {
                    DB::table('admin_bildirimler')->insert([
                        'yonetici_id' => $aid,
                        'tip' => 'gider_hatirlatma',
                        'baslik' => $baslik,
                        'mesaj' => $mesaj,
                        'ilgili_id' => $g->id,
                        'ilgili_tablo' => 'giderler',
                        'okundu' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // yonetici_id kolonu yoksa tek genel bildirim
                DB::table('admin_bildirimler')->insert([
                    'tip' => 'gider_hatirlatma',
                    'baslik' => $baslik,
                    'mesaj' => $mesaj,
                    'ilgili_id' => $g->id,
                    'ilgili_tablo' => 'giderler',
                    'okundu' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Log'a yaz
            if ($logVar) {
                DB::table('gider_bildirim_log')->insert([
                    'gider_id' => $g->id,
                    'tip' => $tip,
                    'bildirim_tarihi' => $bugunStr,
                    'created_at' => now(),
                ]);
            }

            // Vadesi geçmiş ama durumu hâlâ 'bekliyor' ise 'gecikti'ye çek
            if ($gun < 0 && $g->durum === 'bekliyor') {
                DB::table('giderler')->where('id', $g->id)->update(['durum' => 'gecikti', 'updated_at' => now()]);
            }

            $uretilen++;
        }

        // ── TEKRAR EDEN GİDERLERİ OTOMATİK ÜRET ──
        // sonraki_uretim_tarihi'si bugün veya geçmiş olan aktif tekrarlı giderler için
        // bir sonraki dönemin gider kaydını oluştur.
        $uretilenTekrar = $this->tekrarEdenleriUret($bugun);

        return response()->json([
            'ok' => true,
            'uretilen_bildirim' => $uretilen,
            'uretilen_tekrar_gider' => $uretilenTekrar,
            'tarih' => $bugunStr,
        ]);
    }

    /**
     * Tekrar eden (periyodik) giderleri otomatik üretir.
     * Cron tarafından çağrılır. sonraki_uretim_tarihi gelmiş/geçmiş olanlar için
     * yeni dönemin gider kaydını açar ve şablonun sonraki tarihini ileri taşır.
     * Güvenlik: bir şablon için aynı dönemde birden fazla üretim yapılmaz.
     */
    private function tekrarEdenleriUret(Carbon $bugun): int
    {
        if (!Schema::hasColumn('giderler', 'tekrar_eden')) {
            return 0;
        }

        $uretilen = 0;

        // Aktif, tekrar eden, üretim tarihi gelmiş şablonlar
        $sablonlar = DB::table('giderler')
            ->where('tekrar_eden', 1)
            ->where('tekrar_aktif', 1)
            ->whereNotNull('sonraki_uretim_tarihi')
            ->whereDate('sonraki_uretim_tarihi', '<=', $bugun->toDateString())
            ->get();

        foreach ($sablonlar as $s) {
            try {
                $tekrarAy = max(1, (int) ($s->tekrar_ay ?? 1));
                $yeniSonOdeme = Carbon::parse($s->sonraki_uretim_tarihi);

                // Aynı şablondan bu son ödeme tarihi için zaten üretilmiş mi? (çift üretim koruması)
                $zatenUretildi = DB::table('giderler')
                    ->where('ana_gider_id', $s->id)
                    ->whereDate('son_odeme_tarihi', $yeniSonOdeme->toDateString())
                    ->exists();

                if (!$zatenUretildi) {
                    DB::table('giderler')->insert([
                        'baslik' => $s->baslik,
                        'kategori_id' => $s->kategori_id,
                        'tutar' => $s->tutar,
                        'para_birimi' => $s->para_birimi ?? 'TRY',
                        'gider_tarihi' => $bugun->toDateString(),
                        'son_odeme_tarihi' => $yeniSonOdeme->toDateString(),
                        'durum' => 'bekliyor',
                        'odeme_yontemi' => $s->odeme_yontemi,
                        'tekrar' => $s->tekrar ?? 'tek',
                        'tekrar_eden' => 0,           // üretilen kopya kendisi tekrar etmez
                        'tekrar_ay' => $tekrarAy,
                        'ana_gider_id' => $s->id,     // hangi şablondan geldiği
                        'sonraki_uretim_tarihi' => null,
                        'tekrar_aktif' => 0,
                        'aciklama' => $s->aciklama,
                        'olusturan_id' => $s->olusturan_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $uretilen++;
                }

                // Şablonun sonraki üretim tarihini ileri taşı (kaç ayda bir ise)
                $yeniTarih = $yeniSonOdeme->copy()->addMonths($tekrarAy);
                DB::table('giderler')->where('id', $s->id)->update([
                    'sonraki_uretim_tarihi' => $yeniTarih->toDateString(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Tekrar eden gider üretim hatası', ['gider_id' => $s->id, 'err' => $e->getMessage()]);
                continue;
            }
        }

        return $uretilen;
    }

    /**
     * Son ödeme tarihine tekrar_ay ekleyerek bir sonraki üretim tarihini hesaplar.
     * Son ödeme tarihi yoksa bugünden itibaren hesaplar.
     */
    private function sonrakiTarihHesapla($sonOdemeTarihi, int $tekrarAy): ?string
    {
        $tekrarAy = max(1, $tekrarAy);
        try {
            $baz = $sonOdemeTarihi ? Carbon::parse($sonOdemeTarihi) : Carbon::today();
            return $baz->copy()->addMonths($tekrarAy)->toDateString();
        } catch (\Throwable $e) {
            return Carbon::today()->addMonths($tekrarAy)->toDateString();
        }
    }

    /**
     * Giderleri Excel'e aktar. Sistemde PhpSpreadsheet varsa .xlsx, yoksa CSV.
     * Mevcut filtreleri uygular.
     */
    public function excelAktar(Request $request)
    {
        if (!Schema::hasTable('giderler')) {
            return back()->with('error', 'Gider tablosu yok.');
        }

        // Filtreleri uygula (index ile aynı mantık)
        $query = DB::table('giderler as g')
            ->leftJoin('gider_kategorileri as k', 'k.id', '=', 'g.kategori_id')
            ->select('g.baslik', 'k.ad as kategori', 'g.tutar', 'g.para_birimi',
                     'g.gider_tarihi', 'g.son_odeme_tarihi', 'g.durum', 'g.odeme_yontemi', 'g.tekrar', 'g.aciklama');

        if ($request->filled('q')) {
            $ara = trim($request->get('q'));
            $query->where(function ($w) use ($ara) {
                $w->where('g.baslik', 'like', "%{$ara}%")->orWhere('g.aciklama', 'like', "%{$ara}%");
            });
        }
        if ($request->filled('kategori')) $query->where('g.kategori_id', (int) $request->get('kategori'));
        if ($request->filled('durum')) $query->where('g.durum', $request->get('durum'));
        if ($request->filled('bas')) $query->whereDate('g.gider_tarihi', '>=', $request->get('bas'));
        if ($request->filled('bit')) $query->whereDate('g.gider_tarihi', '<=', $request->get('bit'));

        $rows = $query->orderByDesc('g.id')->get();

        $basliklar = ['Başlık', 'Kategori', 'Tutar', 'Para Birimi', 'Gider Tarihi', 'Son Ödeme', 'Durum', 'Ödeme Yöntemi', 'Tekrar', 'Açıklama'];
        $durumMap = ['odendi' => 'Ödendi', 'bekliyor' => 'Bekliyor', 'gecikti' => 'Gecikti'];
        $tekrarMap = ['tek' => 'Tek seferlik', 'aylik' => 'Aylık', 'yillik' => 'Yıllık'];

        $satirlar = [];
        foreach ($rows as $r) {
            $satirlar[] = [
                $r->baslik,
                $r->kategori ?? '',
                number_format((float) $r->tutar, 2, ',', '.'),
                $r->para_birimi ?? 'TRY',
                $r->gider_tarihi ?? '',
                $r->son_odeme_tarihi ?? '',
                $durumMap[$r->durum] ?? $r->durum,
                $r->odeme_yontemi ?? '',
                $tekrarMap[$r->tekrar] ?? $r->tekrar,
                $r->aciklama ?? '',
            ];
        }

        $dosyaAd = 'giderler_' . date('Y-m-d');

        // PhpSpreadsheet varsa .xlsx üret
        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            try {
                $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $ss->getActiveSheet();
                $sheet->setTitle('Giderler');
                $sheet->fromArray($basliklar, null, 'A1');
                $sheet->fromArray($satirlar, null, 'A2');
                // Başlık satırı kalın
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
                return response()->streamDownload(function () use ($writer) {
                    $writer->save('php://output');
                }, $dosyaAd . '.xlsx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            } catch (\Throwable $e) {
                \Log::warning('xlsx üretilemedi, CSV fallback', ['err' => $e->getMessage()]);
            }
        }

        // CSV fallback (her zaman çalışır, Excel açar)
        return response()->streamDownload(function () use ($basliklar, $satirlar) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM (Excel TR karakter)
            fputcsv($out, $basliklar, ';');
            foreach ($satirlar as $s) {
                fputcsv($out, $s, ';');
            }
            fclose($out);
        }, $dosyaAd . '.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    // ═══════════════════════════════════════════════════════════
    // ENVANTER (Demirbaş / Stok) — Harcamalar sayfasında "Envanter" tab'ı
    // ═══════════════════════════════════════════════════════════

    /**
     * Envanter listesi + özet.
     */
    public function envanterIndex(Request $request)
    {
        if (!Schema::hasTable('envanter')) {
            return view('admin.giderler.envanter', [
                'envanterler' => collect(),
                'ozet' => ['kalem' => 0, 'toplam_adet' => 0, 'toplam_deger' => 0],
                'filtre' => $request->all(),
                'tabloYok' => true,
            ]);
        }

        $arama = trim((string) $request->get('q'));
        $durum = $request->get('durum');

        $query = DB::table('envanter');
        if ($arama !== '') {
            $query->where(function ($w) use ($arama) {
                $w->where('ad', 'like', "%{$arama}%")
                  ->orWhere('kategori', 'like', "%{$arama}%")
                  ->orWhere('aciklama', 'like', "%{$arama}%");
            });
        }
        if (in_array($durum, ['kullanimda', 'depoda', 'arizali', 'elden_cikti'])) {
            $query->where('durum', $durum);
        }

        $envanterler = $query->orderByDesc('id')->paginate(25)->withQueryString();

        $ozet = [
            'kalem' => (int) DB::table('envanter')->count(),
            'toplam_adet' => (int) DB::table('envanter')->sum('adet'),
            'toplam_deger' => (float) DB::table('envanter')->select(DB::raw('SUM(adet * birim_fiyat) as t'))->value('t'),
        ];

        return view('admin.giderler.envanter', [
            'envanterler' => $envanterler,
            'ozet' => $ozet,
            'filtre' => compact('arama', 'durum'),
        ]);
    }

    /**
     * Yeni envanter kalemi ekleme formu.
     */
    public function envanterOlustur()
    {
        return view('admin.giderler.envanter_olustur');
    }

    /**
     * Envanter kalemini kaydet.
     */
    public function envanterStore(Request $request)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'adet' => 'required|integer|min:1',
            'birim_fiyat' => 'required|numeric|min:0',
            'durum' => 'required|in:kullanimda,depoda,arizali,elden_cikti',
            'alis_tarihi' => 'nullable|date',
            'aciklama' => 'nullable|string',
        ], [
            'ad.required' => 'Ürün adı zorunludur.',
            'adet.required' => 'Adet zorunludur.',
            'birim_fiyat.required' => 'Birim fiyat zorunludur.',
        ]);

        if (!Schema::hasTable('envanter')) {
            return back()->withInput()->with('error', 'Envanter tablosu yok. SQL kurulumunu çalıştırın.');
        }

        DB::table('envanter')->insert([
            'ad' => $validated['ad'],
            'kategori' => $validated['kategori'] ?? null,
            'adet' => $validated['adet'],
            'birim_fiyat' => $validated['birim_fiyat'],
            'durum' => $validated['durum'],
            'alis_tarihi' => $validated['alis_tarihi'] ?? null,
            'aciklama' => $validated['aciklama'] ?? null,
            'olusturan_id' => session('admin_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.giderler.envanter')->with('success', 'Envanter kalemi eklendi.');
    }

    /**
     * Envanter düzenleme formu.
     */
    public function envanterDuzenle($id)
    {
        $kalem = DB::table('envanter')->where('id', $id)->first();
        if (!$kalem) {
            return redirect()->route('admin.giderler.envanter')->with('error', 'Kayıt bulunamadı.');
        }
        return view('admin.giderler.envanter_duzenle', compact('kalem'));
    }

    /**
     * Envanter güncelle.
     */
    public function envanterGuncelle(Request $request, $id)
    {
        $validated = $request->validate([
            'ad' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'adet' => 'required|integer|min:1',
            'birim_fiyat' => 'required|numeric|min:0',
            'durum' => 'required|in:kullanimda,depoda,arizali,elden_cikti',
            'alis_tarihi' => 'nullable|date',
            'aciklama' => 'nullable|string',
        ]);

        DB::table('envanter')->where('id', $id)->update([
            'ad' => $validated['ad'],
            'kategori' => $validated['kategori'] ?? null,
            'adet' => $validated['adet'],
            'birim_fiyat' => $validated['birim_fiyat'],
            'durum' => $validated['durum'],
            'alis_tarihi' => $validated['alis_tarihi'] ?? null,
            'aciklama' => $validated['aciklama'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.giderler.envanter')->with('success', 'Envanter kalemi güncellendi.');
    }

    /**
     * Envanter sil.
     */
    public function envanterSil($id)
    {
        if (!Schema::hasTable('envanter')) {
            return back();
        }
        DB::table('envanter')->where('id', $id)->delete();
        return redirect()->route('admin.giderler.envanter')->with('success', 'Envanter kalemi silindi.');
    }


    /**
     * Giderleri Tablolar (Luckysheet) sistemine YENİ bir tablo olarak aktarır.
     * Her aktarımda yeni Spreadsheet kaydı oluşturur. Mevcut filtreleri uygular.
     */
    public function tablolaraAktar(Request $request)
    {
        if (!Schema::hasTable('giderler')) {
            return back()->with('error', 'Gider tablosu yok.');
        }
        if (!class_exists(\App\Models\Spreadsheet::class) || !Schema::hasTable('spreadsheets')) {
            return back()->with('error', 'Tablolar modülü bulunamadı.');
        }

        // Filtreleri uygula (index/excel ile aynı)
        $query = DB::table('giderler as g')
            ->leftJoin('gider_kategorileri as k', 'k.id', '=', 'g.kategori_id')
            ->select('g.baslik', 'k.ad as kategori', 'g.tutar',
                     'g.gider_tarihi', 'g.son_odeme_tarihi', 'g.durum', 'g.odeme_yontemi');

        if ($request->filled('q')) {
            $ara = trim($request->get('q'));
            $query->where(function ($w) use ($ara) {
                $w->where('g.baslik', 'like', "%{$ara}%")->orWhere('g.aciklama', 'like', "%{$ara}%");
            });
        }
        if ($request->filled('kategori')) $query->where('g.kategori_id', (int) $request->get('kategori'));
        if ($request->filled('durum')) $query->where('g.durum', $request->get('durum'));
        if ($request->filled('bas')) $query->whereDate('g.gider_tarihi', '>=', $request->get('bas'));
        if ($request->filled('bit')) $query->whereDate('g.gider_tarihi', '<=', $request->get('bit'));

        $rows = $query->orderByDesc('g.id')->get();

        // Luckysheet celldata üret (SpreadsheetTemplates ile birebir format)
        $celldata = $this->giderCelldata($rows);

        $sheet = [
            'name' => 'Giderler',
            'color' => '#d97706',
            'index' => 0,
            'order' => 0,
            'status' => 1,
            'row' => 100,
            'column' => 26,
            'load' => 0,
            'config' => [
                'columnlen' => (object) ['0' => 240, '1' => 160, '2' => 120, '3' => 120, '4' => 120, '5' => 110, '6' => 150],
                'rowlen' => (object) ['0' => 32],
            ],
            'celldata' => $celldata,
            'zoomRatio' => 1,
            'showGridLines' => 1,
            'defaultRowHeight' => 24,
            'defaultColWidth' => 100,
        ];

        $veri = json_encode([$sheet], JSON_UNESCAPED_UNICODE);

        $sp = Spreadsheet::create([
            'ad' => 'Harcamalar - ' . now()->format('d.m.Y H:i'),
            'aciklama' => 'Harcamalar modülünden aktarıldı (' . $rows->count() . ' kayıt).',
            'ikon' => '💰',
            'veri' => $veri,
            'olusturan_id' => session('admin_id'),
            'yetkili_ids' => [],
            'herkes_gorur' => false,
            'otomatik_kaydet' => true,
        ]);

        return redirect()->route('admin.tablolar.show', $sp->id)
            ->with('success', 'Giderler tabloya aktarıldı. Düzenleyebilirsiniz.');
    }

    /**
     * Gider satırlarından Luckysheet celldata dizisi üretir.
     */
    private function giderCelldata($rows): array
    {
        $cd = [];
        // Stiller (SpreadsheetTemplates ile aynı mantık)
        $head = ['bg' => '#d97706', 'fc' => '#ffffff', 'bl' => 1, 'ht' => 0, 'vt' => 0, 'fs' => 11];
        $red  = ['fc' => '#dc2626', 'bl' => 1, 'ht' => 2, 'fs' => 12];

        $basliklar = ['BAŞLIK', 'KATEGORİ', 'TUTAR', 'GİDER TARİHİ', 'SON ÖDEME', 'DURUM', 'ÖDEME YÖNTEMİ'];
        foreach ($basliklar as $c => $b) {
            $cd[] = ['r' => 0, 'c' => $c, 'v' => [
                'v' => $b, 'm' => $b, 'ct' => ['fa' => 'General', 't' => 'g'],
            ] + $head];
        }

        $durumMap = ['odendi' => 'Ödendi', 'bekliyor' => 'Bekliyor', 'gecikti' => 'Gecikti'];
        $r = 1;
        foreach ($rows as $g) {
            $tarih = $g->gider_tarihi ? date('d.m.Y', strtotime($g->gider_tarihi)) : '';
            $sonOdeme = $g->son_odeme_tarihi ? date('d.m.Y', strtotime($g->son_odeme_tarihi)) : '';
            $deger = [
                $g->baslik,
                $g->kategori ?? '',
                (float) $g->tutar,
                $tarih,
                $sonOdeme,
                $durumMap[$g->durum] ?? $g->durum,
                $g->odeme_yontemi ?? '',
            ];
            foreach ($deger as $c => $v) {
                if ($c === 2) {
                    // Tutar sütunu: sayı + para formatı
                    $cd[] = ['r' => $r, 'c' => $c, 'v' => [
                        'v' => $v, 'm' => number_format((float) $v, 2, ',', '.'),
                        'ct' => ['fa' => '#,##0.00', 't' => 'n'],
                    ]];
                } else {
                    $cd[] = ['r' => $r, 'c' => $c, 'v' => [
                        'v' => $v, 'm' => (string) $v, 'ct' => ['fa' => 'General', 't' => 'g'],
                    ]];
                }
            }
            $r++;
        }

        // TOPLAM satırı (formül)
        if ($r > 1) {
            $cd[] = ['r' => $r, 'c' => 1, 'v' => [
                'v' => 'TOPLAM', 'm' => 'TOPLAM', 'ct' => ['fa' => 'General', 't' => 'g'],
            ] + $red];
            $cd[] = ['r' => $r, 'c' => 2, 'v' => [
                'f' => '=SUM(C2:C' . $r . ')', 'v' => 0, 'm' => '0',
                'ct' => ['fa' => '#,##0.00', 't' => 'n'],
            ] + $red];
        }

        return $cd;
    }

}