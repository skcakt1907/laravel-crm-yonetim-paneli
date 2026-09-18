<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Services\PersonelDurumGecmisi;
use App\Services\PersonelDurumu;
use App\Support\HrmYetki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * HRM — Personel anlık durum bildirimi.
 *
 * Herkes birbirinin durumunu GÖRÜR, herkes yalnızca KENDİ durumunu
 * değiştirir. Yöneticiler başkasınınkini de değiştirebilir.
 */
class DurumController extends Controller
{
    /** Durum panosu: kim ne yapıyor. */
    public function index()
    {
        $aktifId = (int) session('admin_id');

        return view('admin.hrm.durum.index', [
            'personeller' => PersonelDurumu::herkes(),
            'tipler'      => PersonelDurumu::tipler(),
            'tiplerHepsi' => PersonelDurumu::tiplerHepsi(),
            'aktifId'     => $aktifId,
            'yonetici'    => $this->yoneticiMi(),
            'alicilar'    => PersonelDurumu::seciliIdler(),
        ]);
    }

    /**
     * DURUM GEÇMİŞİ RAPORU.
     *
     * Tarihçe zaten tutuluyordu ama görüntüleyecek ekran yoktu: "dün kaçta
     * izne çıktı", "bu hafta kaç saat çekimdeydi" sorularının cevabı
     * veritabanında duruyor, kimse göremiyordu.
     *
     * Herkes herkesin geçmişini görür — durum panosunun kendisi de öyle;
     * kim ne yapıyor zaten herkese açık, geçmişini gizlemek tutarsız olurdu.
     */
    public function gecmis(Request $request)
    {
        [$bas, $son, $etiket] = PersonelDurumGecmisi::aralik(
            $request->get('bas'),
            $request->get('son')
        );

        $kisiId = (int) $request->get('kisi') ?: null;

        $kayitlar = PersonelDurumGecmisi::kayitlar($bas, $son, $kisiId);

        return view('admin.hrm.durum.gecmis', [
            'kayitlar'    => $kayitlar,
            'ozet'        => PersonelDurumGecmisi::ozet($kayitlar),
            'bas'         => $bas,
            'son'         => $son,
            'etiket'      => $etiket,
            'kisiId'      => $kisiId,
            'personeller' => PersonelDurumu::herkes(),
            'aktifId'     => (int) session('admin_id'),
            'sinir'       => PersonelDurumGecmisi::SATIR_SINIRI,
        ]);
    }

    /**
     * Durum değiştir.
     *
     * Kendi durumunu herkes değiştirir; başkasınınki yönetici yetkisi ister.
     */
    public function degistir(Request $request)
    {
        $veri = $request->validate([
            'durum_tipi_id' => 'required|integer|exists:personel_durum_tipleri,id',
            'not'           => 'nullable|string|max:200',
            'yonetici_id'   => 'nullable|integer',
        ]);

        $aktifId = (int) session('admin_id');
        $hedefId = (int) ($veri['yonetici_id'] ?? $aktifId);

        if (! $aktifId) {
            throw ValidationException::withMessages(['durum_tipi_id' => 'Oturum bulunamadı.']);
        }

        if ($hedefId !== $aktifId && ! $this->yoneticiMi()) {
            throw ValidationException::withMessages([
                'durum_tipi_id' => 'Yalnızca kendi durumunuzu değiştirebilirsiniz.',
            ]);
        }

        // Pasife alınmış tip seçilemesin
        $tip = DB::table('personel_durum_tipleri')->where('id', $veri['durum_tipi_id'])->first();
        if (! $tip || ! $tip->aktif) {
            throw ValidationException::withMessages(['durum_tipi_id' => 'Bu durum artık kullanılmıyor.']);
        }

        $mailGitsin = PersonelDurumu::degistir(
            $hedefId,
            (int) $veri['durum_tipi_id'],
            $veri['not'] ?? null,
            $aktifId
        );

        if ($mailGitsin) {
            PersonelDurumu::mailGonder($hedefId, (int) $veri['durum_tipi_id'], $veri['not'] ?? null);
        }

        return back()->with('success', 'Durum güncellendi.');
    }

    /* ─────────── DURUM TİPLERİ (yönetici) ─────────── */

    public function tipEkle(Request $request)
    {
        $this->yoneticiSart();

        $veri = $request->validate([
            'ad'          => 'required|string|max:60',
            'emoji'       => 'nullable|string|max:16',
            'renk'        => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'mail_gonder' => 'nullable|boolean',
        ], [
            'renk.regex' => 'Renk #rrggbb biçiminde olmalı.',
        ]);

        DB::table('personel_durum_tipleri')->insert([
            'ad'          => $veri['ad'],
            'emoji'       => $veri['emoji'] ?? null,
            'renk'        => $veri['renk'] ?? '#6b7280',
            'sira'        => (int) DB::table('personel_durum_tipleri')->max('sira') + 1,
            'mail_gonder' => $request->boolean('mail_gonder', true),
            'aktif'       => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return back()->with('success', 'Durum eklendi.');
    }

    public function tipGuncelle(Request $request, int $id)
    {
        $this->yoneticiSart();

        $veri = $request->validate([
            'ad'          => 'required|string|max:60',
            'emoji'       => 'nullable|string|max:16',
            'renk'        => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sira'        => 'nullable|integer|min:0|max:999',
            'mail_gonder' => 'nullable|boolean',
            'aktif'       => 'nullable|boolean',
        ]);

        DB::table('personel_durum_tipleri')->where('id', $id)->update([
            'ad'          => $veri['ad'],
            'emoji'       => $veri['emoji'] ?? null,
            'renk'        => $veri['renk'] ?? '#6b7280',
            'sira'        => (int) ($veri['sira'] ?? 0),
            'mail_gonder' => $request->boolean('mail_gonder'),
            'aktif'       => $request->boolean('aktif'),
            'updated_at'  => now(),
        ]);

        return back()->with('success', 'Durum güncellendi.');
    }

    /**
     * Durum tipini kaldır.
     *
     * Geçmişte kullanılmışsa SİLMEZ, pasife alır: silinirse o kayıtların
     * bağlı olduğu tip kaybolur ve tarihçe okunamaz hale gelir.
     */
    public function tipSil(int $id)
    {
        $this->yoneticiSart();

        $kullanilmis = DB::table('personel_durumlari')->where('durum_tipi_id', $id)->exists();

        if ($kullanilmis) {
            DB::table('personel_durum_tipleri')->where('id', $id)
                ->update(['aktif' => 0, 'updated_at' => now()]);

            return back()->with('success', 'Bu durum geçmişte kullanıldığı için silinmedi, listeden kaldırıldı.');
        }

        DB::table('personel_durum_tipleri')->where('id', $id)->delete();

        return back()->with('success', 'Durum silindi.');
    }

    /* ─────────── MAİL ALICILARI (yönetici) ─────────── */

    public function alicilar(Request $request)
    {
        $this->yoneticiSart();

        $veri = $request->validate([
            'alicilar'   => 'nullable|array',
            'alicilar.*' => 'integer|exists:yoneticiler,id',
        ]);

        $secilen = $veri['alicilar'] ?? [];

        DB::table('personel_durum_alicilari')->delete();

        if (! empty($secilen)) {
            DB::table('personel_durum_alicilari')->insert(
                collect($secilen)->unique()->map(fn ($id) => [
                    'yonetici_id' => (int) $id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])->all()
            );
        }

        return back()->with('success', empty($secilen)
            ? 'Seçim temizlendi — bildirim varsayılan listeye gidecek.'
            : count($secilen) . ' kişi bildirim alıcısı olarak kaydedildi.');
    }

    /* ─────────── YETKİ ─────────── */

    /**
     * Başkasının durumunu değiştirebilir mi / tip yönetebilir mi?
     *
     * HrmYetki::izinOnaylayabilir() zaten Patron + Muhasebe rollerini
     * kontrol ediyor; aynı kaynağı kullanıyoruz ki yetki iki ayrı yerde
     * tanımlanmasın.
     */
    private function yoneticiMi(): bool
    {
        return HrmYetki::izinOnaylayabilir();
    }

    private function yoneticiSart(): void
    {
        if (! $this->yoneticiMi()) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }
    }
}
