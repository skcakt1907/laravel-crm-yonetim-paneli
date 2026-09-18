<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EkHizmet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ek Hizmetler yönetimi (madde 15).
 * Paket/ilan detayında "Yanında Satın Alınabilecekler" bölümünde gösterilir.
 */
class EkHizmetController extends Controller
{
    public function index()
    {
        $hizmetler = EkHizmet::orderBy('sira')->orderBy('id')->get();

        // Kapsam seçimi için kategori listesi (paketlerin kategori tablosu: web_kategori)
        $kategoriler = collect([]);
        try {
            if (Schema::hasTable('web_kategori')) {
                $kategoriler = DB::table('web_kategori')
                    ->select('id', 'adi')
                    ->where('durum', 1)
                    ->when(Schema::hasColumn('web_kategori', 'dil'), fn ($q) => $q->where('dil', 1))
                    ->orderBy('adi')
                    ->get();
            }
        } catch (\Throwable $e) {
        }

        return view('admin.ek-hizmetler.index', compact('hizmetler', 'kategoriler'));
    }

    public function store(Request $request)
    {
        $data = $this->dogrula($request);
        EkHizmet::create($data);
        return back()->with('success', '"' . $data['ad'] . '" ek hizmeti eklendi.');
    }

    public function update(Request $request, int $id)
    {
        $hizmet = EkHizmet::findOrFail($id);
        $hizmet->update($this->dogrula($request));
        return back()->with('success', 'Ek hizmet güncellendi.');
    }

    public function destroy(int $id)
    {
        $hizmet = EkHizmet::findOrFail($id);
        $ad = $hizmet->ad;
        $hizmet->delete();
        return back()->with('success', '"' . $ad . '" silindi.');
    }

    public function toggle(int $id)
    {
        $hizmet = EkHizmet::findOrFail($id);
        $hizmet->durum = $hizmet->durum ? 0 : 1;
        $hizmet->save();
        return back()->with('success', 'Durum güncellendi.');
    }

    private function dogrula(Request $request): array
    {
        $data = $request->validate([
            'ad'            => 'required|string|max:190',
            'fiyat'         => 'required|numeric|min:0',
            'ikon'          => 'nullable|string|max:60',
            'aciklama'      => 'nullable|string|max:1000',
            'kategoriler'   => 'nullable|array',
            'kategoriler.*' => 'integer|min:1',
            'sira'          => 'nullable|integer|min:0',
            'durum'         => 'nullable|in:0,1',
        ]);
        $data['sira']  = $data['sira'] ?? 0;
        $data['durum'] = $request->has('durum') ? (int) $data['durum'] : 1;

        // Kapsam: seçilen kategori id'leri virgüllü saklanır. Hiç seçilmezse boş = tüm kategoriler.
        $data['kategoriler'] = !empty($data['kategoriler'])
            ? implode(',', array_unique($data['kategoriler']))
            : null;

        return $data;
    }
}
