<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sanal Tur Hosting Takip — tek tablo (sanaltur_hosting_takip).
 * Domain & Hosting Takip modülünün sadeleştirilmiş kopyasıdır (online sipariş kaynağı yoktur).
 */
class SanalTurHostingController extends Controller
{
    protected string $tablo = 'sanaltur_hosting_takip';

    public function index(Request $request)
    {
        $search  = trim((string) $request->get('search'));
        $status  = $request->get('status');
        $perPage = 20;

        $rows = collect();
        if (Schema::hasTable($this->tablo)) {
            $q = DB::table($this->tablo . ' as s')
                ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
                ->select(
                    's.id as id',
                    's.domain as domain',
                    's.durum as status',
                    's.tutar as fiyat',
                    's.baslangic_tarih as baslangic',
                    's.bitis_tarih as bitis',
                    's.created_at as created_at',
                    's.uyeid as uye_id',
                    'u.email as uye_email',
                    'u.ad as uye_ad',
                    'u.soyad as uye_soyad',
                    'u.firmaadi as uye_firma'
                );

            if ($search !== '') {
                $q->where(function ($w) use ($search) {
                    $w->where('s.domain', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%")
                      ->orWhere('u.firmaadi', 'like', "%{$search}%");
                });
            }
            if ($status !== null && $status !== '') {
                $q->where('s.durum', $status);
            }

            $rows = $q->orderByDesc('s.created_at')->get();
        }

        // Kalan gün yardımcıları
        $now = time();
        $kalanGun = function ($bitis) use ($now) {
            if (!$bitis) return null;
            try { return (int) round((strtotime($bitis) - $now) / 86400); } catch (\Throwable $e) { return null; }
        };
        $altinda = function (int $esik) use ($rows, $kalanGun) {
            return $rows->filter(function ($d) use ($esik, $kalanGun) {
                $g = $kalanGun($d->bitis ?? null);
                return $g !== null && $g >= 0 && $g <= $esik;
            })->count();
        };

        // Kart filtreleri: ?kalan=30|7 ve ?sgrup=aktif|pasif
        $kalan = (int) $request->get('kalan');
        $sgrup = $request->get('sgrup');
        $filtered = $rows;
        if (in_array($kalan, [7, 30], true)) {
            $filtered = $filtered->filter(function ($d) use ($kalan, $kalanGun) {
                $g = $kalanGun($d->bitis ?? null);
                return $g !== null && $g >= 0 && $g <= $kalan;
            })->values();
        }
        if ($sgrup === 'aktif') {
            $filtered = $filtered->whereIn('status', [1, '1'])->values();
        } elseif ($sgrup === 'pasif') {
            $filtered = $filtered->whereIn('status', [0, '0'])->values();
        }

        // Sayfalama
        $page    = LengthAwarePaginator::resolveCurrentPage('page');
        $total   = $filtered->count();
        $items   = $filtered->slice(($page - 1) * $perPage, $perPage)->values();
        $domains = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);

        $stats = [
            'toplam'   => $rows->count(),
            'gun30'    => $altinda(30),
            'gun7'     => $altinda(7),
            'musteri'  => $rows->pluck('uye_id')->filter()->unique()->count(),
            'aktif'    => $rows->whereIn('status', [1, '1'])->count(),
            'pasif'    => $rows->whereIn('status', [0, '0'])->count(),
        ];

        return view('admin.crm.sanaltur-hosting.index', compact('domains', 'stats', 'search', 'status'));
    }

    public function create(Request $request)
    {
        $uyeler = collect();
        if (Schema::hasTable('uyeler')) {
            $uyeler = DB::table('uyeler')
                ->select('id', 'ad', 'soyad', 'email', 'firmaadi')
                ->orderBy('ad')->get();
        }
        $selectedUyeId = $request->integer('uyeid') ?: null;
        [$hizmetFiyatlari, $vdsSecenekleri] = $this->fiyatListeleri();
        $row = null;

        return view('admin.crm.sanaltur-hosting.create', compact('uyeler', 'selectedUyeId', 'hizmetFiyatlari', 'vdsSecenekleri', 'row'));
    }

    public function store(Request $request)
    {
        $validated = $this->dogrula($request);
        $validated['tutar'] = $this->hesaplaTutar($validated['hizmetler'] ?? [], $validated['vds'] ?? null, $validated['tutar'] ?? null);

        $baslangic = $validated['baslangic_tarih'] ?? now()->toDateString();
        $bitis     = $validated['bitis_tarih'] ?? \Carbon\Carbon::parse($baslangic)->addYear()->toDateString();

        DB::table($this->tablo)->insert([
            'uyeid'           => $validated['uyeid'],
            'domain'          => strtolower(trim($validated['domain'])),
            'durum'           => (int) $validated['durum'],
            'tutar'           => $validated['tutar'] ?? 0,
            'baslangic_tarih' => $baslangic,
            'bitis_tarih'     => $bitis,
            'saglayici'       => $validated['saglayici'] ?? null,
            'hizmetler'       => json_encode(array_values($validated['hizmetler'] ?? []), JSON_UNESCAPED_UNICODE),
            'vds'             => $validated['vds'] ?? null,
            'mesaj'           => $validated['mesaj'] ?? null,
            'olusturan_id'    => session('admin_id'),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return redirect()->route('admin.crm.sanaltur-hosting.index')
            ->with('success', 'Sanal Tur hosting kaydı eklendi.');
    }

    public function show(int $id)
    {
        $row = DB::table($this->tablo . ' as s')
            ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
            ->where('s.id', $id)
            ->select('s.*', 'u.email as uye_email', 'u.ad as uye_ad', 'u.soyad as uye_soyad', 'u.firmaadi as uye_firma')
            ->first();
        abort_if(!$row, 404);

        return view('admin.crm.sanaltur-hosting.show', compact('row'));
    }

    public function edit(int $id)
    {
        $row = DB::table($this->tablo . ' as s')
            ->leftJoin('uyeler as u', 'u.id', '=', 's.uyeid')
            ->where('s.id', $id)
            ->select('s.*', 'u.email as uye_email', 'u.ad as uye_ad', 'u.soyad as uye_soyad', 'u.firmaadi as uye_firma')
            ->first();
        abort_if(!$row, 404);

        $uyeler = DB::table('uyeler')
            ->select('id', 'ad', 'soyad', 'email', 'firmaadi')
            ->orderBy('ad')->get();
        [$hizmetFiyatlari, $vdsSecenekleri] = $this->fiyatListeleri();

        return view('admin.crm.sanaltur-hosting.edit', compact('row', 'uyeler', 'hizmetFiyatlari', 'vdsSecenekleri'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->dogrula($request);
        $validated['tutar'] = $this->hesaplaTutar($validated['hizmetler'] ?? [], $validated['vds'] ?? null, $validated['tutar'] ?? null);

        DB::table($this->tablo)->where('id', $id)->update([
            'uyeid'           => $validated['uyeid'],
            'domain'          => strtolower(trim($validated['domain'])),
            'durum'           => (int) $validated['durum'],
            'tutar'           => $validated['tutar'] ?? 0,
            'baslangic_tarih' => $validated['baslangic_tarih'] ?? null,
            'bitis_tarih'     => $validated['bitis_tarih'] ?? null,
            'saglayici'       => $validated['saglayici'] ?? null,
            'hizmetler'       => json_encode(array_values($validated['hizmetler'] ?? []), JSON_UNESCAPED_UNICODE),
            'vds'             => $validated['vds'] ?? null,
            'mesaj'           => $validated['mesaj'] ?? null,
            'updated_at'      => now(),
        ]);

        return redirect()->route('admin.crm.sanaltur-hosting.index')
            ->with('success', 'Sanal Tur hosting kaydı güncellendi.');
    }

    public function destroy(int $id)
    {
        DB::table($this->tablo)->where('id', $id)->delete();

        return redirect()->route('admin.crm.sanaltur-hosting.index')
            ->with('success', 'Kayıt silindi.');
    }

    /** Ortak doğrulama */
    protected function dogrula(Request $request): array
    {
        return $request->validate([
            'uyeid'           => 'required|integer|exists:uyeler,id',
            'domain'          => 'required|string|max:190',
            'tutar'           => 'nullable|numeric|min:0',
            'baslangic_tarih' => 'nullable|date',
            'bitis_tarih'     => 'nullable|date|after_or_equal:baslangic_tarih',
            'durum'           => 'required|in:0,1',
            'mesaj'           => 'nullable|string|max:2000',
            'saglayici'       => 'nullable|string|max:100',
            'hizmetler'       => 'nullable|array',
            'hizmetler.*'     => 'string|max:200',
            'vds'             => 'nullable|string|max:100',
        ]);
    }

    /** hizmet_fiyatlari -> [hizmetler, vds seçenekleri] */
    private function fiyatListeleri(): array
    {
        $tum = Schema::hasTable('hizmet_fiyatlari')
            ? DB::table('hizmet_fiyatlari')->orderBy('sira')->get()
            : collect();
        $vds    = $tum->filter(fn ($f) => stripos($f->etiket ?? '', 'vds') !== false)->values();
        $hizmet = $tum->filter(fn ($f) => stripos($f->etiket ?? '', 'vds') === false)->values();
        return [$hizmet, $vds];
    }

    /** Seçilen hizmetlerin sabit fiyat toplamı; seçilmemişse formdan gelen tutar korunur */
    private function hesaplaTutar(array $hizmetler, ?string $vds, $fallback)
    {
        if (empty($hizmetler) && empty($vds)) {
            return $fallback ?? 0;
        }
        if (!Schema::hasTable('hizmet_fiyatlari')) {
            return $fallback ?? 0;
        }
        $etiketler = $hizmetler;
        if ($vds) $etiketler[] = $vds;
        return (float) DB::table('hizmet_fiyatlari')
            ->whereIn('etiket', $etiketler)
            ->sum('fiyat');
    }
}
