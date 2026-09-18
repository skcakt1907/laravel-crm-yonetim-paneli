<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\DovizKuruHelper;
use App\Models\Uye;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaketTeklifEmail;

class PaketTeklifController extends Controller
{
    public function create()
    {
        $uyeler = Uye::orderBy('ad')->orderBy('soyad')->get();

        $paketler = DB::table('yazilimlar')
            ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($q) {
                $q->where('dil', 1);
            })
            ->orderBy('sira', 'asc')
            ->orderBy('id', 'desc')
            ->get(['id', 'adi', 'tutar']);

        // Kategoriler (teklifi de bir paket gibi sınıflandırabilmek için)
        $kategoriler = collect();
        try {
            if (Schema::hasTable('web_kategori')) {
                $kategoriler = DB::table('web_kategori')
                    ->when(Schema::hasColumn('web_kategori', 'durum'), fn($q) => $q->where('durum', 1))
                    ->when(Schema::hasColumn('web_kategori', 'dil'), fn($q) => $q->where('dil', 1))
                    ->when(Schema::hasColumn('web_kategori', 'sira'),
                        fn($q) => $q->orderBy('sira', 'asc'),
                        fn($q) => $q->orderBy('id', 'asc'))
                    ->get(['id', 'adi']);
            }
        } catch (\Throwable $e) {
            $kategoriler = collect();
        }

        return view('admin.paketler.teklif-olustur', compact('uyeler', 'paketler', 'kategoriler'));
    }

    public function goruntule($id)
    {
        $teklif = DB::table('paket_teklifleri')
            ->join('uyeler', 'paket_teklifleri.uye_id', '=', 'uyeler.id')
            ->select('paket_teklifleri.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
            ->where('paket_teklifleri.id', $id)
            ->firstOrFail();

        $paketler = DB::table('paket_teklif_paketleri')
            ->join('yazilimlar', 'paket_teklif_paketleri.paket_id', '=', 'yazilimlar.id')
            ->select('paket_teklif_paketleri.*', 'yazilimlar.adi')
            ->where('paket_teklif_paketleri.teklif_id', $id)
            ->get();

        return view('admin.paketler.teklif-goruntule', compact('teklif', 'paketler'));
    }

    /**
     * PUBLIC görüntüleme — login gerektirmez, token ile açılır.
     * GET /teklif-detay/{token}
     */
    public function goster($token)
    {
        $teklif = DB::table('paket_teklifleri')
            ->leftJoin('uyeler', 'paket_teklifleri.uye_id', '=', 'uyeler.id')
            ->select('paket_teklifleri.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
            ->where('paket_teklifleri.token', $token)
            ->firstOrFail();

        $paketler = DB::table('paket_teklif_paketleri')
            ->join('yazilimlar', 'paket_teklif_paketleri.paket_id', '=', 'yazilimlar.id')
            ->select('paket_teklif_paketleri.*', 'yazilimlar.adi')
            ->where('paket_teklif_paketleri.teklif_id', $teklif->id)
            ->get();

        // Galeri (gösterim tutarlılığı için boş diziler)
        $galeri = [];
        $teklifGorselleri = [];
        if (!empty($teklif->resim)) { $teklifGorselleri[] = $teklif->resim; }
        try {
            if (Schema::hasTable('paket_teklif_resimleri')) {
                $ekstra = DB::table('paket_teklif_resimleri')
                    ->where('teklif_id', $teklif->id)
                    ->orderBy('sira', 'asc')->orderBy('id', 'asc')
                    ->pluck('resim')->all();
                $teklifGorselleri = array_merge($teklifGorselleri, $ekstra);
            }
        } catch (\Throwable $e) {}

        $kategoriAdi = null;
        if (!empty($teklif->kategori) && Schema::hasTable('web_kategori')) {
            try {
                // Çoklu kategori: virgüllü id'leri ad'lara çevir
                $katIdler = array_filter(array_map('intval', explode(',', (string) $teklif->kategori)));
                if (!empty($katIdler)) {
                    $adlar = DB::table('web_kategori')->whereIn('id', $katIdler)->pluck('adi')->toArray();
                    $kategoriAdi = !empty($adlar) ? implode(', ', $adlar) : null;
                }
            } catch (\Throwable $e) { $kategoriAdi = null; }
        }

        $ayar = \App\Models\Ayar::first();

        return view('tema.teklif-detay', compact('teklif', 'paketler', 'galeri', 'teklifGorselleri', 'kategoriAdi', 'ayar'));
    }

    /**
     * PUBLIC görüntüleme — temiz SEO slug ile.
     * GET /detay/teklif/{slug}
     */
    public function gosterSlug($slug)
    {
        $teklif = DB::table('paket_teklifleri')
            ->leftJoin('uyeler', 'paket_teklifleri.uye_id', '=', 'uyeler.id')
            ->select('paket_teklifleri.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
            ->where('paket_teklifleri.seo', $slug)
            ->firstOrFail();

        // Teklif paketleri + her paketin gorseli/seo'su (galeri + sepet linki icin)
        $paketler = DB::table('paket_teklif_paketleri')
            ->join('yazilimlar', 'paket_teklif_paketleri.paket_id', '=', 'yazilimlar.id')
            ->select(
                'paket_teklif_paketleri.*',
                'yazilimlar.adi',
                'yazilimlar.seo as paket_seo',
                'yazilimlar.resim as paket_resim',
                'yazilimlar.kisa as paket_kisa'
            )
            ->where('paket_teklif_paketleri.teklif_id', $teklif->id)
            ->get();

        // Her paketin ek galeri gorselleri (webpaketresim) - rid'e gore grupla
        $galeri = [];
        try {
            if (Schema::hasTable('webpaketresim')) {
                $paketIdleri = $paketler->pluck('paket_id')->filter()->unique()->values()->all();
                if (!empty($paketIdleri)) {
                    $galeri = DB::table('webpaketresim')
                        ->whereIn('rid', $paketIdleri)
                        ->orderBy('id', 'asc')
                        ->get()
                        ->groupBy('rid')
                        ->toArray();
                }
            }
        } catch (\Throwable $e) {
            $galeri = [];
        }

        // Teklifin KENDİ galeri görselleri (kapak + paket_teklif_resimleri)
        $teklifGorselleri = [];
        if (!empty($teklif->resim)) {
            $teklifGorselleri[] = $teklif->resim;
        }
        try {
            if (Schema::hasTable('paket_teklif_resimleri')) {
                $ekstra = DB::table('paket_teklif_resimleri')
                    ->where('teklif_id', $teklif->id)
                    ->orderBy('sira', 'asc')
                    ->orderBy('id', 'asc')
                    ->pluck('resim')
                    ->all();
                $teklifGorselleri = array_merge($teklifGorselleri, $ekstra);
            }
        } catch (\Throwable $e) {
            // sessiz geç
        }

        // Kategori adı (varsa)
        $kategoriAdi = null;
        if (!empty($teklif->kategori) && Schema::hasTable('web_kategori')) {
            try {
                // Çoklu kategori: virgüllü id'leri ad'lara çevir
                $katIdler = array_filter(array_map('intval', explode(',', (string) $teklif->kategori)));
                if (!empty($katIdler)) {
                    $adlar = DB::table('web_kategori')->whereIn('id', $katIdler)->pluck('adi')->toArray();
                    $kategoriAdi = !empty($adlar) ? implode(', ', $adlar) : null;
                }
            } catch (\Throwable $e) { $kategoriAdi = null; }
        }

        $ayar = \App\Models\Ayar::first();

        return view('tema.teklif-detay', compact('teklif', 'paketler', 'galeri', 'teklifGorselleri', 'kategoriAdi', 'ayar'));
    }

    /**
     * Teklifi sil (satır kalemleriyle birlikte).
     * DELETE /paketler/teklif/{id}
     */
    public function sil($id)
    {
        $teklif = DB::table('paket_teklifleri')->where('id', $id)->first();
        if (!$teklif) {
            return redirect()->route('admin.paketler.ozel')->with('error', 'Teklif bulunamadı.');
        }

        try {
            // Önce satır kalemleri
            if (Schema::hasTable('paket_teklif_paketleri')) {
                DB::table('paket_teklif_paketleri')->where('teklif_id', $id)->delete();
            }
            if (Schema::hasTable('paket_teklif_resimleri')) {
                DB::table('paket_teklif_resimleri')->where('teklif_id', $id)->delete();
            }
            // Sonra teklif
            DB::table('paket_teklifleri')->where('id', $id)->delete();

            // Yüklenmiş dosya varsa sil (public diskte)
            if (!empty($teklif->dosya)) {
                try { \Illuminate\Support\Facades\Storage::disk('public')->delete($teklif->dosya); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            \Log::warning('Paket teklif silme hatası', ['id' => $id, 'err' => $e->getMessage()]);
            return redirect()->route('admin.paketler.ozel')->with('error', 'Teklif silinirken bir hata oluştu.');
        }

        return redirect()->route('admin.paketler.ozel')->with('success', 'Teklif silindi.');
    }

    /**
     * Paket teklifini DÜZENLEME formunda aç (teklif-olustur blade'i düzenleme modunda).
     * GET /admin/paketler/teklif/{id}/duzenle
     */
    public function duzenle($id)
    {
        $teklif = DB::table('paket_teklifleri')->where('id', $id)->first();
        if (!$teklif) {
            return redirect()->route('admin.paketler.ozel')->with('error', 'Teklif bulunamadı.');
        }

        $uyeler = Uye::orderBy('ad')->orderBy('soyad')->get();

        $paketler = DB::table('yazilimlar')
            ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($q) {
                $q->where('dil', 1);
            })
            ->orderBy('sira', 'asc')
            ->orderBy('id', 'desc')
            ->get(['id', 'adi', 'tutar']);

        $kategoriler = collect();
        try {
            if (Schema::hasTable('web_kategori')) {
                $kategoriler = DB::table('web_kategori')
                    ->when(Schema::hasColumn('web_kategori', 'durum'), fn($q) => $q->where('durum', 1))
                    ->when(Schema::hasColumn('web_kategori', 'dil'), fn($q) => $q->where('dil', 1))
                    ->when(Schema::hasColumn('web_kategori', 'sira'),
                        fn($q) => $q->orderBy('sira', 'asc'),
                        fn($q) => $q->orderBy('id', 'asc'))
                    ->get(['id', 'adi']);
            }
        } catch (\Throwable $e) {
            $kategoriler = collect();
        }

        // Bu teklifte seçili paketler ve özel fiyatları: [paket_id => birim_fiyat_tl]
        $seciliPaketler = [];
        if (Schema::hasTable('paket_teklif_paketleri')) {
            $kalemler = DB::table('paket_teklif_paketleri')->where('teklif_id', $id)->get();
            foreach ($kalemler as $k) {
                $seciliPaketler[(int) $k->paket_id] = [
                    'fiyat' => (float) ($k->birim_fiyat_tl ?? 0),
                    'ay'    => (int) ($k->adet ?? 1),
                ];
            }
        }

        // Bu teklifin kategorileri (virgüllü string → dizi)
        $seciliKategoriler = [];
        if (!empty($teklif->kategori)) {
            $seciliKategoriler = array_filter(array_map('intval', explode(',', (string) $teklif->kategori)));
        }

        $duzenleMod = true;

        return view('admin.paketler.teklif-olustur',
            compact('uyeler', 'paketler', 'kategoriler', 'teklif', 'seciliPaketler', 'seciliKategoriler', 'duzenleMod'));
    }

    /**
     * Paket teklifini GÜNCELLE.
     * POST /admin/paketler/teklif/{id}/guncelle
     * store() ile aynı toplam-hesaplama mantığı; insert yerine update.
     * - Dosya/resim: yenisi yüklenmezse eski korunur.
     * - Paket kalemleri: eskiler silinip yeniden eklenir.
     * - Mail/bildirim GÖNDERİLMEZ (düzenleme tekrar bildirim atmaz).
     */
    public function guncelle(Request $request, $id)
    {
        $mevcut = DB::table('paket_teklifleri')->where('id', $id)->first();
        if (!$mevcut) {
            return redirect()->route('admin.paketler.ozel')->with('error', 'Teklif bulunamadı.');
        }

        $validated = $request->validate([
            'uye_id'       => 'required|integer|exists:uyeler,id',
            'baslik'       => 'required|string|max:191',
            'paketler'     => 'nullable|array',
            'paketler.*'   => 'integer|exists:yazilimlar,id',
            'manuel_tutar' => 'nullable|numeric|min:0',
            'para_birimi'  => 'nullable|string|in:TL,TRY,USD,EUR,AED',
            'dosya'        => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'resim'        => 'nullable|image|max:10240',
            'kategori'     => 'nullable|array',
            'kategori.*'   => 'integer',
        ]);

        $uye = Uye::findOrFail($validated['uye_id']);
        $paraBirimi = strtoupper($request->input('para_birimi', 'TL'));

        $secilenIdler = array_values(array_filter((array) $request->input('paketler', [])));
        $paketKayitlari = collect();
        if (!empty($secilenIdler)) {
            $paketKayitlari = DB::table('yazilimlar')
                ->whereIn('id', $secilenIdler)
                ->get(['id', 'adi', 'tutar']);
        }

        $ozelFiyatlar = $request->input('paket_fiyat', []);
        $paketAylar = $request->input('paket_ay', []);  // [paket_id => ay]
        $manuelTutar = $request->filled('manuel_tutar') ? (float) $request->input('manuel_tutar') : 0;

        $toplamTl = $manuelTutar;
        foreach ($paketKayitlari as $paket) {
            $ozelFiyat = isset($ozelFiyatlar[$paket->id]) ? (float) $ozelFiyatlar[$paket->id] : null;
            $fiyat = $ozelFiyat !== null ? $ozelFiyat : (float) ($paket->tutar ?? 0);
            $ay = isset($paketAylar[$paket->id]) ? max(1, (int) $paketAylar[$paket->id]) : 1;
            $toplamTl += $fiyat * $ay;
        }

        if ($paketKayitlari->isEmpty() && $manuelTutar <= 0) {
            return back()
                ->with('error', 'Bir tutar girin veya en az bir paket seçin.')
                ->withInput();
        }

        if (in_array($paraBirimi, ['USD', 'EUR', 'AED'])) {
            $toplamParaBirimi = DovizKuruHelper::tldenCevir($toplamTl, $paraBirimi);
        } else {
            $paraBirimi = 'TL';
            $toplamParaBirimi = $toplamTl;
        }

        // Dosya: yenisi varsa değiştir (eskiyi sil), yoksa eskiyi koru
        $dosyaYolu = $mevcut->dosya ?? null;
        if ($request->hasFile('dosya')) {
            $yeni = $request->file('dosya')->store('teklifler', 'public');
            if ($yeni) {
                if (!empty($mevcut->dosya)) {
                    try { \Illuminate\Support\Facades\Storage::disk('public')->delete($mevcut->dosya); } catch (\Throwable $e) {}
                }
                $dosyaYolu = $yeni;
            }
        }

        // Kapak resmi: yenisi varsa değiştir, yoksa eskiyi koru
        $kapakResim = $mevcut->resim ?? null;
        if ($request->hasFile('resim')) {
            try {
                $uploadDir = public_path('tema/uploads/webpaketleri');
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
                $baseName = time() . '_teklif_' . Str::random(8);
                $kapakResim = $this->storeImageAsWebp($request->file('resim'), $uploadDir, $baseName);
            } catch (\Throwable $e) {
                \Log::warning('Teklif kapak güncelleme hatası: ' . $e->getMessage());
            }
        }

        $updateData = [
            'uye_id'                   => $uye->id,
            'baslik'                   => $validated['baslik'] ?: 'Paket Teklifi',
            'toplam_tl'                => $toplamTl,
            'para_birimi'              => $paraBirimi,
            'toplam_para_birimi_tutar' => $toplamParaBirimi,
            'aciklama'                 => $request->input('aciklama'),
            'dosya'                    => $dosyaYolu,
            'updated_at'               => now(),
        ];
        // Schema-aware opsiyonel kolonlar
        if (Schema::hasColumn('paket_teklifleri', 'resim')) {
            $updateData['resim'] = $kapakResim;
        }
        if (Schema::hasColumn('paket_teklifleri', 'kategori')) {
            if ($request->filled('kategori')) {
                $katDizi = array_values(array_filter((array) $request->input('kategori'), fn($v) => $v !== '' && $v !== null));
                $updateData['kategori'] = !empty($katDizi) ? implode(',', array_map('intval', $katDizi)) : null;
            } else {
                $updateData['kategori'] = null;
            }
        }
        if (Schema::hasColumn('paket_teklifleri', 'kisa')) {
            $updateData['kisa'] = $request->input('kisa');
        }
        if (Schema::hasColumn('paket_teklifleri', 'ozellik')) {
            $updateData['ozellik'] = $request->input('ozellik');
        }

        DB::table('paket_teklifleri')->where('id', $id)->update($updateData);

        // Paket kalemleri: eskileri sil, yenileri ekle
        if (Schema::hasTable('paket_teklif_paketleri')) {
            DB::table('paket_teklif_paketleri')->where('teklif_id', $id)->delete();
            foreach ($paketKayitlari as $paket) {
                $ozelFiyat = isset($ozelFiyatlar[$paket->id]) ? (float) $ozelFiyatlar[$paket->id] : null;
                $birimTl = $ozelFiyat !== null ? $ozelFiyat : (float) ($paket->tutar ?? 0);
                $adet = isset($paketAylar[$paket->id]) ? max(1, (int) $paketAylar[$paket->id]) : 1;
                DB::table('paket_teklif_paketleri')->insert([
                    'teklif_id'       => $id,
                    'paket_id'        => $paket->id,
                    'birim_fiyat_tl'  => $birimTl,
                    'adet'            => $adet,
                    'satir_toplam_tl' => $birimTl * $adet,
                ]);
            }
        }

        return redirect()
            ->route('admin.paketler.teklif.goruntule', $id)
            ->with('success', '✅ Teklif güncellendi.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uye_id'       => 'required|integer|exists:uyeler,id',
            'baslik'       => 'required|string|max:191',
            'paketler'     => 'nullable|array',
            'paketler.*'   => 'integer|exists:yazilimlar,id',
            'manuel_tutar' => 'nullable|numeric|min:0',
            'para_birimi'  => 'nullable|string|in:TL,TRY,USD,EUR,AED',
            'dosya'        => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'kategori'     => 'nullable|array',
            'kategori.*'   => 'integer',
            'resim'        => 'nullable|image|max:10240',
            'galeri'       => 'nullable|array|max:10',
            'galeri.*'     => 'image|max:10240',
        ]);

        $uye = Uye::findOrFail($validated['uye_id']);
        $paraBirimi = strtoupper($request->input('para_birimi', 'TL'));

        // Seçili paketler (opsiyonel)
        $secilenIdler = array_values(array_filter((array) $request->input('paketler', [])));
        $paketKayitlari = collect();
        if (!empty($secilenIdler)) {
            $paketKayitlari = DB::table('yazilimlar')
                ->whereIn('id', $secilenIdler)
                ->get(['id', 'adi', 'tutar']);
        }

        // Özel fiyatlar
        $ozelFiyatlar = $request->input('paket_fiyat', []);
        $paketAylar = $request->input('paket_ay', []);  // [paket_id => ay] (1-12), boş=1

        // Manuel tutar (paket seçilmeden de teklif oluşturulabilir)
        $manuelTutar = $request->filled('manuel_tutar') ? (float) $request->input('manuel_tutar') : 0;

        // TL toplamı = manuel tutar + seçili paketlerin (özel/standart fiyat × ay) toplamı
        $toplamTl = $manuelTutar;
        foreach ($paketKayitlari as $paket) {
            $ozelFiyat = isset($ozelFiyatlar[$paket->id]) ? (float) $ozelFiyatlar[$paket->id] : null;
            $fiyat = $ozelFiyat !== null ? $ozelFiyat : (float) ($paket->tutar ?? 0);
            $ay = isset($paketAylar[$paket->id]) ? max(1, (int) $paketAylar[$paket->id]) : 1;
            $toplamTl += $fiyat * $ay;
        }

        // En az bir kaynak olmalı: ya manuel tutar ya da en az 1 paket
        if ($paketKayitlari->isEmpty() && $manuelTutar <= 0) {
            return back()
                ->with('error', 'Bir tutar girin veya en az bir paket seçin.')
                ->withInput();
        }

        // Gösterim için seçilen para biriminde toplam
        if (in_array($paraBirimi, ['USD', 'EUR', 'AED'])) {
            $toplamParaBirimi = DovizKuruHelper::tldenCevir($toplamTl, $paraBirimi);
        } else {
            $paraBirimi = 'TL';
            $toplamParaBirimi = $toplamTl;
        }

        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $dosyaYolu = $request->file('dosya')->store('teklifler', 'public');
        }

        // Kapak görseli (webp) — paket sistemiyle aynı klasör/yöntem
        $kapakResim = null;
        if ($request->hasFile('resim')) {
            try {
                $uploadDir = public_path('tema/uploads/webpaketleri');
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
                $baseName = time() . '_teklif_' . Str::random(8);
                $kapakResim = $this->storeImageAsWebp($request->file('resim'), $uploadDir, $baseName);
            } catch (\Throwable $e) {
                \Log::warning('Teklif kapak yukleme hatasi: ' . $e->getMessage());
            }
        }

        $teklifToken = \Illuminate\Support\Str::random(48);
        $teklifBaslik = $request->input('baslik') ?: 'Paket Teklifi';

        $insertTeklif = [
            'uye_id' => $uye->id,
            'baslik' => $teklifBaslik,
            'toplam_tl' => $toplamTl,
            'para_birimi' => $paraBirimi,
            'toplam_para_birimi_tutar' => $toplamParaBirimi,
            'durum' => 'beklemede',
            'aciklama' => $request->input('aciklama'),
            'dosya' => $dosyaYolu,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('paket_teklifleri', 'token')) {
            $insertTeklif['token'] = $teklifToken;
        }
        // Zengin alanlar (kolon varsa ekle — schema-aware)
        if (Schema::hasColumn('paket_teklifleri', 'resim') && $kapakResim) {
            $insertTeklif['resim'] = $kapakResim;
        }
        if (Schema::hasColumn('paket_teklifleri', 'kategori') && $request->filled('kategori')) {
            $katDizi = array_values(array_filter((array) $request->input('kategori'), fn($v) => $v !== '' && $v !== null));
            $insertTeklif['kategori'] = !empty($katDizi) ? implode(',', array_map('intval', $katDizi)) : null;
        }
        if (Schema::hasColumn('paket_teklifleri', 'kisa') && $request->filled('kisa')) {
            $insertTeklif['kisa'] = $request->input('kisa');
        }
        if (Schema::hasColumn('paket_teklifleri', 'ozellik') && $request->filled('ozellik')) {
            $insertTeklif['ozellik'] = $request->input('ozellik');
        }
        if (Schema::hasColumn('paket_teklifleri', 'aciklama_html') && $request->filled('aciklama_html')) {
            $insertTeklif['aciklama_html'] = $request->input('aciklama_html');
        }

        $teklifId = DB::table('paket_teklifleri')->insertGetId($insertTeklif);

        // SEO slug üret (başlık + id → benzersiz, temiz link)
        $teklifSlug = null;
        if (Schema::hasColumn('paket_teklifleri', 'seo')) {
            $teklifSlug = \Illuminate\Support\Str::slug($teklifBaslik);
            if ($teklifSlug === '') { $teklifSlug = 'teklif'; }
            $teklifSlug .= '-' . $teklifId;
            DB::table('paket_teklifleri')->where('id', $teklifId)->update(['seo' => $teklifSlug]);
        }

        // Paket satır kalemleri (varsa) — adet = seçilen ay (süre çarpanı)
        $mailKalemleri = [];
        foreach ($paketKayitlari as $paket) {
            $ozelFiyat = isset($ozelFiyatlar[$paket->id]) ? (float) $ozelFiyatlar[$paket->id] : null;
            $birimTl = $ozelFiyat !== null ? $ozelFiyat : (float) ($paket->tutar ?? 0);
            $adet = isset($paketAylar[$paket->id]) ? max(1, (int) $paketAylar[$paket->id]) : 1;
            DB::table('paket_teklif_paketleri')->insert([
                'teklif_id' => $teklifId,
                'paket_id' => $paket->id,
                'birim_fiyat_tl' => $birimTl,
                'adet' => $adet,
                'satir_toplam_tl' => $birimTl * $adet,
            ]);
            $mailKalemleri[] = (object) [
                'adi'        => $paket->adi ?? 'Paket',
                'birim'      => $birimTl,
                'ay'         => $adet,
                'satir'      => $birimTl * $adet,
            ];
        }

        // Galeri görselleri (teklife özel) — paket_teklif_resimleri
        if ($request->hasFile('galeri') && Schema::hasTable('paket_teklif_resimleri')) {
            $galeriDosyalari = $request->file('galeri');
            if (is_array($galeriDosyalari)) {
                $uploadDir = public_path('tema/uploads/webpaketleri');
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
                $sira = 0;
                foreach ($galeriDosyalari as $i => $gorsel) {
                    if (!$gorsel || !$gorsel->isValid()) { continue; }
                    $ext = strtolower($gorsel->getClientOriginalExtension());
                    if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg'], true)) { continue; }
                    if ($gorsel->getSize() > 10 * 1024 * 1024) { continue; }
                    try {
                        $baseName = time() . '_teklif' . $teklifId . '_' . $i . '_' . Str::random(6);
                        $dosyaAdi = $this->storeImageAsWebp($gorsel, $uploadDir, $baseName);
                        DB::table('paket_teklif_resimleri')->insert([
                            'teklif_id' => $teklifId,
                            'resim' => $dosyaAdi,
                            'sira' => $sira++,
                            'created_at' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        \Log::warning('Teklif galeri yukleme hatasi: ' . $e->getMessage());
                        continue;
                    }
                }
            }
        }

        // Müşteriye mail gönder (Notification sistemi ile)
        try {
            Notification::send($uye, new PaketTeklifEmail([
                'uye' => $uye,
                'teklif_id' => $teklifId,
                'toplam_tl' => $toplamTl,
                'para_birimi' => $paraBirimi,
                'toplam_para_birimi' => $toplamParaBirimi,
                'paketler' => $mailKalemleri,
            ]));
        } catch (\Throwable $e) {
            \Log::warning('PaketTeklifEmail gonderilemedi: ' . $e->getMessage());
        }

        // Müşteriye panel bildirimi (zil)
        try {
            if (function_exists('uye_bildirim_gonder')) {
                $link = null;
                if (!empty($teklifSlug) && \Illuminate\Support\Facades\Route::has('teklif.detay.slug')) {
                    $link = route('teklif.detay.slug', $teklifSlug);
                } elseif (Schema::hasColumn('paket_teklifleri', 'token') && \Illuminate\Support\Facades\Route::has('teklif.detay.public')) {
                    $link = route('teklif.detay.public', $teklifToken);
                } elseif (\Illuminate\Support\Facades\Route::has('tekliflerim')) {
                    $link = route('tekliflerim');
                }
                uye_bildirim_gonder(
                    (int) $uye->id,
                    'Size Ozel Teklif: ' . $teklifBaslik,
                    'Hesabiniza ozel bir paket teklifi tanimlandi. Incelemek icin tiklayin.',
                    'info',
                    $link,
                    'mdi-file-document'
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Paket teklif musteri bildirimi: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.paketler.index')
            ->with('success', 'Müşteriye özel teklif oluşturuldu ve mail gönderimi denendi.');
    }

    /**
     * Görseli mümkünse WEBP olarak kaydeder (PaketController ile aynı mantık).
     */
    protected function storeImageAsWebp(UploadedFile $file, string $uploadDir, string $baseName): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'svg') {
            $filename = $baseName . '.svg';
            $file->move($uploadDir, $filename);
            @chmod($uploadDir . DIRECTORY_SEPARATOR . $filename, 0644);
            return $filename;
        }

        $canConvert = function_exists('imagewebp') && in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
        $targetFilename = $baseName . '.webp';
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetFilename;

        if ($canConvert) {
            $sourcePath = $file->getRealPath();
            $image = null;
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = @imagecreatefromjpeg($sourcePath);
                    break;
                case 'png':
                    $image = @imagecreatefrompng($sourcePath);
                    break;
                case 'gif':
                    $image = @imagecreatefromgif($sourcePath);
                    break;
                case 'webp':
                    $image = @imagecreatefromwebp($sourcePath);
                    break;
            }
            if ($image) {
                if (function_exists('imagepalettetotruecolor')) {
                    @imagepalettetotruecolor($image);
                }
                @imagealphablending($image, true);
                @imagesavealpha($image, true);
                if (@imagewebp($image, $targetPath, 85)) {
                    @imagedestroy($image);
                    @chmod($targetPath, 0644);
                    return $targetFilename;
                }
                @imagedestroy($image);
            }
        }

        $fallbackFilename = $baseName . '.' . $extension;
        $file->move($uploadDir, $fallbackFilename);
        @chmod($uploadDir . DIRECTORY_SEPARATOR . $fallbackFilename, 0644);
        return $fallbackFilename;
    }
}