<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HizmetFiyatlariController extends Controller
{
    public function index()
    {
        $fiyatlar = DB::table('hizmet_fiyatlari')->orderBy('sira')->get();
        return view('admin.hizmet-fiyatlari.index', compact('fiyatlar'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'fiyat'         => 'required|array',
            'fiyat.*'       => 'nullable|numeric|min:0',
            'yeni_etiket'   => 'nullable|string|max:200',
            'yeni_fiyat'    => 'nullable|numeric|min:0',
        ]);

        foreach ($request->fiyat as $id => $tutar) {
            DB::table('hizmet_fiyatlari')->where('id', $id)->update([
                'fiyat'      => (float) $tutar,
                'updated_at' => now(),
            ]);
        }

        // Yeni hizmet ekleme
        if ($request->filled('yeni_etiket') && $request->filled('yeni_fiyat')) {
            $anahtar = \Illuminate\Support\Str::slug($request->yeni_etiket, '_');
            DB::table('hizmet_fiyatlari')->insertOrIgnore([
                'anahtar'    => $anahtar,
                'etiket'     => $request->yeni_etiket,
                'fiyat'      => (float) $request->yeni_fiyat,
                'sira'       => 99,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Fiyatlar güncellendi.');
    }

    public function destroy(int $id)
    {
        DB::table('hizmet_fiyatlari')->where('id', $id)->delete();
        return back()->with('success', 'Hizmet silindi.');
    }
}
