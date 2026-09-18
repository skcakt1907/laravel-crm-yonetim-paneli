<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Spreadsheet;

class FaturaController extends Controller
{
    /**
     * Fatura listelerinde arama çubuğu (?ara=...).
     * Fatura no, başlık, müşteri adı-soyadı ve e-posta üzerinden arar.
     * Sorguda 'uyeler' join'i bulunduğu varsayılır.
     */
    protected function aramaUygula($query, Request $request): void
    {
        $ara = trim((string) $request->query('ara', ''));
        if ($ara === '') {
            return;
        }

        $query->where(function ($w) use ($ara) {
            $w->where('faturalar.fatura_no', 'like', "%{$ara}%")
              ->orWhere('faturalar.baslik', 'like', "%{$ara}%")
              ->orWhere('uyeler.ad', 'like', "%{$ara}%")
              ->orWhere('uyeler.soyad', 'like', "%{$ara}%")
              ->orWhere('uyeler.email', 'like', "%{$ara}%")
              ->orWhereRaw("CONCAT(COALESCE(uyeler.ad,''),' ',COALESCE(uyeler.soyad,'')) LIKE ?", ["%{$ara}%"]);
        });
    }

    /**
     * Manuel fatura olusturma formu.
     */
    public function ekle(Request $request)
    {
        $uyeler = DB::table('uyeler')
            ->select('id', 'ad', 'soyad', 'email', 'telefon')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        $secilenUyeId = $request->get('uyeid');

        // ekle.blade.php her üyede $item->secim (seçili mi?) bekliyor. Eksikse
        // "Undefined property: stdClass::$secim" hatası veriyordu.
        $uyeler->transform(function ($u) use ($secilenUyeId) {
            $u->secim = ($secilenUyeId !== null && (int) $u->id === (int) $secilenUyeId);
            return $u;
        });

        return view('admin.faturalar.ekle', compact('uyeler', 'secilenUyeId'));
    }

    /**
     * Manuel fatura kaydet.
     */
    public function eklePost(Request $request)
    {
        $request->validate([
            'uyeid'       => 'required|integer|exists:uyeler,id',
            'baslik'      => 'required|string|max:255',
            'hizmet'      => 'nullable|string|max:255',
            'tutar'       => 'required|numeric|min:0',
            'bitis_tarih' => 'nullable|date',
            'aciklama'    => 'nullable|string',
            'durum'       => 'nullable|integer|in:0,1,2',
            'kdv_orani'   => 'nullable|numeric|min:0|max:100',
            'para_birimi' => 'nullable|string|in:TL,USD,EUR,AED,GBP',
        ]);

        $uye = DB::table('uyeler')->where('id', $request->uyeid)->first();

        // 💱 Para birimi: TL dışıysa TCMB kuruyla TL'ye çevir.
        // Faturanın ana tutarı her zaman TL tutulur (ödeme/rapor akışları bozulmaz),
        // orijinal döviz bilgisi para_birimi/doviz_tutar/kur kolonlarında saklanır.
        $paraBirimi  = $request->para_birimi ?: 'TL';
        $girilen     = (float) $request->tutar;
        $kur         = 1.0;
        $dovizTutar  = null;
        $tlTutar     = $girilen;
        if ($paraBirimi !== 'TL') {
            $kurlar = \App\Helpers\DovizKuruHelper::tcmbKurlariCek();
            $kur = (float) ($kurlar[$paraBirimi] ?? 0);
            if ($kur <= 0) {
                return back()->withInput()->with('error', 'TCMB kuru alınamadı (' . $paraBirimi . '). Lütfen tekrar deneyin veya TL seçin.');
            }
            $dovizTutar = $girilen;
            $tlTutar = round($girilen * $kur, 2);
        }

        // Döviz faturasında açıklamanın başına kur notu (fatura detayı + mailde görünür)
        $aciklama = $request->aciklama;
        if ($paraBirimi !== 'TL') {
            $kurNotu = 'Döviz: ' . number_format($dovizTutar, 2, ',', '.') . ' ' . $paraBirimi
                . ' × ' . number_format($kur, 4, ',', '.') . ' (TCMB) = '
                . number_format($tlTutar, 2, ',', '.') . ' TL';
            $aciklama = $kurNotu . ($aciklama ? "\n" . $aciklama : '');
        }

        // Fatura numarasi uret: YYYYMMDD-XXXX
        $faturaNo = 'F-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        while (DB::table('faturalar')->where('fatura_no', $faturaNo)->exists()) {
            $faturaNo = 'F-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $kayit = [
            'fatura_no'   => $faturaNo,
            'uyeid'       => $request->uyeid,
            'baslik'      => $request->baslik,
            'hizmet'      => $request->hizmet,
            'tutar'       => $tlTutar,
            'kdv_orani'   => $request->kdv_orani ?? 20,
            'kdv'         => round($tlTutar * ((float) ($request->kdv_orani ?? 20)) / 100, 2),
            'bitis_tarih' => $request->bitis_tarih,
            'aciklama'    => $aciklama,
            'durum'       => $request->durum ?? 0, // 0=bekleyen, 1=odendi, 2=iptal
            'tarih'       => now(),
            'mail'        => $uye->email ?? null,
        ];

        // TAHSILAT TARIHI
        // Fatura kesilirken dogrudan "Odendi" isaretlenebiliyor. Burada
        // odenen_tarih yazilmadigi icin fatura tahsilat tarihsiz kaydediliyordu;
        // gunluk hareketler / finans raporu DATE(odenen_tarih) uzerinden
        // calistigi icin bu faturalar hicbir gune dusmuyordu ve kullanicinin
        // faturayi tekrar acip tarihi elle girmesi gerekiyordu.
        // Duzenleme ekranindaki (duzenlePost) mantigin aynisi:
        //   elle girildiyse o, girilmediyse bugun, odendi degilse bos.
        if ((int) ($request->durum ?? 0) === 1) {
            $kayit['odenen_tarih'] = $request->filled('odenen_tarih')
                ? $request->odenen_tarih
                : now()->toDateString();
        } else {
            $kayit['odenen_tarih'] = null;
        }

        // Döviz kolonları (SQL kurulumu yapıldıysa)
        if (\Illuminate\Support\Facades\Schema::hasColumn('faturalar', 'para_birimi')) {
            $kayit['para_birimi'] = $paraBirimi;
            $kayit['doviz_tutar'] = $dovizTutar;
            $kayit['kur']         = $paraBirimi !== 'TL' ? $kur : null;
        }

        $id = DB::table('faturalar')->insertGetId($kayit);

        // ETTN (e-fatura benzeri benzersiz kod) — kolon varsa üret ve kaydet.
        if (\Illuminate\Support\Facades\Schema::hasColumn('faturalar', 'ettn')) {
            DB::table('faturalar')->where('id', $id)->update(['ettn' => (string) \Illuminate\Support\Str::uuid()]);
        }

        // 📧 Müşteriye fatura mailı (admin "mail gönder" checkbox işaretlemişse veya varsayılan)
        $mailGonder = $request->has('mail_gonder') ? (int)$request->mail_gonder === 1 : true;
        if ($mailGonder && $request->uyeid) {
            try {
                \App\Services\CustomerNotifier::faturaKesildi($request->uyeid, $id);
            } catch (\Throwable $e) {
                \Log::warning('Fatura mail bildirimi gönderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.faturalar.detay', $id)
            ->with('success', 'Fatura oluşturuldu: ' . $faturaNo);
    }

    public function index(Request $request)
    {
        $ay = $request->get('ay');
        $yil = $request->get('yil');
        $durum = $request->get('durum');

        $query = DB::table('faturalar')
            ->join('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select('faturalar.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email');

        // Ay filtresi
        if ($ay && $yil) {
            $query->whereMonth('faturalar.tarih', $ay)
                  ->whereYear('faturalar.tarih', $yil);
        } elseif ($yil) {
            $query->whereYear('faturalar.tarih', $yil);
        }

        // Durum filtresi
        if ($durum !== null && $durum !== '') {
            $query->where('faturalar.durum', $durum);
        }

        $this->aramaUygula($query, $request);

        $faturalar = $query->orderBy('faturalar.id', 'desc')->paginate(20)->onEachSide(1)->withQueryString();

        // Ozet istatistikler (filtreye gore)
        $queryOzet = DB::table('faturalar');
        if ($ay && $yil) {
            $queryOzet->whereMonth('tarih', $ay)->whereYear('tarih', $yil);
        } elseif ($yil) {
            $queryOzet->whereYear('tarih', $yil);
        }

        $toplamTutar = (clone $queryOzet)->sum('tutar');
        $odenenTutar = (clone $queryOzet)->where('durum', 1)->sum('tutar');
        $bekleyenTutar = (clone $queryOzet)->where('durum', 0)->sum('tutar');

        // Mevcut yillar (dropdown icin)
        $mevcutYillar = DB::table('faturalar')
            ->selectRaw('YEAR(tarih) as yil')
            ->whereNotNull('tarih')
            ->groupBy('yil')
            ->orderBy('yil', 'desc')
            ->pluck('yil');

        $statCards = [
            ['label'=>'TOPLAM FATURA','value'=>DB::table('faturalar')->count(),'icon'=>'🧾','color'=>'#3b82f6','text'=>'#60a5fa'],
            ['label'=>'ÖDENEN','value'=>DB::table('faturalar')->where('durum',1)->count(),'icon'=>'✅','color'=>'#22c55e','text'=>'#4ade80'],
            ['label'=>'BEKLEYEN','value'=>DB::table('faturalar')->where('durum',0)->count(),'icon'=>'⏳','color'=>'#f59e0b','text'=>'#fdba74'],
            ['label'=>'TOPLAM TUTAR','value'=>'₺'.number_format((float)$toplamTutar,0,',','.'),'icon'=>'💰','color'=>'#b8b62e','text'=>'#d4d066'],
        ];

        return view('admin.faturalar.index', compact(
            'faturalar', 'ay', 'yil', 'durum',
            'toplamTutar', 'odenenTutar', 'bekleyenTutar', 'mevcutYillar', 'statCards'
        ));
    }
    
    /**
     * Fatura düzenleme formu.
     */
    public function duzenle($id)
    {
        $fatura = DB::table('faturalar')->where('id', $id)->first();
        if (!$fatura) {
            return redirect()->route('admin.faturalar.index')->with('error', 'Fatura bulunamadı!');
        }

        $uyeler = DB::table('uyeler')
            ->select('id', 'ad', 'soyad', 'email', 'telefon')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        return view('admin.faturalar.duzenle', compact('fatura', 'uyeler'));
    }

    /**
     * Fatura güncelle (döviz destekli — tutar/birim değişirse güncel TCMB kuruyla TL yeniden hesaplanır).
     */
    public function duzenlePost(Request $request, $id)
    {
        $fatura = DB::table('faturalar')->where('id', $id)->first();
        if (!$fatura) {
            return redirect()->route('admin.faturalar.index')->with('error', 'Fatura bulunamadı!');
        }

        $request->validate([
            'uyeid'        => 'required|integer|exists:uyeler,id',
            'baslik'       => 'required|string|max:255',
            'hizmet'       => 'nullable|string|max:255',
            'tutar'        => 'required|numeric|min:0',
            // Fatura (düzenleme) ve tahsilat tarihi artık elle düzeltilebiliyor:
            // Haziran'da kesilip Temmuz'da tahsil edilen faturalar doğru aya taşınabilsin.
            'tarih'        => 'nullable|date',
            'odenen_tarih' => 'nullable|date',
            'bitis_tarih' => 'nullable|date',
            'aciklama'    => 'nullable|string',
            'durum'       => 'nullable|integer|in:0,1,2',
            'kdv_orani'   => 'nullable|numeric|min:0|max:100',
            'para_birimi' => 'nullable|string|in:TL,USD,EUR,AED,GBP',
        ]);

        $uye = DB::table('uyeler')->where('id', $request->uyeid)->first();

        // 💱 Döviz işleme: eklePost ile aynı mantık
        $paraBirimi = $request->para_birimi ?: 'TL';
        $girilen    = (float) $request->tutar;
        $kur        = 1.0;
        $dovizTutar = null;
        $tlTutar    = $girilen;
        if ($paraBirimi !== 'TL') {
            $kurlar = \App\Helpers\DovizKuruHelper::tcmbKurlariCek();
            $kur = (float) ($kurlar[$paraBirimi] ?? 0);
            if ($kur <= 0) {
                return back()->withInput()->with('error', 'TCMB kuru alınamadı (' . $paraBirimi . '). Lütfen tekrar deneyin veya TL seçin.');
            }
            $dovizTutar = $girilen;
            $tlTutar = round($girilen * $kur, 2);
        }

        $kayit = [
            'uyeid'       => $request->uyeid,
            'baslik'      => $request->baslik,
            'hizmet'      => $request->hizmet,
            'tutar'       => $tlTutar,
            'kdv_orani'   => $request->kdv_orani ?? 20,
            'kdv'         => round($tlTutar * ((float) ($request->kdv_orani ?? 20)) / 100, 2),
            'bitis_tarih' => $request->bitis_tarih,
            'aciklama'    => $request->aciklama,
            'durum'       => $request->durum ?? 0,
            'mail'        => $uye->email ?? null,
        ];

        // Fatura tarihi: boş bırakılırsa mevcut değer korunur (sıfırlanmaz).
        if ($request->filled('tarih')) {
            $kayit['tarih'] = $request->tarih;
        }
        // Tahsilat tarihi: elle girilirse o kullanılır. Girilmemiş ama fatura
        // "Ödendi" işaretlendiyse ve daha önce hiç tarih yoksa bugünü yaz.
        if ($request->filled('odenen_tarih')) {
            $kayit['odenen_tarih'] = $request->odenen_tarih;
        } elseif ((int) ($request->durum ?? 0) === 1 && empty($fatura->odenen_tarih)) {
            $kayit['odenen_tarih'] = now()->toDateString();
        } elseif ((int) ($request->durum ?? 0) !== 1) {
            $kayit['odenen_tarih'] = null; // ödendi değilse tahsilat tarihi tutulmaz
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('faturalar', 'para_birimi')) {
            $kayit['para_birimi'] = $paraBirimi;
            $kayit['doviz_tutar'] = $dovizTutar;
            $kayit['kur']         = $paraBirimi !== 'TL' ? $kur : null;
        }

        DB::table('faturalar')->where('id', $id)->update($kayit);

        return redirect()->route('admin.faturalar.detay', $id)
            ->with('success', 'Fatura güncellendi: ' . ($fatura->fatura_no ?? '#' . $id));
    }

    public function detay($id)
    {
        $fatura = DB::table('faturalar')
            ->join('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select('faturalar.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email', 'uyeler.telefon')
            ->where('faturalar.id', $id)
            ->first();
        
        if (!$fatura) {
            return redirect()->route('admin.faturalar.index')->with('error', 'Fatura bulunamadı!');
        }
        
        return view('admin.faturalar.detay', compact('fatura'));
    }
    
    /**
     * Fatura görüntüleme (yazdırma için)
     */
    public function goster($id)
    {
        $fatura = DB::table('faturalar')
            ->join('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->leftJoin('adresler', function ($j) {
                $j->on('adresler.uyeid', '=', DB::raw('CAST(uyeler.id AS CHAR)'))
                  ->where('adresler.varsayilan', '=', 1);
            })
            ->select('faturalar.*',
                'uyeler.ad',
                'uyeler.soyad',
                'uyeler.email',
                'uyeler.telefon',
                'uyeler.firmaadi',
                'uyeler.vergino',
                'uyeler.vergidairesi',
                'adresler.adres',
                'adresler.il',
                'adresler.ilce',
                'adresler.pkodu'
            )
            ->where('faturalar.id', $id)
            ->first();
        
        if (!$fatura) {
            return redirect()->route('admin.faturalar.index')->with('error', 'Fatura bulunamadı!');
        }
        
        // Fatura ayarlarını getir
        $ayarlar = DB::table('ayarlar')->first();
        
        return view('admin.faturalar.goster', compact('fatura', 'ayarlar'));
    }
    
    public function durumDegistir($id, $durum)
    {
        // Durum: route'tan gelen string'i integer'a çevir
        $durumMap = ['bekleyen' => 0, 'onayli' => 1, 'odendi' => 1, 'iptal' => 2, 'reddedildi' => 2];
        $durumInt = isset($durumMap[$durum]) ? $durumMap[$durum] : (is_numeric($durum) ? (int)$durum : 0);

        DB::table('faturalar')->where('id', $id)->update([
            'durum' => $durumInt,
            'odenen_tarih' => $durumInt == 1 ? now()->toDateString() : null,
        ]);

        return redirect()->back()->with('success', 'Fatura durumu güncellendi.');
    }

    /**
     * MÜŞTERİYE ÖDEME BİLDİRİMİ (hatırlatma) — fatura detay ekranındaki butondan.
     * Müşteriye e-posta + (telefonu varsa) SMS ile ödeme hatırlatması gönderir.
     * Yalnızca ödenmemiş (durum=0) faturalar için çalışır.
     */
    public function hatirlatGonder($id)
    {
        $f = DB::table('faturalar')
            ->join('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select('faturalar.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email', 'uyeler.telefon', 'uyeler.firmaadi')
            ->where('faturalar.id', $id)
            ->first();

        if (!$f) {
            return redirect()->route('admin.faturalar.index')->with('error', 'Fatura bulunamadı!');
        }
        if ((int) $f->durum !== 0) {
            return redirect()->back()->with('error', 'Bu fatura ödenmemiş görünmüyor; hatırlatma gönderilmedi.');
        }

        $ad       = trim(($f->ad ?? '') . ' ' . ($f->soyad ?? '')) ?: ($f->firmaadi ?: 'Değerli Müşterimiz');
        $faturaNo = $f->fatura_no ?? $f->id;
        $tutar    = number_format((float) ($f->tutar ?? 0), 2, ',', '.');
        $vade     = $f->bitis_tarih ? \Carbon\Carbon::parse($f->bitis_tarih)->format('d.m.Y') : '-';

        $gidenler = [];
        $hatalar  = [];

        // ── 1) E-POSTA ──────────────────────────────────────────────
        $email = trim((string) ($f->email ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $subject = "Ödeme Hatırlatması — Fatura #{$faturaNo}";
                \App\Services\EmailNotificationService::send($email, $subject, $this->hatirlatmaMailGovdesi($ad, $faturaNo, $tutar, $vade, $f));
                $gidenler[] = 'e-posta (' . $email . ')';
            } catch (\Throwable $e) {
                \Log::warning('Fatura hatirlatma maili gonderilemedi', ['fatura' => $id, 'err' => $e->getMessage()]);
                $hatalar[] = 'e-posta gönderilemedi';
            }
        } else {
            $hatalar[] = 'müşterinin e-posta adresi kayıtlı değil';
        }

        // ── 2) SMS ──────────────────────────────────────────────────
        $tel = trim((string) ($f->telefon ?? ''));
        if ($tel !== '') {
            try {
                $mesaj = "Sayin {$ad}, {$faturaNo} nolu faturanizin (" . $tutar . " TL) son odeme tarihi {$vade}. "
                    . "Odemeniz icin tesekkur ederiz.";
                $sonuc = (new \App\Services\SmsService())->send($tel, $mesaj);
                if (!empty($sonuc['success'])) {
                    $gidenler[] = 'SMS (' . $tel . ')';
                } else {
                    $hatalar[] = 'SMS gönderilemedi (' . ($sonuc['message'] ?? 'servis kapalı olabilir') . ')';
                }
            } catch (\Throwable $e) {
                \Log::warning('Fatura hatirlatma SMS gonderilemedi', ['fatura' => $id, 'err' => $e->getMessage()]);
                $hatalar[] = 'SMS gönderilemedi';
            }
        } else {
            $hatalar[] = 'müşterinin telefonu kayıtlı değil';
        }

        // ── 3) Log (varsa) ──────────────────────────────────────────
        try {
            if (Schema::hasTable('odeme_hatirlatma_log')) {
                DB::table('odeme_hatirlatma_log')->insert([
                    'fatura_id'       => $id,
                    'email'           => $email ?: null,
                    'kalan_gun'       => 0, // 0 = elle gönderim (otomatik hatırlatma 7/3/1 kullanır, çakışmaz)
                    'gonderim_tarihi' => now()->toDateString(),
                    'durum'           => empty($gidenler) ? 'basarisiz' : 'gonderildi',
                    'hata'            => empty($hatalar) ? null : implode('; ', $hatalar),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // log tablosu şeması farklıysa akışı bozma
        }

        if (!empty($gidenler)) {
            $msg = 'Ödeme bildirimi gönderildi: ' . implode(' + ', $gidenler) . '.';
            if (!empty($hatalar)) {
                $msg .= ' (Not: ' . implode(', ', $hatalar) . '.)';
            }
            return redirect()->back()->with('success', $msg);
        }

        return redirect()->back()->with('error', 'Bildirim gönderilemedi: ' . implode(', ', $hatalar) . '.');
    }

    /** Ödeme hatırlatma e-postası HTML gövdesi. */
    private function hatirlatmaMailGovdesi(string $ad, $faturaNo, string $tutar, string $vade, $f): string
    {
        $baslik = htmlspecialchars($f->baslik ?? '—');
        $no     = htmlspecialchars((string) $faturaNo);
        $adSafe = htmlspecialchars($ad);

        return <<<HTML
<div style="font-family:Inter,system-ui,sans-serif;max-width:600px;margin:auto;padding:24px;background:#fafafa">
<div style="background:#fff;border-radius:12px;padding:30px;border-top:4px solid #b8b62e">
<h2 style="margin:0 0 12px;color:#0f172a">Merhaba {$adSafe},</h2>
<p style="color:#334155">Aşağıdaki faturanız için ödeme hatırlatmasıdır.</p>
<table style="width:100%;border-collapse:collapse;margin:18px 0">
<tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Fatura No</strong></td><td style="padding:8px;border-bottom:1px solid #eee">#{$no}</td></tr>
<tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Başlık</strong></td><td style="padding:8px;border-bottom:1px solid #eee">{$baslik}</td></tr>
<tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Tutar</strong></td><td style="padding:8px;border-bottom:1px solid #eee;font-weight:700;color:#b8b62e">₺{$tutar}</td></tr>
<tr><td style="padding:8px"><strong>Son Ödeme Tarihi</strong></td><td style="padding:8px;color:#dc2626;font-weight:700">{$vade}</td></tr>
</table>
<p style="color:#475569;font-size:14px">Ödemenizi tamamladıysanız bu mesajı dikkate almayınız. Sorularınız için bizimle iletişime geçebilirsiniz.</p>
<p style="color:#94a3b8;font-size:12px;margin-top:24px">İş Ortağım — ödeme bildirimi</p>
</div>
</div>
HTML;
    }

    public function sil($id)
    {
        DB::table('faturalar')->where('id', $id)->delete();
        
        return redirect()->route('admin.faturalar.index')->with('success', 'Fatura silindi.');
    }
    
    public function bekleyen(Request $request)
    {
        $filter = $request->query('period', 'all');

        $now = now();
        $thisMonth = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];

        $defaultPick = $now->copy()->subMonth();
        $pickMonth = (int) $request->query('month', $defaultPick->month);
        $pickYear = (int) $request->query('year', $defaultPick->year);
        if ($pickMonth < 1 || $pickMonth > 12) { $pickMonth = $defaultPick->month; }
        if ($pickYear < 2000 || $pickYear > (int) $now->year + 1) { $pickYear = $defaultPick->year; }

        $pickedRef = \Carbon\Carbon::create($pickYear, $pickMonth, 1);
        $pickedRange = [$pickedRef->copy()->startOfMonth(), $pickedRef->copy()->endOfMonth()];

        $baseStats = fn() => DB::table('faturalar')->where('durum', 0);

        $stats = [
            'this_month' => [
                'count' => (clone $baseStats())->whereTarihBetween('tarih', $thisMonth[0], $thisMonth[1])->count(),
                'total' => (float) (clone $baseStats())->whereTarihBetween('tarih', $thisMonth[0], $thisMonth[1])->sum('tutar'),
            ],
            'picked' => [
                'count' => (clone $baseStats())->whereTarihBetween('tarih', $pickedRange[0], $pickedRange[1])->count(),
                'total' => (float) (clone $baseStats())->whereTarihBetween('tarih', $pickedRange[0], $pickedRange[1])->sum('tutar'),
                'month' => $pickMonth,
                'year'  => $pickYear,
            ],
            'all' => [
                'count' => (clone $baseStats())->count(),
                'total' => (float) (clone $baseStats())->sum('tutar'),
            ],
        ];

        $mevcutYillar = DB::table('faturalar')
            ->where('durum', 0)
            ->whereNotNull('tarih')
            ->selectRaw('DISTINCT YEAR(tarih) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn($y) => (int) $y)
            ->toArray();
        if (!in_array((int) $now->year, $mevcutYillar, true)) {
            array_unshift($mevcutYillar, (int) $now->year);
        }
        if (!in_array($pickYear, $mevcutYillar, true)) {
            $mevcutYillar[] = $pickYear;
            rsort($mevcutYillar);
        }

        $query = DB::table('faturalar')
            ->join('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select('faturalar.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
            ->where('faturalar.durum', 0);

        if ($filter === 'this_month') {
            $query->whereTarihBetween('faturalar.tarih', $thisMonth[0], $thisMonth[1]);
        } elseif ($filter === 'picked') {
            $query->whereTarihBetween('faturalar.tarih', $pickedRange[0], $pickedRange[1]);
        }

        $this->aramaUygula($query, $request);

        $faturalar = $query->orderBy('faturalar.id', 'desc')
            ->paginate(20)->onEachSide(1)
            ->appends($request->query());

        return view('admin.faturalar.bekleyen', compact(
            'faturalar', 'stats', 'filter', 'pickMonth', 'pickYear', 'mevcutYillar'
        ));
    }
    
    public function onaylanan(Request $request)
    {
        $filter = $request->query('period', 'all');

        $now = now();
        $thisMonth = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];

        $defaultPick = $now->copy()->subMonth();
        $pickMonth = (int) $request->query('month', $defaultPick->month);
        $pickYear = (int) $request->query('year', $defaultPick->year);
        if ($pickMonth < 1 || $pickMonth > 12) { $pickMonth = $defaultPick->month; }
        if ($pickYear < 2000 || $pickYear > (int) $now->year + 1) { $pickYear = $defaultPick->year; }

        $pickedRef = \Carbon\Carbon::create($pickYear, $pickMonth, 1);
        $pickedRange = [$pickedRef->copy()->startOfMonth(), $pickedRef->copy()->endOfMonth()];

        $baseStats = fn() => DB::table('faturalar')->where('durum', 1);

        $stats = [
            'this_month' => [
                'count' => (clone $baseStats())->whereTarihBetween('tarih', $thisMonth[0], $thisMonth[1])->count(),
                'total' => (float) (clone $baseStats())->whereTarihBetween('tarih', $thisMonth[0], $thisMonth[1])->sum('tutar'),
            ],
            'picked' => [
                'count' => (clone $baseStats())->whereTarihBetween('tarih', $pickedRange[0], $pickedRange[1])->count(),
                'total' => (float) (clone $baseStats())->whereTarihBetween('tarih', $pickedRange[0], $pickedRange[1])->sum('tutar'),
                'month' => $pickMonth,
                'year'  => $pickYear,
            ],
            'all' => [
                'count' => (clone $baseStats())->count(),
                'total' => (float) (clone $baseStats())->sum('tutar'),
            ],
        ];

        $mevcutYillar = DB::table('faturalar')
            ->where('durum', 1)
            ->whereNotNull('tarih')
            ->selectRaw('DISTINCT YEAR(tarih) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn($y) => (int) $y)
            ->toArray();
        if (!in_array((int) $now->year, $mevcutYillar, true)) {
            array_unshift($mevcutYillar, (int) $now->year);
        }
        if (!in_array($pickYear, $mevcutYillar, true)) {
            $mevcutYillar[] = $pickYear;
            rsort($mevcutYillar);
        }

        $query = DB::table('faturalar')
            ->join('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select('faturalar.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
            ->where('faturalar.durum', 1);

        if ($filter === 'this_month') {
            $query->whereTarihBetween('faturalar.tarih', $thisMonth[0], $thisMonth[1]);
        } elseif ($filter === 'picked') {
            $query->whereTarihBetween('faturalar.tarih', $pickedRange[0], $pickedRange[1]);
        }

        $this->aramaUygula($query, $request);

        $faturalar = $query->orderBy('faturalar.id', 'desc')
            ->paginate(20)->onEachSide(1)
            ->appends($request->query());

        return view('admin.faturalar.onaylanan', compact(
            'faturalar', 'stats', 'filter', 'pickMonth', 'pickYear', 'mevcutYillar'
        ));
    }
    
    /**
     * Faturaları Excel'e aktar (tümü)
     */
    public function export($tip = 'all')
    {
        $query = DB::table('faturalar')
            ->leftJoin('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select(
                'faturalar.id',
                'faturalar.uyeid',
                'faturalar.baslik',
                'faturalar.bitis_tarih',
                'faturalar.tutar',
                'faturalar.odenen_tarih',
                'faturalar.durum',
                'faturalar.hizmet',
                'faturalar.aciklama',
                'faturalar.tarih',
                'faturalar.odeme_yontemi',
                'faturalar.spno',
                'faturalar.mail',
                'uyeler.ad',
                'uyeler.soyad',
                'uyeler.email',
                'uyeler.telefon'
            );
        
        // Tip'e göre filtrele
        if ($tip == 'bekleyen') {
            $query->where('faturalar.durum', 0);
        } elseif ($tip == 'onaylanan') {
            $query->where('faturalar.durum', 1);
        }
        // 'all' için filtre yok
        
        $faturalar = $query->orderBy('faturalar.id', 'desc')->get();

        return $this->exportFaturalarToXlsx($faturalar, $tip);
    }

    /**
     * Stilli XLSX export (PhpSpreadsheet) — başlık sarı, dondur, kolon genişlik, para format, alternating row
     */
    private function exportFaturalarToXlsx($faturalar, string $tip)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $title = $tip === 'bekleyen' ? 'Bekleyen Faturalar' : ($tip === 'onaylanan' ? 'Onaylanan Faturalar' : 'Tüm Faturalar');
        $sheet->setTitle(mb_substr($title, 0, 31));

        // Başlık banner (1. satır)
        $sheet->mergeCells('A1:Q1');
        $sheet->setCellValue('A1', 'İŞ ORTAĞIM — ' . mb_strtoupper($title) . ' (' . date('d.m.Y H:i') . ')');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FACC15']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Kolonlar (2. satır = header)
        $headers = ['ID','Müşteri ID','Müşteri','Email','Telefon','Başlık','Tutar (₺)','Durum','Tarih','Bitiş','Ödeme Tarihi','Ödeme Yöntemi','Hizmet','Açıklama','SP No','Mail','Fatura No'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '2', $h);
        }
        $sheet->getStyle('A2:Q2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '92400E']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'DDDDDD']]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // Veri (3. satırdan başlar)
        $row = 3;
        foreach ($faturalar as $f) {
            $musteri = trim(($f->ad ?? '') . ' ' . ($f->soyad ?? '')) ?: '—';
            $durum = (int) ($f->durum ?? 0) === 1 ? 'Ödendi' : 'Bekliyor';
            $sheet->fromArray([
                $f->id ?? '',
                $f->uyeid ?? '',
                $musteri,
                $f->email ?? '',
                $f->telefon ?? '',
                $f->baslik ?? '',
                (float) ($f->tutar ?? 0),
                $durum,
                $f->tarih ?? '',
                $f->bitis_tarih ?? '',
                $f->odenen_tarih ?? '',
                $f->odeme_yontemi ?? '',
                $f->hizmet ?? '',
                $f->aciklama ?? '',
                $f->spno ?? '',
                $f->mail ?? '',
                $f->id ?? '',
            ], null, 'A' . $row);

            // Tutar para birimi format
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00 "₺"');
            // Durum boyama
            $color = $durum === 'Ödendi' ? 'D1FAE5' : 'FEF3C7';
            $textColor = $durum === 'Ödendi' ? '047857' : 'C2410C';
            $sheet->getStyle('H' . $row)->applyFromArray([
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'font' => ['bold' => true, 'color' => ['rgb' => $textColor]],
                'alignment' => ['horizontal' => 'center'],
            ]);
            // Alternating row (çift satır gri)
            if ($row % 2 === 1) {
                $sheet->getStyle('A' . $row . ':Q' . $row)->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAFAFA']],
                ]);
            }
            $row++;
        }

        // Tüm data range border
        if ($row > 3) {
            $sheet->getStyle('A3:Q' . ($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'EEEEEE']]],
                'alignment' => ['vertical' => 'center', 'wrapText' => false],
                'font' => ['size' => 10],
            ]);
        }

        // Kolon genişlikleri
        $widths = ['A'=>6,'B'=>10,'C'=>22,'D'=>26,'E'=>16,'F'=>30,'G'=>14,'H'=>12,'I'=>12,'J'=>12,'K'=>14,'L'=>16,'M'=>20,'N'=>30,'O'=>10,'P'=>20,'Q'=>10];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Başlığı dondur (3. satırdan itibaren scroll)
        $sheet->freezePane('A3');

        // Auto filter
        if ($row > 3) {
            $sheet->setAutoFilter('A2:Q' . ($row - 1));
        }

        // Output
        $filename = 'faturalar_' . $tip . '_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    
    /**
     * Faturaları Excel'e aktar (sadece sayfadaki)
     */
    public function exportPage($tip = 'all', Request $request)
    {
        $query = DB::table('faturalar')
            ->leftJoin('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select(
                'faturalar.id',
                'faturalar.uyeid',
                'faturalar.baslik',
                'faturalar.bitis_tarih',
                'faturalar.tutar',
                'faturalar.odenen_tarih',
                'faturalar.durum',
                'faturalar.hizmet',
                'faturalar.aciklama',
                'faturalar.tarih',
                'faturalar.odeme_yontemi',
                'faturalar.spno',
                'faturalar.mail',
                'uyeler.ad',
                'uyeler.soyad',
                'uyeler.email',
                'uyeler.telefon'
            );
        
        // Tip'e göre filtrele
        if ($tip == 'bekleyen') {
            $query->where('faturalar.durum', 0);
        } elseif ($tip == 'onaylanan') {
            $query->where('faturalar.durum', 1);
        }
        
        // Sayfalama parametrelerini al (eğer varsa)
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $faturalar = $query->orderBy('faturalar.id', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        $filename = 'faturalar_' . $tip . '_sayfa_' . $page . '_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // UTF-8 BOM ekle (Excel için)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Başlıklar
        fputcsv($handle, [
            'ID', 'Müşteri ID', 'Müşteri Adı', 'Müşteri Soyadı', 'Email', 'Telefon',
            'Başlık', 'Tutar', 'Durum', 'Tarih', 'Bitiş Tarihi', 'Ödeme Tarihi',
            'Ödeme Yöntemi', 'Hizmet', 'Açıklama', 'SP No', 'Mail'
        ], ';');
        
        // Veriler
        foreach ($faturalar as $fatura) {
            fputcsv($handle, [
                $fatura->id ?? '',
                $fatura->uyeid ?? '',
                $fatura->ad ?? '',
                $fatura->soyad ?? '',
                $fatura->email ?? '',
                $fatura->telefon ?? '',
                $fatura->baslik ?? '',
                number_format((float)($fatura->tutar ?? 0), 2, ',', '.'),
                ($fatura->durum ?? 0) == 1 ? 'Ödendi' : 'Bekliyor',
                $fatura->tarih ?? '',
                $fatura->bitis_tarih ?? '',
                $fatura->odenen_tarih ?? '',
                $fatura->odeme_yontemi ?? '',
                $fatura->hizmet ?? '',
                $fatura->aciklama ?? '',
                $fatura->spno ?? '',
                $fatura->mail ?? '',
            ], ';');
        }
        
        fclose($handle);
        exit;
    }

    /**
     * Faturaları Tablolar (Luckysheet) sistemine YENİ tablo olarak aktarır.
     * $tip: all | bekleyen | onaylanan
     */
    public function tablolaraAktar($tip = 'all')
    {
        if (!class_exists(\App\Models\Spreadsheet::class) || !Schema::hasTable('spreadsheets')) {
            return back()->with('error', 'Tablolar modülü bulunamadı.');
        }

        $query = DB::table('faturalar')
            ->leftJoin('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->select('faturalar.id', 'faturalar.baslik', 'faturalar.tutar',
                     'faturalar.tarih', 'faturalar.odenen_tarih', 'faturalar.durum',
                     'uyeler.ad', 'uyeler.soyad', 'uyeler.firmaadi', 'uyeler.email');

        if ($tip == 'bekleyen') {
            $query->where('faturalar.durum', 0);
        } elseif ($tip == 'onaylanan') {
            $query->where('faturalar.durum', 1);
        }

        $rows = $query->orderByDesc('faturalar.id')->get();

        $celldata = $this->faturaCelldata($rows);

        $sheet = [
            'name' => 'Faturalar',
            'color' => '#10b981',
            'index' => 0, 'order' => 0, 'status' => 1,
            'row' => 100, 'column' => 26, 'load' => 0,
            'config' => [
                'columnlen' => (object) ['0' => 60, '1' => 220, '2' => 240, '3' => 120, '4' => 120, '5' => 110],
                'rowlen' => (object) ['0' => 32],
            ],
            'celldata' => $celldata,
            'zoomRatio' => 1, 'showGridLines' => 1, 'defaultRowHeight' => 24, 'defaultColWidth' => 100,
        ];

        $veri = json_encode([$sheet], JSON_UNESCAPED_UNICODE);
        $tipMetni = $tip === 'bekleyen' ? 'Bekleyen' : ($tip === 'onaylanan' ? 'Onaylanan' : 'Tüm');

        $sp = Spreadsheet::create([
            'ad' => 'Faturalar (' . $tipMetni . ') - ' . now()->format('d.m.Y H:i'),
            'aciklama' => 'Faturalar modülünden aktarıldı (' . $rows->count() . ' kayıt).',
            'ikon' => '🧾',
            'veri' => $veri,
            'olusturan_id' => session('admin_id'),
            'yetkili_ids' => [],
            'herkes_gorur' => false,
            'otomatik_kaydet' => true,
        ]);

        return redirect()->route('admin.tablolar.show', $sp->id)
            ->with('success', 'Faturalar tabloya aktarıldı.');
    }

    /**
     * Fatura satırlarından Luckysheet celldata üretir.
     */
    private function faturaCelldata($rows): array
    {
        $cd = [];
        $head = ['bg' => '#10b981', 'fc' => '#ffffff', 'bl' => 1, 'ht' => 0, 'vt' => 0, 'fs' => 11];
        $red  = ['fc' => '#059669', 'bl' => 1, 'ht' => 2, 'fs' => 12];

        $basliklar = ['NO', 'BAŞLIK', 'MÜŞTERİ', 'TUTAR', 'TARİH', 'DURUM'];
        foreach ($basliklar as $c => $b) {
            $cd[] = ['r' => 0, 'c' => $c, 'v' => ['v' => $b, 'm' => $b, 'ct' => ['fa' => 'General', 't' => 'g']] + $head];
        }

        $durumMap = [0 => 'Bekliyor', 1 => 'Ödendi', 2 => 'İptal'];
        $r = 1;
        foreach ($rows as $f) {
            $musteri = trim((string) ($f->firmaadi ?? ''));
            if ($musteri === '') $musteri = trim(($f->ad ?? '') . ' ' . ($f->soyad ?? ''));
            if ($musteri === '') $musteri = $f->email ?? '';
            $tarih = $f->tarih ? date('d.m.Y', strtotime($f->tarih)) : '';

            $deger = [
                (int) $f->id,
                $f->baslik ?? '',
                $musteri,
                (float) $f->tutar,
                $tarih,
                $durumMap[(int) $f->durum] ?? $f->durum,
            ];
            foreach ($deger as $c => $v) {
                if ($c === 3) {
                    $cd[] = ['r' => $r, 'c' => $c, 'v' => ['v' => $v, 'm' => number_format((float) $v, 2, ',', '.'), 'ct' => ['fa' => '#,##0.00', 't' => 'n']]];
                } else {
                    $cd[] = ['r' => $r, 'c' => $c, 'v' => ['v' => $v, 'm' => (string) $v, 'ct' => ['fa' => 'General', 't' => 'g']]];
                }
            }
            $r++;
        }

        if ($r > 1) {
            $cd[] = ['r' => $r, 'c' => 2, 'v' => ['v' => 'TOPLAM', 'm' => 'TOPLAM', 'ct' => ['fa' => 'General', 't' => 'g']] + $red];
            $cd[] = ['r' => $r, 'c' => 3, 'v' => ['f' => '=SUM(D2:D' . $r . ')', 'v' => 0, 'm' => '0', 'ct' => ['fa' => '#,##0.00', 't' => 'n']] + $red];
        }

        return $cd;
    }

}