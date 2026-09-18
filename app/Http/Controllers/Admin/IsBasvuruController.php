<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IsBasvuru;

/**
 * İş Başvuruları (Rubito) — yönetim.
 */
class IsBasvuruController extends Controller
{
    public function index()
    {
        $basvurular = IsBasvuru::orderByDesc('id')->paginate(20);
        $okunmamis  = IsBasvuru::where('okundu', 0)->count();
        return view('admin.is-basvurulari.index', compact('basvurular', 'okunmamis'));
    }

    public function goster(int $id)
    {
        $basvuru = IsBasvuru::findOrFail($id);
        if (!$basvuru->okundu) {
            $basvuru->okundu = 1;
            $basvuru->save();
        }
        return view('admin.is-basvurulari.goster', compact('basvuru'));
    }

    public function sil(int $id)
    {
        $basvuru = IsBasvuru::find($id);
        if ($basvuru) {
            // CV dosyasını da temizle
            if ($basvuru->cv_dosya && is_file(public_path($basvuru->cv_dosya))) {
                @unlink(public_path($basvuru->cv_dosya));
            }
            $basvuru->delete();
        }
        return redirect()->route('admin.is-basvurulari.index')->with('success', 'İş başvurusu silindi.');
    }
}
