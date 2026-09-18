<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\TranslationHelper;

class GlobalDataComposer
{
    public function compose(View $view)
    {
        $languageId = session('k_dil', 1);
        
        // Ayarlar
        $ayarlar = null;
        try {
            $ayarlar = DB::table('ayarlar')->first();
        } catch (\Exception $e) {
            // Tablo yoksa boş bırak
        }
        
        // Modüller
        $moduller = null;
        try {
            $moduller = DB::table('moduller')->where('id', 1)->first();
        } catch (\Exception $e) {
            // Tablo yoksa boş bırak
        }
        
        // Arka plan
        $arkaplan = null;
        try {
            $arkaplan = DB::table('arka_plan')->where('id', 1)->first();
        } catch (\Exception $e) {
            // Tablo yoksa boş bırak
        }
        
        // Menüler - Ana Menü
        $menuler = collect([]);
        try {
            if (DB::getSchemaBuilder()->hasTable('menuler')) {
                $menuler = DB::table('menuler')
                    ->where('durum', 1)
                    ->where('ustid', 0)
                    ->when(Schema::hasColumn('menuler', 'dil'), function ($query) use ($languageId) {
                        return $query->where('dil', $languageId);
                    })
                    ->orderBy('sira', 'asc')
                    ->get();

                if ($menuler->isEmpty() && $languageId !== 1) {
                    $menuler = DB::table('menuler')
                        ->where('durum', 1)
                        ->where('ustid', 0)
                        ->when(Schema::hasColumn('menuler', 'dil'), function ($query) {
                            return $query->where('dil', 1);
                        })
                        ->orderBy('sira', 'asc')
                        ->get()
                        ->map(function ($menu) {
                            $menu->adi = TranslationHelper::translate($menu->adi);
                            return $menu;
                        });
                }

                // Her menü için alt menüleri çek
                foreach ($menuler as $menu) {
                    $altMenuQuery = DB::table('menuler')
                        ->where('durum', 1)
                        ->where('ustid', $menu->id)
                        ->when(Schema::hasColumn('menuler', 'dil'), function ($query) use ($languageId) {
                            return $query->where('dil', $languageId);
                        })
                        ->orderBy('sira', 'asc');

                    $menu->altmenu = $altMenuQuery->get();

                    if ($menu->altmenu->isEmpty() && $languageId !== 1) {
                        $menu->altmenu = DB::table('menuler')
                            ->where('durum', 1)
                            ->where('ustid', $menu->id)
                            ->when(Schema::hasColumn('menuler', 'dil'), function ($query) {
                                return $query->where('dil', 1);
                            })
                            ->orderBy('sira', 'asc')
                            ->get()
                            ->map(function ($alt) {
                                $alt->adi = TranslationHelper::translate($alt->adi);
                                return $alt;
                            });
                    }
                }
            }
        } catch (\Exception $e) {
            // Tablo yoksa boş collection
        }
        
        // Diller
        $diller = collect([]);
        try {
            if (DB::getSchemaBuilder()->hasTable('diller')) {
                $diller = DB::table('diller')->where('durum', 1)->get();
            }
        } catch (\Exception $e) {
            // Tablo yoksa boş collection
        }
        
        // Sepet sayısı (eğer giriş yapılmışsa)
        $sepet_sayisi = 0;
        try {
            if (auth()->guard('uye')->check() && DB::getSchemaBuilder()->hasTable('sepet')) {
                $sepet_sayisi = DB::table('sepet')
                    ->where('user_id', auth()->guard('uye')->id())
                    ->count();
            }
        } catch (\Exception $e) {
            // Hata varsa 0 olarak bırak
        }
        
        // Navbar Ayarları (Top Bar Elementleri)
        $navbarSettings = null;
        try {
            if (DB::getSchemaBuilder()->hasTable('navbar_settings')) {
                $navbarSettings = DB::table('navbar_settings')->first();
                if ($navbarSettings) {
                    // JSON alanları decode et
                    $navbarSettings->currency_options = json_decode($navbarSettings->currency_options ?? '[]', true);
                    $navbarSettings->language_options = json_decode($navbarSettings->language_options ?? '[]', true);
                }
            }
        } catch (\Exception $e) {
            // Tablo yoksa null bırak
        }
        
        $view->with([
            'ayarlar' => $ayarlar,
            'moduller' => $moduller,
            'arkaplan' => $arkaplan,
            'menuler' => $menuler,
            'diller' => $diller,
            'sepet_sayisi' => $sepet_sayisi,
            'navbarSettings' => $navbarSettings,
        ]);
    }
}

