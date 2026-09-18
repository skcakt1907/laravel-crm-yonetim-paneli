<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sayfa;
use App\Models\Ayar;
use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SayfaController extends Controller
{
    public function show($seo)
    {
        $ayar = Ayar::first();
        $sayfa = Sayfa::where('seo', $seo)
            ->where('durum', 1)
            ->when(Schema::hasColumn('sayfalar', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->firstOrFail();

        // Hit sayısını artır (kolon yoksa atla)
        if (Schema::hasColumn('sayfalar', 'hit')) {
            $sayfa->increment('hit');
        }

        $sayfa->baslik = TranslationHelper::translate($sayfa->adi ?? '');
        $sayfa->icerik = TranslationHelper::translate($sayfa->aciklama ?? '');
        
        // Builder içeriğini kontrol et
        if ($sayfa->builder_content) {
            try {
                $builderData = json_decode($sayfa->builder_content, true);
                if (!is_array($builderData) || !isset($builderData['html'])) {
                    // Geçersiz format, builder_content'i null yap
                    $sayfa->builder_content = null;
                }
            } catch (\Exception $e) {
                // Hata durumunda builder_content'i null yap
                $sayfa->builder_content = null;
            }
        }
        
        // Arka plan
        $arkaplan = DB::table('arka_plan')->first();
        
        return view('tema.sayfa', compact('ayar', 'sayfa', 'arkaplan'));
    }
}
