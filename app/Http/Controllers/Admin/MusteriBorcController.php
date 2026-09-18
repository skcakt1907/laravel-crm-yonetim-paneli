<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Müşteri Borç Takip (cari) — Borç Takip modülünün müşteri bazlı sürümü.
 * Her kayıt tip='borc' (+) veya tip='odeme' (−).
 * Bir müşterinin kalan borcu = toplam borç - toplam ödeme.
 * Müşteri kaynağı: crm_customers (CRM Müşteriler).
 */
class MusteriBorcController extends Controller
{
    /** Liste — tüm müşterilerin cari özeti + genel toplam. */
    public function index(Request $request)
    {
        if (!Schema::hasTable('musteri_borc_takip')) {
            return view('admin.musteri-borc.index', [
                'kurulumGerekli' => true,
                'musteriler'     => collect(),
                'tumMusteriler'  => collect(),
                'genelToplam'    => ['borc' => 0, 'odeme' => 0, 'net' => 0],
            ]);
        }

        // Ekleme modalındaki müşteri seçimi için tüm müşteriler (ad + ünvan)
        $tumMusteriler = DB::table('crm_customers')
            ->select('id', 'adi', 'unvan')
            ->orderBy('adi')
            ->get();

        $q = trim((string) $request->get('q', ''));

        // Müşteri başına borç/ödeme toplamları
        $toplamlar = DB::table('musteri_borc_takip')
            ->select('musteri_id',
                DB::raw("SUM(CASE WHEN tip='borc' THEN tutar ELSE 0 END) as borc"),
                DB::raw("SUM(CASE WHEN tip='odeme' THEN tutar ELSE 0 END) as odeme"),
                DB::raw("COUNT(*) as adet"))
            ->groupBy('musteri_id')
            ->get()
            ->keyBy('musteri_id');

        $musterilerQ = DB::table('crm_customers')
            ->select('id', 'adi', 'unvan', 'email', 'telefon');

        if ($q !== '') {
            $musterilerQ->where(function ($w) use ($q) {
                $w->where('adi', 'like', "%{$q}%")
                  ->orWhere('unvan', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('telefon', 'like', "%{$q}%");
            });
        }

        // Sadece hareketi olan müşterileri öne çıkar; arama yoksa hareketi olanlar listelenir.
        $hareketliIdler = $toplamlar->keys()->all();
        if ($q === '') {
            $musterilerQ->whereIn('id', $hareketliIdler ?: [0]);
        }

        $musteriler = $musterilerQ->orderBy('adi')->limit(500)->get()->map(function ($m) use ($toplamlar) {
            $t = $toplamlar->get($m->id);
            $borc  = (float) ($t->borc ?? 0);
            $odeme = (float) ($t->odeme ?? 0);
            $m->borc  = $borc;
            $m->odeme = $odeme;
            $m->net   = $borc - $odeme;
            $m->adet  = (int) ($t->adet ?? 0);
            return $m;
        });

        $genelBorc  = (float) $toplamlar->sum('borc');
        $genelOdeme = (float) $toplamlar->sum('odeme');

        return view('admin.musteri-borc.index', [
            'kurulumGerekli' => false,
            'musteriler'     => $musteriler,
            'tumMusteriler'  => $tumMusteriler,
            'q'              => $q,
            'genelToplam'    => [
                'borc'  => $genelBorc,
                'odeme' => $genelOdeme,
                'net'   => $genelBorc - $genelOdeme,
            ],
        ]);
    }

    /** Tek müşterinin cari ekstresi (borç/ödeme listesi + form). */
    public function goster($musteri)
    {
        $musteri = DB::table('crm_customers')->where('id', $musteri)->first();
        if (!$musteri) {
            return redirect()->route('admin.musteri-borc.index')
                ->with('error', 'Müşteri bulunamadı.');
        }

        $kayitlar = DB::table('musteri_borc_takip')
            ->where('musteri_id', $musteri->id)
            ->orderByDesc('tarih')
            ->orderByDesc('id')
            ->get();

        $borc  = (float) $kayitlar->where('tip', 'borc')->sum('tutar');
        $odeme = (float) $kayitlar->where('tip', 'odeme')->sum('tutar');

        return view('admin.musteri-borc.detay', [
            'musteri'  => $musteri,
            'kayitlar' => $kayitlar,
            'ozet'     => [
                'borc'  => $borc,
                'odeme' => $odeme,
                'net'   => $borc - $odeme,
                'adet'  => $kayitlar->count(),
            ],
        ]);
    }

    /** Yeni borç/ödeme kaydı ekle. */
    public function store(Request $request)
    {
        $request->validate([
            'musteri_id' => 'required|integer|exists:crm_customers,id',
            'tip'        => 'required|string|in:borc,odeme',
            'baslik'     => 'required|string|max:200',
            'tutar'      => 'required|numeric|min:0',
            'tarih'      => 'required|date',
            'aciklama'   => 'nullable|string',
        ]);

        DB::table('musteri_borc_takip')->insert([
            'musteri_id'   => (int) $request->musteri_id,
            'tip'          => $request->tip,
            'baslik'       => $request->baslik,
            'aciklama'     => $request->aciklama,
            'tutar'        => (float) $request->tutar,
            'tarih'        => $request->tarih,
            'olusturan_id' => session('admin_id'),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $tipAd = $request->tip === 'borc' ? 'Borç' : 'Ödeme';
        return redirect()->route('admin.musteri-borc.goster', $request->musteri_id)
            ->with('success', $tipAd . ' kaydı eklendi.');
    }

    /** Kaydı düzenle. */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tip'      => 'required|string|in:borc,odeme',
            'baslik'   => 'required|string|max:200',
            'tutar'    => 'required|numeric|min:0',
            'tarih'    => 'required|date',
            'aciklama' => 'nullable|string',
        ]);

        $kayit = DB::table('musteri_borc_takip')->where('id', $id)->first();
        if (!$kayit) {
            return redirect()->route('admin.musteri-borc.index')
                ->with('error', 'Kayıt bulunamadı.');
        }

        DB::table('musteri_borc_takip')->where('id', $id)->update([
            'tip'        => $request->tip,
            'baslik'     => $request->baslik,
            'aciklama'   => $request->aciklama,
            'tutar'      => (float) $request->tutar,
            'tarih'      => $request->tarih,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.musteri-borc.goster', $kayit->musteri_id)
            ->with('success', 'Kayıt güncellendi.');
    }

    /** Kaydı sil. */
    public function destroy($id)
    {
        $kayit = DB::table('musteri_borc_takip')->where('id', $id)->first();
        DB::table('musteri_borc_takip')->where('id', $id)->delete();

        $hedef = $kayit
            ? redirect()->route('admin.musteri-borc.goster', $kayit->musteri_id)
            : redirect()->route('admin.musteri-borc.index');

        return $hedef->with('success', 'Kayıt silindi.');
    }
}
