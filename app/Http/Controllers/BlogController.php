<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Ayar;
use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BlogController extends Controller
{
    public function index()
    {
        $ayarlar = Ayar::first();
        $locale = app()->getLocale();

        $bloglar = Blog::where('durum', 1)
            ->orderBy('tarih', 'desc')
            ->paginate(12)
            ->appends(request()->query());

        $bloglar->getCollection()->transform(function ($blog) use ($locale) {
            // Mevcut locale'e göre çeviri alanlarını kullan
            if ($locale === 'en' && Schema::hasColumn('blog', 'adi_en') && !empty($blog->adi_en)) {
                $blog->adi = $blog->adi_en;
            } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'adi_ar') && !empty($blog->adi_ar)) {
                $blog->adi = $blog->adi_ar;
            }
            
            if ($locale === 'en' && Schema::hasColumn('blog', 'aciklama_en') && !empty($blog->aciklama_en)) {
                $blog->aciklama = $blog->aciklama_en;
            } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'aciklama_ar') && !empty($blog->aciklama_ar)) {
                $blog->aciklama = $blog->aciklama_ar;
            }
            
            return $blog;
        });

        $kategoriler = collect([]);
        try {
            if (DB::getSchemaBuilder()->hasTable('blog_kategori')) {
                $kategoriler = DB::table('blog_kategori')
                    ->where('durum', 1)
                    ->orderBy('sira', 'asc')
                    ->get()
                    ->map(function ($kategori) {
                        $kategori->adi = TranslationHelper::translate($kategori->adi);
                        return $kategori;
                    });
            }
        } catch (\Exception $e) {
            // tablo yoksa boş bırak
        }

        $son_yazilar = Blog::where('durum', 1)
            ->orderBy('tarih', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($blog) use ($locale) {
                if ($locale === 'en' && Schema::hasColumn('blog', 'adi_en') && !empty($blog->adi_en)) {
                    $blog->adi = $blog->adi_en;
                } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'adi_ar') && !empty($blog->adi_ar)) {
                    $blog->adi = $blog->adi_ar;
                }
                return $blog;
            });

        $arkaplan = DB::table('arka_plan')->first();

        return view('tema.blog', compact('ayarlar', 'bloglar', 'kategoriler', 'son_yazilar', 'arkaplan'));
    }
    
    public function show($seo)
    {
        $ayarlar = Ayar::first();
        $locale = app()->getLocale();
        $blog = Blog::where('seo', $seo)
            ->where('durum', 1)
            ->firstOrFail();

        // Hit sayısını artır (kolon varsa)
        if (Schema::hasColumn('blog', 'hit')) {
            $blog->increment('hit');
        }

        // Mevcut locale'e göre çeviri alanlarını kullan
        if ($locale === 'en' && Schema::hasColumn('blog', 'adi_en') && !empty($blog->adi_en)) {
            $blog->adi = $blog->adi_en;
        } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'adi_ar') && !empty($blog->adi_ar)) {
            $blog->adi = $blog->adi_ar;
        }
        
        if ($locale === 'en' && Schema::hasColumn('blog', 'aciklama_en') && !empty($blog->aciklama_en)) {
            $blog->aciklama = $blog->aciklama_en;
        } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'aciklama_ar') && !empty($blog->aciklama_ar)) {
            $blog->aciklama = $blog->aciklama_ar;
        }
        
        // İlgili bloglar
        $ilgili_bloglarQuery = Blog::where('durum', 1)
            ->where('id', '!=', $blog->id);
        
        // Hit kolonu varsa ona göre sırala, yoksa tarihe göre
        if (Schema::hasColumn('blog', 'hit')) {
            $ilgili_bloglarQuery->orderBy('hit', 'desc');
        } else {
            $ilgili_bloglarQuery->orderBy('tarih', 'desc');
        }
        
        $ilgili_bloglar = $ilgili_bloglarQuery
            ->limit(3)
            ->get()
            ->map(function ($item) use ($locale) {
                if ($locale === 'en' && Schema::hasColumn('blog', 'adi_en') && !empty($item->adi_en)) {
                    $item->adi = $item->adi_en;
                } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'adi_ar') && !empty($item->adi_ar)) {
                    $item->adi = $item->adi_ar;
                }
                return $item;
            });
        
        // Kategoriler (tablo varsa)
        $kategoriler = collect([]);
        try {
            if (DB::getSchemaBuilder()->hasTable('blog_kategori')) {
                $kategoriler = DB::table('blog_kategori')
                    ->where('durum', 1)
                    ->orderBy('sira', 'asc')
                    ->get()
                    ->map(function ($kategori) {
                        $kategori->adi = TranslationHelper::translate($kategori->adi);
                        $kategori->blog_count = Schema::hasColumn('blog', 'kategori')
                            ? DB::table('blog')->where('kategori', $kategori->id)->where('durum', 1)->count()
                            : 0;
                        return $kategori;
                    });
            }
        } catch (\Exception $e) {
            // Tablo yoksa boş bırak
        }
        
        // Son yazılar
        $son_yazilarQuery = Blog::where('durum', 1)
            ->where('id', '!=', $blog->id);

        // Sıralama kolonu dinamik: önce created_at, yoksa tarih
        if (Schema::hasColumn('blog', 'created_at')) {
            $son_yazilarQuery->orderBy('created_at', 'desc');
        } elseif (Schema::hasColumn('blog', 'tarih')) {
            $son_yazilarQuery->orderBy('tarih', 'desc');
        }

        $son_yazilar = $son_yazilarQuery
            ->limit(5)
            ->get()
            ->map(function ($item) use ($locale) {
                if ($locale === 'en' && Schema::hasColumn('blog', 'adi_en') && !empty($item->adi_en)) {
                    $item->adi = $item->adi_en;
                } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'adi_ar') && !empty($item->adi_ar)) {
                    $item->adi = $item->adi_ar;
                }
                return $item;
            });
        
        // Arka plan
        $arkaplan = \DB::table('arka_plan')->first();
        
        return view('tema.blog-detay', compact('ayarlar', 'blog', 'ilgili_bloglar', 'kategoriler', 'son_yazilar', 'arkaplan'));
    }
    
    public function kategori($kategori)
    {
        $ayarlar = Ayar::first();
        $locale = app()->getLocale();
        $perPage = 12;

        $bloglarQuery = Blog::where('durum', 1)->orderBy('tarih', 'desc');
        // blog tablosunda 'kategori' kolonu yoksa filtre uygulanamaz (500 yerine boş liste)
        if (Schema::hasColumn('blog', 'kategori')) {
            $bloglarQuery->where('kategori', $kategori);
        } else {
            $bloglarQuery->whereRaw('1=0');
        }
        $bloglar = $bloglarQuery->paginate($perPage)->appends(request()->query());

        $bloglar->getCollection()->transform(function ($blog) use ($locale) {
            if ($locale === 'en' && Schema::hasColumn('blog', 'adi_en') && !empty($blog->adi_en)) {
                $blog->adi = $blog->adi_en;
            } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'adi_ar') && !empty($blog->adi_ar)) {
                $blog->adi = $blog->adi_ar;
            }
            
            if ($locale === 'en' && Schema::hasColumn('blog', 'aciklama_en') && !empty($blog->aciklama_en)) {
                $blog->aciklama = $blog->aciklama_en;
            } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'aciklama_ar') && !empty($blog->aciklama_ar)) {
                $blog->aciklama = $blog->aciklama_ar;
            }
            
            return $blog;
        });

        $kategoriler = collect([]);
        try {
            if (DB::getSchemaBuilder()->hasTable('blog_kategori')) {
                $kategoriler = DB::table('blog_kategori')
                    ->where('durum', 1)
                    ->orderBy('sira', 'asc')
                    ->get()
                    ->map(function ($kat) {
                        $kat->adi = TranslationHelper::translate($kat->adi);
                        return $kat;
                    });
            }
        } catch (\Exception $e) {
            // Tablo yoksa boş bırak
        }

        $son_yazilar = Blog::where('durum', 1)
            ->orderBy('tarih', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($blog) use ($locale) {
                if ($locale === 'en' && Schema::hasColumn('blog', 'adi_en') && !empty($blog->adi_en)) {
                    $blog->adi = $blog->adi_en;
                } elseif ($locale === 'ar' && Schema::hasColumn('blog', 'adi_ar') && !empty($blog->adi_ar)) {
                    $blog->adi = $blog->adi_ar;
                }
                return $blog;
            });

        $arkaplan = DB::table('arka_plan')->first();
        
        return view('tema.blog', compact('ayarlar', 'bloglar', 'kategoriler', 'son_yazilar', 'arkaplan', 'kategori'));
    }
}
