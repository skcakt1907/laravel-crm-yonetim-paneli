<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DomainController extends Controller
{
    // Alan Adı Fiyatları
    public function fiyatlar()
    {
        $table = $this->resolveDomainTable();
        $tableMissing = $table === null;

        if ($table === 'domain_fiyatlar') {
            // sira kolonu varsa ona göre, yoksa uzantıya göre sırala.
            // Sürükle-bırak için sayfalama YOK (tüm uzantılar tek listede).
            $query = DB::table($table);
            if (Schema::hasColumn('domain_fiyatlar', 'sira')) {
                $query->orderBy('sira', 'asc')->orderBy('uzanti', 'asc');
            } else {
                $query->orderBy('uzanti', 'asc');
            }
            $fiyatlar = $query->paginate(200);
        } elseif ($table === 'alanadi') {
            $fiyatlar = $this->legacyDomainPaginator();
        } else {
            $fiyatlar = $this->emptyPaginator();
        }
        
        return view('admin.domain.fiyatlar.index', [
            'fiyatlar' => $fiyatlar,
            'tableMissing' => $tableMissing,
            'usingLegacyTable' => $table === 'alanadi',
        ]);
    }

    /**
     * Sürükle-bırak sıralamasını kaydet (AJAX).
     * Beklenen: { sira: [id1, id2, id3, ...] } — yeni sıralı id dizisi
     */
    public function siraKaydet(Request $request)
    {
        if (!Schema::hasTable('domain_fiyatlar') || !Schema::hasColumn('domain_fiyatlar', 'sira')) {
            return response()->json(['success' => false, 'message' => 'sira kolonu yok. SQL\'i çalıştırın.'], 400);
        }

        $sira = $request->input('sira', []);
        if (!is_array($sira) || empty($sira)) {
            return response()->json(['success' => false, 'message' => 'Geçersiz sıralama verisi.'], 422);
        }

        try {
            $i = 1;
            foreach ($sira as $id) {
                DB::table('domain_fiyatlar')->where('id', (int) $id)->update(['sira' => $i]);
                $i++;
            }
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function fiyatEkle()
    {
        $table = $this->resolveDomainTable(forWrite: true);
        if ($table !== 'domain_fiyatlar') {
            return redirect()
                ->route('admin.domain.fiyatlar.index')
                ->with('error', 'Alan adı fiyat tablosu bulunamadığı için yeni kayıt eklenemez.');
        }

        return view('admin.domain.fiyatlar.ekle');
    }
    
    public function fiyatEklePost(Request $request)
    {
        $table = $this->resolveDomainTable(forWrite: true);
        if ($table !== 'domain_fiyatlar') {
            return redirect()
                ->route('admin.domain.fiyatlar.index')
                ->with('error', 'Alan adı fiyat tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }

        $validated = $request->validate([
            'uzanti' => 'required|string|max:50',
            'kayit_fiyat' => 'required|numeric',
            'yenileme_fiyat' => 'required|numeric',
            'transfer_fiyat' => 'required|numeric',
        ]);

        // Uzantıyı normalize et: küçük harf, başında tek nokta (".com")
        $uzanti = '.' . ltrim(strtolower(trim($validated['uzanti'])), '.');

        // Zaten var mı? (unique kolon → çakışmadan önce kibarca uyar)
        $mevcut = DB::table('domain_fiyatlar')->where('uzanti', $uzanti)->first();
        if ($mevcut) {
            return redirect()
                ->route('admin.domain.fiyatlar.duzenle', $mevcut->id)
                ->with('error', "\"{$uzanti}\" uzantısı zaten kayıtlı. Fiyatını buradan güncelleyebilirsiniz.");
        }

        $insert = [
            'uzanti' => $uzanti,
            'kayit_fiyat' => $validated['kayit_fiyat'],
            'yenileme_fiyat' => $validated['yenileme_fiyat'],
            'transfer_fiyat' => $validated['transfer_fiyat'],
            'durum' => $request->has('durum') ? 1 : 0,
        ];
        // sira kolonu varsa en sona ekle
        if (Schema::hasColumn('domain_fiyatlar', 'sira')) {
            $insert['sira'] = ((int) DB::table('domain_fiyatlar')->max('sira')) + 1;
        }
        DB::table('domain_fiyatlar')->insert($insert);
        
        return redirect()->route('admin.domain.fiyatlar.index')->with('success', 'Alan adı fiyatı başarıyla eklendi.');
    }
    
    public function fiyatDuzenle($id)
    {
        $table = $this->resolveDomainTable(forWrite: true);
        
        // Legacy tablo kontrolü (id formatı: "legacy_id-index")
        if (strpos($id, '-') !== false && Schema::hasTable('alanadi')) {
            [$legacyId, $index] = explode('-', $id, 2);
            $row = DB::table('alanadi')->where('id', $legacyId)->first();
            
            if (!$row) {
                return redirect()->route('admin.domain.fiyatlar.index')->with('error', 'Fiyat bulunamadı.');
            }
            
            $extensions = $this->decodeJsonArray($row->uzanti);
            $registerPrices = $this->decodeJsonArray($row->kayit);
            $renewPrices = $this->decodeJsonArray($row->yenileme);
            
            $fiyat = (object) [
                'id' => $id,
                'legacy_id' => $legacyId,
                'legacy_index' => (int) $index,
                'uzanti' => $extensions[(int) $index] ?? '',
                'kayit_fiyat' => (float) ($registerPrices[(int) $index] ?? 0),
                'yenileme_fiyat' => (float) ($renewPrices[(int) $index] ?? 0),
                'transfer_fiyat' => 0,
                'durum' => 1,
                'is_legacy' => true,
            ];
            
            return view('admin.domain.fiyatlar.duzenle', compact('fiyat'));
        }
        
        if ($table !== 'domain_fiyatlar') {
            return redirect()
                ->route('admin.domain.fiyatlar.index')
                ->with('error', 'Alan adı fiyat tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }

        $fiyat = DB::table('domain_fiyatlar')->where('id', $id)->first();
        
        if (!$fiyat) {
            return redirect()->route('admin.domain.fiyatlar.index')->with('error', 'Fiyat bulunamadı.');
        }
        
        $fiyat->is_legacy = false;
        return view('admin.domain.fiyatlar.duzenle', compact('fiyat'));
    }
    
    public function fiyatDuzenlePost(Request $request, $id)
    {
        $validated = $request->validate([
            'uzanti' => 'required|string|max:50',
            'kayit_fiyat' => 'required|numeric',
            'yenileme_fiyat' => 'required|numeric',
            'transfer_fiyat' => 'required|numeric',
        ]);
        
        // Legacy tablo kontrolü (id formatı: "legacy_id-index")
        if (strpos($id, '-') !== false && Schema::hasTable('alanadi')) {
            [$legacyId, $index] = explode('-', $id, 2);
            $row = DB::table('alanadi')->where('id', $legacyId)->first();
            
            if (!$row) {
                return redirect()->route('admin.domain.fiyatlar.index')->with('error', 'Fiyat bulunamadı.');
            }
            
            $extensions = $this->decodeJsonArray($row->uzanti);
            $registerPrices = $this->decodeJsonArray($row->kayit);
            $renewPrices = $this->decodeJsonArray($row->yenileme);
            
            $indexInt = (int) $index;
            
            // İlgili index'i güncelle
            $extensions[$indexInt] = $validated['uzanti'];
            $registerPrices[$indexInt] = (float) $validated['kayit_fiyat'];
            $renewPrices[$indexInt] = (float) $validated['yenileme_fiyat'];
            
            // JSON'a çevir ve güncelle
            DB::table('alanadi')->where('id', $legacyId)->update([
                'uzanti' => json_encode($extensions),
                'kayit' => json_encode($registerPrices),
                'yenileme' => json_encode($renewPrices),
            ]);
            
            return redirect()->route('admin.domain.fiyatlar.index')->with('success', 'Alan adı fiyatı başarıyla güncellendi.');
        }
        
        $table = $this->resolveDomainTable(forWrite: true);
        if ($table !== 'domain_fiyatlar') {
            return redirect()
                ->route('admin.domain.fiyatlar.index')
                ->with('error', 'Alan adı fiyat tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }
        
        DB::table('domain_fiyatlar')->where('id', $id)->update([
            'uzanti' => $validated['uzanti'],
            'kayit_fiyat' => $validated['kayit_fiyat'],
            'yenileme_fiyat' => $validated['yenileme_fiyat'],
            'transfer_fiyat' => $validated['transfer_fiyat'],
            'durum' => $request->has('durum') ? 1 : 0,
        ]);
        
        return redirect()->route('admin.domain.fiyatlar.index')->with('success', 'Alan adı fiyatı başarıyla güncellendi.');
    }
    
    public function fiyatSil($id)
    {
        $table = $this->resolveDomainTable(forWrite: true);
        if ($table !== 'domain_fiyatlar') {
            return redirect()
                ->route('admin.domain.fiyatlar.index')
                ->with('error', 'Alan adı fiyat tablosu bulunamadığı için işlem gerçekleştirilemedi.');
        }

        DB::table('domain_fiyatlar')->where('id', $id)->delete();
        
        return redirect()->route('admin.domain.fiyatlar.index')->with('success', 'Alan adı fiyatı başarıyla silindi.');
    }
    
    // Alan Adı Satışları
    public function satislar()
    {
        $tableMissing = !Schema::hasTable('domain_satislar');

        $satislar = $tableMissing
            ? $this->emptyPaginator()
            : DB::table('domain_satislar')
                ->orderBy('id', 'desc')
                ->paginate(20);
        
        return view('admin.domain.satislar.index', compact('satislar', 'tableMissing'));
    }

    private function emptyPaginator(int $perPage = 20): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    private function resolveDomainTable(bool $allowLegacy = true, bool $forWrite = false): ?string
    {
        if (Schema::hasTable('domain_fiyatlar')) {
            return 'domain_fiyatlar';
        }

        if ($allowLegacy && Schema::hasTable('alanadi')) {
            return 'alanadi';
        }

        if ($forWrite) {
            return null;
        }

        return null;
    }

    private function legacyDomainPaginator(): LengthAwarePaginator
    {
        $rows = DB::table('alanadi')->orderBy('id', 'asc')->get();

        $entries = new Collection();
        foreach ($rows as $row) {
            $extensions = $this->decodeJsonArray($row->uzanti);
            $registerPrices = $this->decodeJsonArray($row->kayit);
            $renewPrices = $this->decodeJsonArray($row->yenileme);

            foreach ($extensions as $index => $extension) {
                $entries->push((object) [
                    'id' => sprintf('%s-%s', $row->id, $index),
                    'legacy_id' => $row->id,
                    'uzanti' => $extension,
                    'kayit_fiyat' => (float) ($registerPrices[$index] ?? 0),
                    'yenileme_fiyat' => (float) ($renewPrices[$index] ?? 0),
                    'transfer_fiyat' => 0,
                    'durum' => 1,
                ]);
            }
        }

        return $this->paginateCollection($entries, 20);
    }

    private function decodeJsonArray(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function paginateCollection(Collection $collection, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}