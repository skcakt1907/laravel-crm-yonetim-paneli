<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Services\PersonelGunPlani;
use App\Support\HrmYetki;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * HRM — Gün planı (saatli program).
 *
 * Kişi kendi gününü planlar; yöneticiler herkesinkini görür ve düzenler.
 * Durum bildirimiyle bağı yok — bkz. PersonelGunPlani.
 */
class PlanController extends Controller
{
    public function index(Request $request)
    {
        $tarih   = $this->tarih($request);
        $aktifId = (int) session('admin_id');

        return view('admin.hrm.plan.index', [
            'tarih'       => $tarih,
            'planlar'     => PersonelGunPlani::gun($tarih),
            'personeller' => PersonelGunPlani::personeller(),
            'aktifId'     => $aktifId,
            'yonetici'    => $this->yoneticiMi(),
        ]);
    }

    public function ekle(Request $request)
    {
        $veri = $request->validate([
            'tarih'       => 'required|date',
            'baslangic'   => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'bitis'       => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'baslik'      => 'required|string|max:160',
            'aciklama'    => 'nullable|string|max:1000',
            'yonetici_id' => 'nullable|integer',
        ], [
            'baslangic.regex' => 'Saat 00:00 – 23:59 arasında olmalı.',
            'bitis.regex'     => 'Saat 00:00 – 23:59 arasında olmalı.',
        ]);

        $hedefId = $this->hedefKisi($veri['yonetici_id'] ?? null);

        /*
         * Bitis baslangictan once olamaz. Gece yarisini asan vardiya bu
         * panelde yok; oyle bir kayit girilirse ekranda sira bozulur ve
         * sure hesabi eksi cikar.
         */
        if (! empty($veri['bitis']) && $veri['bitis'] <= $veri['baslangic']) {
            throw ValidationException::withMessages([
                'bitis' => 'Bitiş saati başlangıçtan sonra olmalı.',
            ]);
        }

        PersonelGunPlani::ekle([
            'yonetici_id'  => $hedefId,
            'tarih'        => Carbon::parse($veri['tarih'])->toDateString(),
            'baslangic'    => $veri['baslangic'],
            'bitis'        => $veri['bitis'] ?? null,
            'baslik'       => $veri['baslik'],
            'aciklama'     => $veri['aciklama'] ?? null,
            'olusturan_id' => (int) session('admin_id'),
        ]);

        return back()->with('success', 'Plana eklendi.');
    }

    /** Tamamlandı işaretini çevirir. */
    public function isaretle(int $id)
    {
        $satir = $this->satir($id);

        DB::table('personel_gun_plani')->where('id', $id)->update([
            'tamamlandi' => $satir->tamamlandi ? 0 : 1,
            'updated_at' => now(),
        ]);

        return back();
    }

    public function sil(int $id)
    {
        $this->satir($id);   // yetki kontrolü

        DB::table('personel_gun_plani')->where('id', $id)->delete();

        return back()->with('success', 'Plan satırı silindi.');
    }

    /**
     * Bir günün planını başka güne kopyalar.
     *
     * Çoğu gün bir öncekine benziyor; her sabah aynı satırları elle
     * yazmak insanları planı hiç doldurmamaya iter.
     */
    public function kopyala(Request $request)
    {
        $veri = $request->validate([
            'kaynak'      => 'required|date',
            'hedef'       => 'required|date',
            'yonetici_id' => 'nullable|integer',
        ]);

        $hedefId = $this->hedefKisi($veri['yonetici_id'] ?? null);

        $adet = PersonelGunPlani::kopyala(
            $hedefId,
            Carbon::parse($veri['kaynak']),
            Carbon::parse($veri['hedef'])
        );

        return back()->with('success', $adet > 0
            ? $adet . ' satır kopyalandı.'
            : 'Kopyalanacak satır bulunamadı.');
    }

    /* ─────────── YARDIMCILAR ─────────── */

    private function tarih(Request $request): Carbon
    {
        try {
            return $request->filled('tarih')
                ? Carbon::parse($request->get('tarih'))->startOfDay()
                : Carbon::today();
        } catch (\Throwable $e) {
            return Carbon::today();
        }
    }

    /** Hangi kişinin planına yazılıyor — başkasınınki yönetici ister. */
    private function hedefKisi(?int $istenen): int
    {
        $aktifId = (int) session('admin_id');

        if (! $aktifId) {
            throw ValidationException::withMessages(['baslik' => 'Oturum bulunamadı.']);
        }

        $hedefId = $istenen ?: $aktifId;

        if ($hedefId !== $aktifId && ! $this->yoneticiMi()) {
            throw ValidationException::withMessages([
                'baslik' => 'Yalnızca kendi planınızı düzenleyebilirsiniz.',
            ]);
        }

        return $hedefId;
    }

    /** Satırı getirir, üzerinde yetki yoksa durdurur. */
    private function satir(int $id)
    {
        $satir = DB::table('personel_gun_plani')->where('id', $id)->first();

        if (! $satir) {
            abort(404);
        }

        if ((int) $satir->yonetici_id !== (int) session('admin_id') && ! $this->yoneticiMi()) {
            abort(403, 'Bu satır üzerinde yetkiniz yok.');
        }

        return $satir;
    }

    /**
     * Başkasının planını düzenleyebilir mi?
     *
     * Durum panosuyla aynı kaynak (HrmYetki) — yetki iki ayrı yerde
     * tanımlanmasın.
     */
    private function yoneticiMi(): bool
    {
        return HrmYetki::izinOnaylayabilir();
    }
}
