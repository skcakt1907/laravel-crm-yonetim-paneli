<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminBildirimController extends Controller
{
    /**
     * Bildirim listesi
     * NOT: admin_bildirimler tablosu ortak (admin_id kolonu yok),
     * tüm adminler aynı bildirimleri görür.
     */
    public function index(Request $request)
    {
        if (!Schema::hasTable('admin_bildirimler')) {
            return view('admin.bildirimler.index', [
                'bildirimler' => collect(),
                'okunmamisSayi' => 0,
                'tabloYok' => true,
            ]);
        }

        $filtre = $request->query('filtre', 'tumu');

        $aktifAdminId = session('admin_id');
        $kisiselFiltre = function ($q) use ($aktifAdminId) {
            // yonetici_id kolonu varsa: bu admine ait VEYA genel (NULL) bildirimler.
            if (Schema::hasColumn('admin_bildirimler', 'yonetici_id')) {
                $q->where(function ($w) use ($aktifAdminId) {
                    $w->where('yonetici_id', $aktifAdminId)
                      ->orWhereNull('yonetici_id');
                });
            }
        };

        $query = DB::table('admin_bildirimler');
        $kisiselFiltre($query);

        if ($filtre === 'okunmamis') {
            $query->where('okundu', 0);
        } elseif ($filtre === 'okunmus') {
            $query->where('okundu', 1);
        }

        $bildirimler = $query->orderByDesc('id')->paginate(30);

        // Her bildirime yönlendirme linki ekle (sayfada tıklanabilir olsun)
        $bildirimler->getCollection()->transform(function ($b) {
            $b->link = $this->bildirimLinkUret($b->ilgili_tablo ?? null, $b->ilgili_id ?? null, $b->tip ?? null);
            return $b;
        });

        $sayiQuery = DB::table('admin_bildirimler')->where('okundu', 0);
        $kisiselFiltre($sayiQuery);
        $okunmamisSayi = $sayiQuery->count();

        return view('admin.bildirimler.index', compact('bildirimler', 'okunmamisSayi', 'filtre'));
    }


    /**
     * Tek bildirimi okundu yap
     * AJAX (zil dropdown'u) -> JSON doner; normal form -> back().
     */
    public function okundu(Request $request, $id)
    {
        if (!Schema::hasTable('admin_bildirimler')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false]);
            }
            return back();
        }

        $updateData = ['okundu' => 1];
        if (Schema::hasColumn('admin_bildirimler', 'okunma_tarihi')) {
            $updateData['okunma_tarihi'] = now();
        }
        if (Schema::hasColumn('admin_bildirimler', 'updated_at')) {
            $updateData['updated_at'] = now();
        }

        DB::table('admin_bildirimler')
            ->where('id', $id)
            ->update($updateData);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Bildirim okundu olarak işaretlendi.');
    }


    /**
     * Hepsini okundu yap
     * AJAX (zil dropdown'u) -> JSON doner; normal form -> back().
     * index/sayim ile ayni kisisel filtre: yonetici_id kolonu varsa
     * sadece bu admine ait + genel (NULL) bildirimler isaretlenir.
     */
    public function hepsiniOku(Request $request)
    {
        if (!Schema::hasTable('admin_bildirimler')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'okunan' => 0]);
            }
            return back();
        }

        $updateData = ['okundu' => 1];
        if (Schema::hasColumn('admin_bildirimler', 'okunma_tarihi')) {
            $updateData['okunma_tarihi'] = now();
        }
        if (Schema::hasColumn('admin_bildirimler', 'updated_at')) {
            $updateData['updated_at'] = now();
        }

        $query = DB::table('admin_bildirimler')->where('okundu', 0);

        if (Schema::hasColumn('admin_bildirimler', 'yonetici_id')) {
            $aktifAdminId = session('admin_id');
            $query->where(function ($w) use ($aktifAdminId) {
                $w->where('yonetici_id', $aktifAdminId)
                  ->orWhereNull('yonetici_id');
            });
        }

        $count = $query->update($updateData);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'okunan' => $count]);
        }

        return back()->with('success', "{$count} bildirim okundu olarak işaretlendi.");
    }


    /**
     * Tek bildirimi sil
     */
    public function sil($id)
    {
        if (!Schema::hasTable('admin_bildirimler')) return back();

        DB::table('admin_bildirimler')->where('id', $id)->delete();

        return back()->with('success', 'Bildirim silindi.');
    }


    /**
     * Hepsini sil
     */
    public function hepsiniSil()
    {
        if (!Schema::hasTable('admin_bildirimler')) return back();

        $count = DB::table('admin_bildirimler')->delete();

        return back()->with('success', "{$count} bildirim silindi.");
    }

    /**
     * POLLING ENDPOINT (JSON) — zil ikonu icin.
     * Okunmamis sayisi + son 8 bildirimi doner. Sayfayi yenilemeden cekilir.
     * GET /admin/bildirimler/sayim
     */
    public function sayim()
    {
        if (!Schema::hasTable('admin_bildirimler')) {
            return response()->json(['okunmamis' => 0, 'bildirimler' => []]);
        }

        $aktifAdminId = session('admin_id');
        $kisiselFiltre = function ($q) use ($aktifAdminId) {
            if (Schema::hasColumn('admin_bildirimler', 'yonetici_id')) {
                $q->where(function ($w) use ($aktifAdminId) {
                    $w->where('yonetici_id', $aktifAdminId)
                      ->orWhereNull('yonetici_id');
                });
            }
        };

        $sayimQuery = DB::table('admin_bildirimler')->where('okundu', 0);
        $kisiselFiltre($sayimQuery);
        $okunmamis = $sayimQuery->count();

        $sonQuery = DB::table('admin_bildirimler');
        $kisiselFiltre($sonQuery);
        $sonlar = $sonQuery
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'tip', 'baslik', 'mesaj', 'ilgili_id', 'ilgili_tablo', 'okundu', 'created_at']);

        $bildirimler = $sonlar->map(function ($b) {
            $zaman = null;
            if (!empty($b->created_at)) {
                try { $zaman = \Carbon\Carbon::parse($b->created_at)->diffForHumans(); } catch (\Throwable $e) {}
            }
            // Ilgili kayda link uret — ilgili_tablo / tip'e gore
            $link = $this->bildirimLinkUret($b->ilgili_tablo ?? null, $b->ilgili_id ?? null, $b->tip ?? null);
            return [
                'id'      => $b->id,
                'tip'     => $b->tip,
                'baslik'  => $b->baslik,
                'mesaj'   => $b->mesaj,
                'okundu'  => (int) $b->okundu,
                'zaman'   => $zaman,
                'link'    => $link,
            ];
        });

        return response()->json([
            'okunmamis'   => $okunmamis,
            'bildirimler' => $bildirimler,
        ]);
    }

    /**
     * ilgili_tablo + ilgili_id'den admin paneli linki üretir.
     * Route yoksa null döner (güvenli).
     */
    protected function bildirimLinkUret(?string $tablo, $id, ?string $tip = null): ?string
    {
        $R = fn($name, $param) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $param) : null;

        // 1) ilgili_tablo + id eşleşmesi (en güvenilir)
        if ($id) {
            switch ($tablo) {
                case 'uyeler':
                    return $R('admin.uyeler.detay', $id);
                case 'crm_tasks':
                    return $R('admin.crm.gorevler.show', $id);
                case 'crm_customers':
                    return $R('admin.crm.musteriler.show', $id);
                case 'faturalar':
                    return $R('admin.faturalar.detay', $id);
                case 'destek':
                case 'tickets':
                    return $R('admin.tickets.goster', $id) ?? $R('admin.tickets.show', $id) ?? $R('admin.destek.detay', $id);
                case 'satilanlar':
                case 'crm_musteri_teklifleri':
                    return $R('admin.crm.musteriler.show', $id);
                case 'paket_teklifleri':
                    return $R('admin.paketler.teklif.goruntule', $id);
                case 'dnbank':
                    return $R('admin.dnbank-krediler.goster', $id);
            }

            // 2) ilgili_tablo boş/tanımsız ama tip biliniyorsa, tip'e göre dene
            switch ($tip) {
                case 'destek':
                case 'ticket':
                case 'mesaj':
                    return $R('admin.tickets.goster', $id) ?? $R('admin.tickets.show', $id) ?? $R('admin.destek.detay', $id);
                case 'fatura':
                case 'odeme':
                    return $R('admin.faturalar.detay', $id);
                case 'gorev':
                case 'gorev_mesaj':
                    return $R('admin.crm.gorevler.show', $id);
                case 'uye':
                case 'musteri':
                    return $R('admin.uyeler.detay', $id) ?? $R('admin.crm.musteriler.show', $id);
                case 'sozlesme':
                    return $R('admin.crm.sozlesmeler.edit', $id);
            }
        }

        // 3) id yoksa: tip'e göre genel liste sayfasına yönlendir (yine de bir yere gitsin)
        switch ($tip) {
            case 'destek':
            case 'ticket':
            case 'mesaj':
                return $R('admin.tickets.index', null) ?? $R('admin.destek.taleplerim', null);
            case 'fatura':
            case 'odeme':
                return $R('admin.faturalar.index', null);
            case 'gorev':
            case 'gorev_mesaj':
                return $R('admin.crm.gorevler.index', null);
            case 'sozlesme':
                return $R('admin.crm.sozlesmeler.index', null);
            case 'kredi_talep':
                return $R('admin.dnbank-krediler.index', null);
        }

        return null;
    }
}