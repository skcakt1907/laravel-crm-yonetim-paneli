<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TEMİZLİK KONTROL (17.08.2026)
 *
 * Sabit bir kontrol listesi (temizlik_maddeler) tanımlanır; her temizlik
 * için o listeden bir kontrol açılır (temizlik_kontroller) ve maddeler
 * tek tek işaretlenir (temizlik_kontrol_detay). İşaretlenince o maddenin
 * yapılma tarihi ve yapan kişi kaydedilir.
 */
class TemizlikKontrolController extends Controller
{
    /** Kontrol listesi (geçmiş kayıtlar) */
    public function index(Request $request)
    {
        if (!Schema::hasTable('temizlik_kontroller')) {
            return view('admin.temizlik.index', ['kontroller' => collect(), 'tabloYok' => true, 'maddeSayisi' => 0]);
        }

        /*
         * NOT (18.08.2026): Önceki hâli LEFT JOIN + GROUP BY k.id kullanıyordu.
         * Canlı MySQL ONLY_FULL_GROUP_BY (strict) modunda çalıştığı için
         * paginate()'in ürettiği COUNT alt sorgusunda "k.baslik GROUP BY'da
         * değil" hatası veriyordu — yerelde strict kapalı olduğu için
         * fark edilmemişti. Korelasyonlu alt sorgularla GROUP BY tamamen
         * kaldırıldı; hem strict modda güvenli hem de daha basit.
         */
        $kontroller = DB::table('temizlik_kontroller as k')
            ->select(
                'k.*',
                DB::raw('(SELECT COUNT(*) FROM temizlik_kontrol_detay WHERE kontrol_id = k.id) as madde_sayisi'),
                DB::raw('(SELECT COUNT(*) FROM temizlik_kontrol_detay WHERE kontrol_id = k.id AND yapildi = 1) as yapilan_sayisi')
            )
            ->orderByDesc('k.kontrol_tarihi')
            ->orderByDesc('k.id')
            ->paginate(20);

        $maddeSayisi = Schema::hasTable('temizlik_maddeler')
            ? DB::table('temizlik_maddeler')->where('aktif', 1)->count()
            : 0;

        return view('admin.temizlik.index', compact('kontroller', 'maddeSayisi'));
    }

    /** Yeni kontrol başlat — aktif maddelerden satırlar üretir */
    public function baslat(Request $request)
    {
        $maddeler = DB::table('temizlik_maddeler')->where('aktif', 1)->orderBy('sira')->orderBy('id')->get();

        if ($maddeler->isEmpty()) {
            return redirect()->route('admin.temizlik.maddeler')
                ->with('error', 'Önce kontrol listesine madde eklemelisiniz.');
        }

        $tarih = $request->input('kontrol_tarihi') ?: now()->toDateString();

        $kontrolId = DB::table('temizlik_kontroller')->insertGetId([
            'baslik'         => $request->input('baslik') ?: null,
            'kontrol_tarihi' => $tarih,
            'olusturan_id'   => session('admin_id'),
            'olusturan_adi'  => session('admin_adi') ?: session('admin_ad') ?: 'Sistem',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $satirlar = $maddeler->map(fn ($m) => [
            'kontrol_id'   => $kontrolId,
            'madde_id'     => $m->id,
            'madde_baslik' => $m->baslik,
            'yapildi'      => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ])->all();

        DB::table('temizlik_kontrol_detay')->insert($satirlar);

        return redirect()->route('admin.temizlik.goster', $kontrolId)
            ->with('success', 'Yeni temizlik kontrolü başlatıldı.');
    }

    /** Tek kontrolün detayı — checkbox'lar burada */
    public function goster(int $id)
    {
        $kontrol = DB::table('temizlik_kontroller')->where('id', $id)->first();
        abort_if(!$kontrol, 404);

        $detaylar = DB::table('temizlik_kontrol_detay')
            ->where('kontrol_id', $id)
            ->orderBy('id')
            ->get();

        return view('admin.temizlik.goster', compact('kontrol', 'detaylar'));
    }

    /** Bir maddeyi işaretle / işareti kaldır (AJAX) */
    public function isaretle(Request $request, int $id, int $detayId)
    {
        $detay = DB::table('temizlik_kontrol_detay')
            ->where('id', $detayId)->where('kontrol_id', $id)->first();

        if (!$detay) {
            return response()->json(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
        }

        $yeni = !((int) $detay->yapildi === 1);

        DB::table('temizlik_kontrol_detay')->where('id', $detayId)->update([
            'yapildi'        => $yeni ? 1 : 0,
            'yapilma_tarihi' => $yeni ? now() : null,
            'yapan_id'       => $yeni ? session('admin_id') : null,
            'yapan_adi'      => $yeni ? (session('admin_adi') ?: session('admin_ad') ?: 'Sistem') : null,
            'updated_at'     => now(),
        ]);

        // Hepsi işaretlendiyse kontrolü tamamlanmış say
        $kalan = DB::table('temizlik_kontrol_detay')
            ->where('kontrol_id', $id)->where('yapildi', 0)->count();

        DB::table('temizlik_kontroller')->where('id', $id)->update([
            'tamamlanma_tarihi' => $kalan === 0 ? now() : null,
            'updated_at'        => now(),
        ]);

        return response()->json([
            'success'    => true,
            'yapildi'    => $yeni,
            'tarih'      => $yeni ? now()->format('d.m.Y H:i') : null,
            'yapan'      => $yeni ? (session('admin_adi') ?: session('admin_ad') ?: 'Sistem') : null,
            'kalan'      => $kalan,
            'tamamlandi' => $kalan === 0,
        ]);
    }

    /** Kontrol notunu kaydet */
    public function notKaydet(Request $request, int $id)
    {
        $request->validate(['not' => 'nullable|string|max:2000']);

        DB::table('temizlik_kontroller')->where('id', $id)->update([
            'not'        => $request->input('not'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Not kaydedildi.');
    }

    /** Kontrolü sil */
    public function sil(int $id)
    {
        DB::table('temizlik_kontrol_detay')->where('kontrol_id', $id)->delete();
        DB::table('temizlik_kontroller')->where('id', $id)->delete();

        return redirect()->route('admin.temizlik.index')->with('success', 'Kontrol kaydı silindi.');
    }

    /* ═══════════════ KONTROL LİSTESİ (MADDELER) ═══════════════ */

    public function maddeler()
    {
        $maddeler = Schema::hasTable('temizlik_maddeler')
            ? DB::table('temizlik_maddeler')->orderBy('sira')->orderBy('id')->get()
            : collect();

        return view('admin.temizlik.maddeler', compact('maddeler'));
    }

    public function maddeEkle(Request $request)
    {
        $request->validate([
            'baslik'   => 'required|string|max:255',
            'aciklama' => 'nullable|string|max:500',
        ]);

        $sira = (int) (DB::table('temizlik_maddeler')->max('sira') ?? 0) + 1;

        DB::table('temizlik_maddeler')->insert([
            'baslik'       => $request->input('baslik'),
            'aciklama'     => $request->input('aciklama'),
            'sira'         => $sira,
            'aktif'        => 1,
            'olusturan_id' => session('admin_id'),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('success', 'Madde eklendi.');
    }

    public function maddeGuncelle(Request $request, int $id)
    {
        $request->validate([
            'baslik'   => 'required|string|max:255',
            'aciklama' => 'nullable|string|max:500',
        ]);

        DB::table('temizlik_maddeler')->where('id', $id)->update([
            'baslik'     => $request->input('baslik'),
            'aciklama'   => $request->input('aciklama'),
            'aktif'      => $request->boolean('aktif') ? 1 : 0,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Madde güncellendi.');
    }

    /**
     * Maddeyi sil.
     * NOT: Geçmiş kontrollerdeki kayıtlara DOKUNULMAZ — orada madde başlığı
     * kopya olarak saklandığı için eski kayıtlar okunabilir kalır.
     */
    public function maddeSil(int $id)
    {
        DB::table('temizlik_maddeler')->where('id', $id)->delete();

        return back()->with('success', 'Madde silindi. Geçmiş kontrol kayıtları korundu.');
    }
}
