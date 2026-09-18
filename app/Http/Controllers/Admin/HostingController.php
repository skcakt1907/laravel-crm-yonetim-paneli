<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HostingController extends Controller
{
    /**
     * Hosting paketlerini listele.
     */
    public function paketler()
    {
        $tableName = $this->resolvePackagesTable();
        $tableMissing = $tableName === null;

        $paketler = $tableMissing
            ? $this->emptyPaginator()
            : $this->mapPackages(
                DB::table($tableName)
                    ->orderByDesc('id')
                    ->paginate(20),
                $tableName
            );

        return view('admin.hosting.paketler.index', compact('paketler', 'tableMissing', 'tableName'));
    }

    /**
     * Yeni paket formu.
     */
    public function paketEkle()
    {
        $tableName = $this->resolvePackagesTable();
        if ($tableName === null) {
            return redirect()
                ->route('admin.hosting.paketler.index')
                ->with('error', 'Hosting paket tablosu bulunamadı. Lütfen `hostingler` veya `hosting_paketler` tablosunu oluşturun.');
        }

        // Hosting kategorilerini al
        $kategoriler = collect([]);
        if (Schema::hasTable('hosting_kategori')) {
            $kategoriler = DB::table('hosting_kategori')
                ->where('durum', 1)
                ->orderBy('sira', 'asc')
                ->get();
        }

        return view('admin.hosting.paketler.ekle', compact('tableName', 'kategoriler'));
    }

    /**
     * Yeni hosting paketi kaydet.
     */
    public function paketEklePost(Request $request)
    {
        $tableName = $this->resolvePackagesTable();
        if ($tableName === null) {
            return redirect()
                ->route('admin.hosting.paketler.index')
                ->with('error', 'Hosting paket tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }

        $validated = $request->validate([
            'adi' => 'required|string|max:255',
            'disk_alani' => 'required|string',
            'trafik' => 'required|string',
            'veritabani' => 'required|string',
            'email' => 'required|string',
            'fiyat' => 'required|numeric',
            'aciklama' => 'nullable|string',
            'ozellikler' => 'nullable|string',
        ]);

        $insertData = [
            'adi' => $validated['adi'],
            'durum' => $request->has('durum') ? 1 : 0,
        ];

        // Eski hostingler tablosu için kolon isimleri
        if ($tableName === 'hostingler') {
            $insertData['whm_alan'] = $validated['disk_alani'];
            $insertData['whm_atrafik'] = $validated['trafik'];
            $insertData['whm_max_veritabani'] = $validated['veritabani'];
            $insertData['whm_max_email'] = $validated['email'];
            $insertData['tutar'] = $validated['fiyat'];
            
            // Sadece kolon varsa ekle
            if (Schema::hasColumn('hostingler', 'aciklama')) {
                $insertData['aciklama'] = $validated['aciklama'] ?? '';
            }
            if (Schema::hasColumn('hostingler', 'ozellikler')) {
                $insertData['ozellikler'] = $validated['ozellikler'] ?? '';
            }
            if (Schema::hasColumn('hostingler', 'tarih')) {
                $insertData['tarih'] = now();
            }
            if (Schema::hasColumn('hostingler', 'dil')) {
                $insertData['dil'] = 1;
            }
            if (Schema::hasColumn('hostingler', 'kategori')) {
                $kategoriId = $request->input('kategori');
                if ($kategoriId) {
                    $insertData['kategori'] = $kategoriId;
                } else {
                    // İlk kategoriyi al
                    $ilkKategori = DB::table('hosting_kategori')->where('durum', 1)->orderBy('id', 'asc')->first();
                    if ($ilkKategori) {
                        $insertData['kategori'] = $ilkKategori->id;
                    }
                }
            }
            if (Schema::hasColumn('hostingler', 'kategori_id')) {
                $kategoriId = $request->input('kategori');
                if ($kategoriId) {
                    $insertData['kategori_id'] = $kategoriId;
                } else {
                    // İlk kategoriyi al
                    $ilkKategori = DB::table('hosting_kategori')->where('durum', 1)->orderBy('id', 'asc')->first();
                    if ($ilkKategori) {
                        $insertData['kategori_id'] = $ilkKategori->id;
                    }
                }
            }
        } else {
            // Yeni hosting_paketler tablosu için
            $insertData['disk_alani'] = $validated['disk_alani'];
            $insertData['trafik'] = $validated['trafik'];
            $insertData['veritabani'] = $validated['veritabani'];
            $insertData['email'] = $validated['email'];
            $insertData['fiyat'] = $validated['fiyat'];
            $insertData['aciklama'] = $validated['aciklama'] ?? '';
            $insertData['created_at'] = now();
            $insertData['updated_at'] = now();
        }

        $hpid = DB::table($tableName)->insertGetId($insertData);
        \App\Models\Ceviri::sync($tableName, $request->input('ceviriler', []), $hpid);

        return redirect()->route('admin.hosting.paketler.index')->with('success', 'Hosting paketi başarıyla eklendi.');
    }

    /**
     * Paket düzenleme formu.
     */
    public function paketDuzenle($id)
    {
        $tableName = $this->resolvePackagesTable();
        if ($tableName === null) {
            return redirect()
                ->route('admin.hosting.paketler.index')
                ->with('error', 'Hosting paket tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }

        $paket = DB::table($tableName)->where('id', $id)->first();

        if (!$paket) {
            return redirect()->route('admin.hosting.paketler.index')->with('error', 'Paket bulunamadı.');
        }

        // Eski tablo için verileri map et
        if ($tableName === 'hostingler') {
            $paket->disk_alani = $paket->disk_alani ?? $paket->whm_alan ?? '';
            $paket->trafik = $paket->trafik ?? $paket->whm_atrafik ?? '';
            $paket->veritabani = $paket->veritabani ?? $paket->whm_max_veritabani ?? '';
            $paket->email = $paket->email ?? $paket->whm_max_email ?? '';
            $paket->fiyat = $paket->fiyat ?? $paket->tutar ?? 0;
        }

        return view('admin.hosting.paketler.duzenle', compact('paket', 'tableName'));
    }

    /**
     * Paket güncelle.
     */
    public function paketDuzenlePost(Request $request, $id)
    {
        $tableName = $this->resolvePackagesTable();
        if ($tableName === null) {
            return redirect()
                ->route('admin.hosting.paketler.index')
                ->with('error', 'Hosting paket tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }

        $validated = $request->validate([
            'adi' => 'required|string|max:255',
            'disk_alani' => 'required|string',
            'trafik' => 'required|string',
            'veritabani' => 'required|string',
            'email' => 'required|string',
            'fiyat' => 'required|numeric',
            'aciklama' => 'nullable|string',
            'ozellikler' => 'nullable|string',
        ]);

        $updateData = [
            'adi' => $validated['adi'],
            'durum' => $request->has('durum') ? 1 : 0,
        ];

        // Eski hostingler tablosu için kolon isimleri
        if ($tableName === 'hostingler') {
            $updateData['whm_alan'] = $validated['disk_alani'];
            $updateData['whm_atrafik'] = $validated['trafik'];
            $updateData['whm_max_veritabani'] = $validated['veritabani'];
            $updateData['whm_max_email'] = $validated['email'];
            $updateData['tutar'] = $validated['fiyat'];
            
            // Sadece kolon varsa ekle
            if (Schema::hasColumn('hostingler', 'aciklama')) {
                $updateData['aciklama'] = $validated['aciklama'] ?? '';
            }
            if (Schema::hasColumn('hostingler', 'ozellikler')) {
                $updateData['ozellikler'] = $validated['ozellikler'] ?? '';
            }
        } else {
            // Yeni hosting_paketler tablosu için
            $updateData['disk_alani'] = $validated['disk_alani'];
            $updateData['trafik'] = $validated['trafik'];
            $updateData['veritabani'] = $validated['veritabani'];
            $updateData['email'] = $validated['email'];
            $updateData['fiyat'] = $validated['fiyat'];
            $updateData['aciklama'] = $validated['aciklama'] ?? '';
            $updateData['updated_at'] = now();
        }

        DB::table($tableName)->where('id', $id)->update($updateData);
        \App\Models\Ceviri::sync($tableName, $request->input('ceviriler', []), (int) $id);

        return redirect()->route('admin.hosting.paketler.index')->with('success', 'Hosting paketi başarıyla güncellendi.');
    }

    /**
     * Paket sil.
     */
    public function paketSil($id)
    {
        $tableName = $this->resolvePackagesTable();
        if ($tableName === null) {
            return redirect()
                ->route('admin.hosting.paketler.index')
                ->with('error', 'Hosting paket tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }

        DB::table($tableName)->where('id', $id)->delete();

        return redirect()->route('admin.hosting.paketler.index')->with('success', 'Hosting paketi başarıyla silindi.');
    }

    // Hosting Satışları
    public function satislar()
    {
        $tableMissing = !Schema::hasTable('hosting_satislar');

        $satislar = $tableMissing
            ? $this->emptyPaginator()
            : DB::table('hosting_satislar')
                ->orderByDesc('id')
                ->paginate(20);

        return view('admin.satislar.hosting', compact('satislar', 'tableMissing'));
    }

    public function satisDetay($id)
    {
        if (!$this->ensureTableExists('hosting_satislar')) {
            return redirect()
                ->route('admin.hosting.satislar.index')
                ->with('error', 'Hosting satış tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }

        $satis = DB::table('hosting_satislar')->where('id', $id)->first();

        if (!$satis) {
            return redirect()->route('admin.hosting.satislar.index')->with('error', 'Satış bulunamadı.');
        }

        return view('admin.hosting.satislar.detay', compact('satis'));
    }

    /**
     * hostingler tablosundaki kolonları listelemeye uygun hale getir.
     */
    private function mapPackages(LengthAwarePaginator $paginator, string $tableName): LengthAwarePaginator
    {
        if ($tableName === 'hostingler') {
            $paginator->getCollection()->transform(function ($item) {
                $item->disk_alani = $item->disk_alani ?? ($item->whm_alan ?? '-');
                $item->trafik = $item->trafik ?? ($item->whm_atrafik ?? '-');
                $item->veritabani = $item->veritabani ?? ($item->whm_max_veritabani ?? '-');
                $item->email = $item->email ?? ($item->whm_max_email ?? '-');
                $item->fiyat = $item->fiyat ?? ($item->tutar ?? 0);
                $item->durum = $item->durum ?? 0;
                // Özellikler sütununu koru
                if (isset($item->ozellikler)) {
                    $item->ozellikler = $item->ozellikler;
                }
                return $item;
            });
        }

        return $paginator;
    }

    /**
     * Kullanılacak paket tablosunu belirle.
     */
    private function resolvePackagesTable(): ?string
    {
        if (Schema::hasTable('hosting_paketler')) {
            return 'hosting_paketler';
        }

        if (Schema::hasTable('hostingler')) {
            return 'hostingler';
        }

        return null;
    }


    private function ensureTableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function emptyPaginator(int $perPage = 20): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            Collection::make(),
            0,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}


