<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArgeAnketi;

/**
 * Ar-Ge Anketi (Rubito) cevapları — yönetim.
 */
class ArgeAnketiController extends Controller
{
    public function index()
    {
        $anketler = ArgeAnketi::orderByDesc('id')->paginate(20);
        $okunmamis = ArgeAnketi::where('okundu', 0)->count();
        return view('admin.arge-anketleri.index', compact('anketler', 'okunmamis'));
    }

    public function goster(int $id)
    {
        $anket = ArgeAnketi::findOrFail($id);
        if (!$anket->okundu) {
            $anket->okundu = 1;
            $anket->save();
        }
        return view('admin.arge-anketleri.goster', compact('anket'));
    }

    public function sil(int $id)
    {
        ArgeAnketi::where('id', $id)->delete();
        return redirect()->route('admin.arge-anketleri.index')->with('success', 'Anket cevabı silindi.');
    }
}
