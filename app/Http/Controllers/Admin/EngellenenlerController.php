<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EngellenenlerController extends Controller
{
    /**
     * Engellenen kullanıcılar listesi
     * uyeler.durum = 0 → Engellenmiş/Pasif
     */
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));
        $sortBy = $request->query('sort', 'ktarih');
        $sortDir = $request->query('dir', 'desc');

        // Sıralama whitelist
        $allowedSort = ['id', 'ad', 'soyad', 'email', 'telefon', 'tarih', 'ktarih'];
        if (!in_array($sortBy, $allowedSort)) $sortBy = 'ktarih';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $query = DB::table('uyeler')->where('durum', 0);

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('ad', 'like', "%{$q}%")
                  ->orWhere('soyad', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('telefon', 'like', "%{$q}%")
                  ->orWhere('firmaadi', 'like', "%{$q}%");
            });
        }

        $engellenenler = $query->orderBy($sortBy, $sortDir)
            ->paginate(25)
            ->appends($request->query());

        // Schema-aware: hangi tarih kolonu var?
        $hasUpdatedAt = Schema::hasColumn('uyeler', 'updated_at');
        $tarihKolonu = $hasUpdatedAt ? 'updated_at' : 'ktarih';

        // İstatistik
        $istatistik = [
            'toplam'      => DB::table('uyeler')->where('durum', 0)->count(),
            'bu_ay'       => DB::table('uyeler')->where('durum', 0)
                ->where($tarihKolonu, '>=', now()->startOfMonth())->count(),
            'aktif_toplam'=> DB::table('uyeler')->where('durum', 1)->count(),
        ];

        return view('admin.engellenenler.index', compact('engellenenler', 'istatistik', 'q', 'sortBy', 'sortDir'));
    }


    /**
     * Tek bir kullanıcının engelini kaldır
     */
    public function aktiveEt($id)
    {
        $uye = DB::table('uyeler')->where('id', $id)->where('durum', 0)->first();
        if (!$uye) {
            return back()->with('error', 'Engellenen kullanıcı bulunamadı.');
        }

        $updateData = ['durum' => 1];
        if (Schema::hasColumn('uyeler', 'updated_at')) {
            $updateData['updated_at'] = now();
        }
        DB::table('uyeler')->where('id', $id)->update($updateData);

        // 📧 Müşteriye aktif edildiği bildirimi
        try {
            \App\Services\CustomerNotifier::hesapAktifEdildi($id);
        } catch (\Throwable $e) {
            \Log::warning('Aktif bildirim maili gönderilemedi', ['err' => $e->getMessage()]);
        }

        return back()->with('success', "{$uye->ad} {$uye->soyad} kullanıcısının engeli kaldırıldı.");
    }


    /**
     * Çoklu engel kaldırma
     */
    public function topluAktiveEt(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $updateData = ['durum' => 1];
        if (Schema::hasColumn('uyeler', 'updated_at')) {
            $updateData['updated_at'] = now();
        }

        $count = DB::table('uyeler')
            ->whereIn('id', $validated['ids'])
            ->where('durum', 0)
            ->update($updateData);

        // 📧 Her birine aktif bildirimi
        try {
            foreach ($validated['ids'] as $uyeId) {
                \App\Services\CustomerNotifier::hesapAktifEdildi($uyeId);
            }
        } catch (\Throwable $e) {
            \Log::warning('Toplu aktif bildirim hatası', ['err' => $e->getMessage()]);
        }

        return back()->with('success', "{$count} kullanıcının engeli kaldırıldı.");
    }


    /**
     * Kullanıcıyı TAMAMEN sil (uyeler tablosundan)
     * DİKKAT: Bağlı kayıtlar (faturalar, destek vs) eski uye_id'ye bağlı kalır
     */
    public function sil($id)
    {
        $uye = DB::table('uyeler')->where('id', $id)->where('durum', 0)->first();
        if (!$uye) {
            return back()->with('error', 'Kullanıcı bulunamadı veya engellenmemiş.');
        }

        DB::table('uyeler')->where('id', $id)->delete();

        return back()->with('success', "{$uye->ad} {$uye->soyad} kullanıcısı tamamen silindi.");
    }


    /**
     * Çoklu silme
     */
    public function topluSil(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $count = DB::table('uyeler')
            ->whereIn('id', $validated['ids'])
            ->where('durum', 0)
            ->delete();

        return back()->with('success', "{$count} engellenen kullanıcı tamamen silindi.");
    }


    /**
     * Aktif kullanıcıyı engelle (manuel)
     * Bu metot başka yerlerden kullanılabilir
     */
    public function engelle($id, Request $request)
    {
        $uye = DB::table('uyeler')->where('id', $id)->where('durum', 1)->first();
        if (!$uye) {
            return back()->with('error', 'Aktif kullanıcı bulunamadı.');
        }

        $updateData = ['durum' => 0];
        if (Schema::hasColumn('uyeler', 'updated_at')) {
            $updateData['updated_at'] = now();
        }
        DB::table('uyeler')->where('id', $id)->update($updateData);

        // 📧 Müşteriye engellendiği bildirimi
        try {
            $sebep = $request->input('sebep', null);
            \App\Services\CustomerNotifier::hesapEngellendi($id, $sebep);
        } catch (\Throwable $e) {
            \Log::warning('Engelleme bildirim maili gönderilemedi', ['err' => $e->getMessage()]);
        }

        return back()->with('success', "{$uye->ad} {$uye->soyad} kullanıcısı engellendi.");
    }
}