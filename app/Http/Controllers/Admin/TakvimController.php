<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TakvimController extends Controller
{
    /**
     * Oturumdaki rol kaydını tek seferde getirir (tekrar tekrar sorgu atmamak için).
     */
    private function rolKaydi()
    {
        static $rol = null;
        static $cozuldu = false;
        if (!$cozuldu) {
            $rol = DB::table('roller')->where('id', (int) session('admin_rol', 0))->first();
            $cozuldu = true;
        }
        return $rol;
    }

    /**
     * Herkesin takvimini GÖREBİLEN roller.
     * Patron (korumali=1), Tüm Yetkiler açık roller (tam_yetki=1) ve
     * geriye dönük uyumluluk için patron/muhasebe slug'ları.
     * RolKontrol middleware'iyle aynı mantık (korumali + tam_yetki).
     */
    private function herkesiGorur(): bool
    {
        $rol = $this->rolKaydi();
        if (!$rol) {
            return false;
        }
        if (!empty($rol->korumali)) {
            return true;
        }
        if (!empty($rol->tam_yetki)) {
            return true;
        }
        return in_array($rol->slug, ['patron', 'muhasebe'], true);
    }

    /**
     * Başkasının etkinliğini DÜZENLEYEBİLEN / SİLEBİLEN / TAŞIYABİLEN roller.
     * Şu an "herkesi görebilen" roller aynı zamanda yönetebilir.
     * (Görme yetkisinden ayrı bir helper tutuldu ki ileride farklılaşabilsin.)
     */
    private function herkesiYonetir(): bool
    {
        return $this->herkesiGorur();
    }

    /**
     * Takvim sayfası. Herkes kendi takvimini görür; yöneticiler herkesinkini.
     */
    public function index()
    {
        $herkesiGorur = $this->herkesiGorur();

        // Yöneticiler kişiye göre filtreleyebilsin diye yönetici listesi
        $kullanicilar = $herkesiGorur
            ? DB::table('yoneticiler')->select('id', 'adi', 'kullaniciadi')->orderBy('adi')->get()
            : collect();

        return view('admin.takvim.index', compact('herkesiGorur', 'kullanicilar'));
    }

    /**
     * FullCalendar için JSON etkinlik beslemesi.
     * İstenen aralığa (start/end) göre filtreler.
     */
    public function events(Request $request)
    {
        $query = DB::table('takvim_etkinlikler');

        // Yetki: herkesi görmeyen kullanıcı sadece kendi etkinliklerini görür
        if ($this->herkesiGorur()) {
            // Yönetici belirli bir kişiyi filtrelemek isterse
            if ($request->filled('kullanici')) {
                $query->where('olusturan_id', (int) $request->kullanici);
            }
        } else {
            $query->where('olusturan_id', session('admin_id'));
        }

        if ($request->filled('start') && $request->filled('end')) {
            $query->where('baslangic', '<', $request->end)
                  ->where(function ($q) use ($request) {
                      $q->where('bitis', '>=', $request->start)
                        ->orWhereNull('bitis');
                  });
        }

        $herkesiGorur  = $this->herkesiGorur();
        $herkesiYonetir = $this->herkesiYonetir();
        $aktifAdminId  = (int) session('admin_id');

        $etkinlikler = $query->orderBy('baslangic')->get();

        $data = $etkinlikler->map(function ($e) use ($herkesiGorur, $herkesiYonetir, $aktifAdminId) {
            // Herkesi goren modda: baskasinin etkinliginin basina ismini ekle (karismasin)
            $baslik = $e->baslik;
            $benimMi = ((int) $e->olusturan_id === $aktifAdminId);
            if ($herkesiGorur && !$benimMi && !empty($e->olusturan_adi)) {
                $baslik = $e->olusturan_adi . ' • ' . $e->baslik;
            }

            // Yonetebilir mi: kendi etkinligi VEYA yetkili rol (Patron / tam_yetki / muhasebe)
            $yonetebilir = $benimMi || $herkesiYonetir;

            return [
                'id'    => $e->id,
                'title' => $baslik,
                'start' => $e->baslangic,
                'end'   => $e->bitis,
                'allDay' => (bool) $e->tum_gun,
                'color' => $e->renk ?: '#b8b62e',
                // Yetkili degilse baskasininkine FE'de tiklayinca duzenleme acilmasin / surukleme olmasin
                'editable' => $yonetebilir,
                'startEditable' => $yonetebilir,
                'durationEditable' => $yonetebilir,
                'extendedProps' => [
                    'aciklama'      => $e->aciklama,
                    'olusturan_adi' => $e->olusturan_adi,
                    'benim_mi'      => $benimMi,
                    'yonetebilir'   => $yonetebilir,
                ],
            ];
        });

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'baslik'     => 'required|string|max:255',
            'aciklama'   => 'nullable|string',
            'baslangic'  => 'required|date',
            'bitis'      => 'nullable|date|after_or_equal:baslangic',
            'tum_gun'    => 'nullable|boolean',
            'renk'       => 'nullable|string|max:20',
        ], [
            'baslik.required'    => 'Başlık gereklidir.',
            'baslangic.required' => 'Başlangıç tarihi gereklidir.',
            'bitis.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
        ]);

        DB::table('takvim_etkinlikler')->insert([
            'baslik'        => $validated['baslik'],
            'aciklama'      => $validated['aciklama'] ?? null,
            'baslangic'     => $validated['baslangic'],
            'bitis'         => $validated['bitis'] ?? null,
            'tum_gun'       => (int) ($request->boolean('tum_gun')),
            'renk'          => $validated['renk'] ?? '#b8b62e',
            'olusturan_id'  => session('admin_id'),
            'olusturan_adi' => session('admin_adi', session('admin_kullanici_adi')),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.takvim.index')->with('success', 'Etkinlik eklendi.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'baslik'     => 'required|string|max:255',
            'aciklama'   => 'nullable|string',
            'baslangic'  => 'required|date',
            'bitis'      => 'nullable|date|after_or_equal:baslangic',
            'tum_gun'    => 'nullable|boolean',
            'renk'       => 'nullable|string|max:20',
        ]);

        // Yetkili rol (Patron / tam_yetki / muhasebe) herkesinkini, digerleri sadece kendi etkinligini duzenler
        $q = DB::table('takvim_etkinlikler')->where('id', $id);
        if (!$this->herkesiYonetir()) {
            $q->where('olusturan_id', session('admin_id'));
        }

        $etkilenen = $q->update([
            'baslik'     => $validated['baslik'],
            'aciklama'   => $validated['aciklama'] ?? null,
            'baslangic'  => $validated['baslangic'],
            'bitis'      => $validated['bitis'] ?? null,
            'tum_gun'    => (int) ($request->boolean('tum_gun')),
            'renk'       => $validated['renk'] ?? '#b8b62e',
            'updated_at' => now(),
        ]);

        // Hicbir satir guncellenmediyse: ya yok ya da baskasinin etkinligi
        if ($etkilenen === 0) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Bu etkinliği düzenleme yetkiniz yok.'], 403);
            }
            return redirect()->route('admin.takvim.index')->with('error', 'Sadece kendi etkinliklerinizi düzenleyebilirsiniz.');
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.takvim.index')->with('success', 'Etkinlik güncellendi.');
    }

    /**
     * Sürükle-bırak ile tarih güncelleme (FullCalendar eventDrop/resize).
     */
    public function tasi(Request $request, $id)
    {
        $validated = $request->validate([
            'baslangic' => 'required|date',
            'bitis'     => 'nullable|date',
            'tum_gun'   => 'nullable|boolean',
        ]);

        // Yetkili rol herkesinkini, digerleri sadece kendi etkinligini tasiyabilir
        $q = DB::table('takvim_etkinlikler')->where('id', $id);
        if (!$this->herkesiYonetir()) {
            $q->where('olusturan_id', session('admin_id'));
        }

        $etkilenen = $q->update([
            'baslangic'  => $validated['baslangic'],
            'bitis'      => $validated['bitis'] ?? null,
            'tum_gun'    => (int) ($request->boolean('tum_gun')),
            'updated_at' => now(),
        ]);

        if ($etkilenen === 0) {
            return response()->json(['success' => false, 'message' => 'Bu etkinliği taşıma yetkiniz yok.'], 403);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        // Yetkili rol (Patron / tam_yetki / muhasebe) herkesinkini, digerleri sadece kendi etkinligini siler
        $q = DB::table('takvim_etkinlikler')->where('id', $id);
        if (!$this->herkesiYonetir()) {
            $q->where('olusturan_id', session('admin_id'));
        }

        $etkilenen = $q->delete();

        if ($etkilenen === 0) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Bu etkinliği silme yetkiniz yok.'], 403);
            }
            return redirect()->route('admin.takvim.index')->with('error', 'Sadece kendi etkinliklerinizi silebilirsiniz.');
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.takvim.index')->with('success', 'Etkinlik silindi.');
    }
}