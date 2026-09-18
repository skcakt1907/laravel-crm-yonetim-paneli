<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SpreadsheetTemplates;
use App\Http\Controllers\Controller;
use App\Models\Spreadsheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpreadsheetController extends Controller
{
    /**
     * Liste sayfası
     */
    public function index()
    {
        $adminId = session('admin_id');
        $spreadsheets = Spreadsheet::accessibleBy($adminId)->get();

        return view('admin.tablolar.index', compact('spreadsheets'));
    }

    /**
     * Yeni oluşturma sayfası (şablon seçimi ile)
     */
    public function create()
    {
        $adminler = DB::table('yoneticiler')
            ->select('id', 'adi', 'email', 'eposta')
            ->where('durum', 1)
            ->orderBy('adi')
            ->get();

        // Şablon listesi (4 hazır + boş + hepsi)
        $sablonlar = SpreadsheetTemplates::list();

        return view('admin.tablolar.create', compact('adminler', 'sablonlar'));
    }

    /**
     * Yeni kaydet
     */
    public function store(Request $request)
    {
        $request->validate([
            'ad'       => 'required|string|max:150',
            'aciklama' => 'nullable|string',
            'ikon'     => 'nullable|string|max:10',
            'sablon'   => 'nullable|string|max:50',
        ]);

        try {
            // Şablonu yükle (boş veya önceden hazırlanmış)
            $sablon = $request->input('sablon', 'bos');
            $sheets = SpreadsheetTemplates::getTemplate($sablon);
            $veri = json_encode($sheets, JSON_UNESCAPED_UNICODE);

            $sp = Spreadsheet::create([
                'ad'              => $request->ad,
                'aciklama'        => $request->aciklama,
                'ikon'            => $request->ikon ?: '📊',
                'veri'            => $veri,
                'olusturan_id'    => session('admin_id'),
                'yetkili_ids'     => $request->yetkili_ids ?? [],
                'herkes_gorur'    => $request->boolean('herkes_gorur'),
                'otomatik_kaydet' => true,
            ]);

            $mesaj = '✅ Tablo oluşturuldu, şimdi düzenleyebilirsin.';
            if ($sablon !== 'bos') {
                $sablonInfo = SpreadsheetTemplates::list()[$sablon] ?? null;
                if ($sablonInfo) {
                    $mesaj = "✅ \"{$sablonInfo['ad']}\" şablonu yüklendi. Hücrelere veri girmeye başlayabilirsin.";
                }
            }

            // Yeni tablo açılınca DOĞRUDAN düzenleme (Luckysheet) ekranına git
            return redirect()->route('admin.tablolar.show', $sp->id)
                ->with('success', $mesaj);

        } catch (\Exception $e) {
            Log::error('Spreadsheet oluşturma hatası: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Hata: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Tablo Editörü (Luckysheet ekranı) — show
     *
     * ÖNEMLİ: Bu metot artık 'show' view'unu dönüyor (Luckysheet).
     * Daha önce yanlışlıkla 'edit' view'una yönlendiriliyordu.
     */
    public function show($id)
    {
        $sp = Spreadsheet::findOrFail($id);

        if (!$sp->canAccess(session('admin_id'))) {
            abort(403, 'Bu tabloya erişim yetkiniz yok.');
        }

        return view('admin.tablolar.show', compact('sp'));
    }

    /**
     * Tablo Ayarları (ad, açıklama, ikon, yetkiler) — edit
     */
    public function edit($id)
    {
        $sp = Spreadsheet::findOrFail($id);

        if (!$sp->canAccess(session('admin_id'))) {
            abort(403, 'Bu tabloya erişim yetkiniz yok.');
        }

        $adminler = DB::table('yoneticiler')
            ->select('id', 'adi', 'email', 'eposta')
            ->where('durum', 1)
            ->orderBy('adi')
            ->get();

        $yetkiliIds = is_array($sp->yetkili_ids)
            ? $sp->yetkili_ids
            : (json_decode($sp->yetkili_ids, true) ?: []);

        return view('admin.tablolar.edit', compact('sp', 'adminler', 'yetkiliIds'));
    }

    /**
     * AJAX: Luckysheet verisini kaydet
     */
    public function save(Request $request, $id)
    {
        $sp = Spreadsheet::findOrFail($id);

        if (!$sp->canAccess(session('admin_id'))) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        try {
            $yeniVeri = $request->input('veri');

            // ── VERİ KAYBI KORUMASI ──
            // Luckysheet düzgün yüklenmediyse boş/çok küçük veri gönderir.
            // Mevcut dolu veriyi bu boş veriyle EZMEYİ reddet.
            $yeniUzunluk   = is_string($yeniVeri) ? strlen($yeniVeri) : 0;
            $mevcutUzunluk = is_string($sp->veri) ? strlen($sp->veri) : 0;

            // Yeni veri boş veya geçersizse → reddet
            if ($yeniUzunluk < 20) {
                Log::warning('Spreadsheet bos veri kaydetme engellendi', [
                    'id' => $id, 'yeni_uzunluk' => $yeniUzunluk, 'mevcut_uzunluk' => $mevcutUzunluk,
                ]);
                return response()->json([
                    'error' => 'Boş veri kaydedilmedi (mevcut veriniz korundu). Sayfayı yenileyip tekrar deneyin.',
                ], 422);
            }

            // Geçerli JSON mu kontrol et
            $decoded = json_decode($yeniVeri, true);
            if (!is_array($decoded) || empty($decoded)) {
                Log::warning('Spreadsheet gecersiz JSON kaydetme engellendi', ['id' => $id]);
                return response()->json([
                    'error' => 'Geçersiz veri kaydedilmedi (mevcut veriniz korundu).',
                ], 422);
            }

            // Mevcut veri büyük (>5000) ama yeni veri onun %20'sinden küçükse → ani veri kaybı şüphesi, reddet
            if ($mevcutUzunluk > 5000 && $yeniUzunluk < ($mevcutUzunluk * 0.2)) {
                Log::warning('Spreadsheet ani veri kaybi engellendi', [
                    'id' => $id, 'yeni' => $yeniUzunluk, 'mevcut' => $mevcutUzunluk,
                ]);
                return response()->json([
                    'error' => 'Veride ani büyük küçülme algılandı, kaydetme güvenlik için durduruldu. Verileriniz korundu. Sayfayı yenileyin.',
                ], 422);
            }

            $sp->update([
                'veri'            => $yeniVeri,
                'son_kaydeden_id' => session('admin_id'),
            ]);

            return response()->json([
                'success'        => true,
                'kaydedildi_at'  => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Spreadsheet kaydetme hatası: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Otomatik kaydet tercih güncelle
     */
    public function toggleAutoSave(Request $request, $id)
    {
        $sp = Spreadsheet::findOrFail($id);

        if (!$sp->canAccess(session('admin_id'))) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        $sp->update(['otomatik_kaydet' => $request->boolean('otomatik_kaydet')]);

        return response()->json(['success' => true, 'otomatik_kaydet' => $sp->otomatik_kaydet]);
    }

    /**
     * Spreadsheet meta güncelle (ad/aciklama/ikon/yetkiler)
     */
    public function update(Request $request, $id)
    {
        $sp = Spreadsheet::findOrFail($id);

        if ($sp->olusturan_id != session('admin_id') && session('admin_rol') != 1) {
            return redirect()->back()->with('error', 'Bu tabloyu düzenleme yetkin yok.');
        }

        $sp->update([
            'ad'           => $request->ad,
            'aciklama'     => $request->aciklama,
            'ikon'         => $request->ikon ?: '📊',
            'yetkili_ids'  => $request->yetkili_ids ?? [],
            'herkes_gorur' => $request->boolean('herkes_gorur'),
        ]);

        return redirect()->route('admin.tablolar.show', $sp->id)
            ->with('success', '✅ Tablo bilgileri güncellendi.');
    }

    /**
     * Spreadsheet sil
     */
    public function destroy($id)
    {
        $sp = Spreadsheet::findOrFail($id);

        if ($sp->olusturan_id != session('admin_id') && session('admin_rol') != 1) {
            return redirect()->back()->with('error', 'Yetkisiz.');
        }

        $sp->delete();

        return redirect()->route('admin.tablolar.index')->with('success', 'Tablo silindi.');
    }
}