<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ayar;
use App\Models\Sayfa;
use App\Models\Blog;
use App\Models\Hizmet;
use Illuminate\Support\Facades\DB;
use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\Schema;
use App\Models\Satilan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\PaytrService;
use App\Services\IyzicoService;

class HomeController extends Controller
{
    public function index()
    {
        // Slider verilerini kaynak (Türkçe) dilden çekip hedef dile çevir
        $sliders = DB::table('slider')
            ->where('durum', 1)
            ->when(Schema::hasColumn('slider', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->when(Schema::hasColumn('slider', 'sira'), function ($query) {
                return $query->orderBy('sira', 'asc');
            }, function ($query) {
                return $query->orderBy('id', 'asc');
            })
            ->get()
            ->map(function ($slider) {
                if (isset($slider->baslik)) {
                    $slider->baslik = TranslationHelper::translateField('slider', $slider->id, 'baslik', $slider->baslik);
                }
                if (isset($slider->aciklama)) {
                    $slider->aciklama = TranslationHelper::translateField('slider', $slider->id, 'aciklama', $slider->aciklama);
                }
                return $slider;
            });

        $alanadi = null;
        try {
            if (Schema::hasTable('alanadi')) {
                $alanadi = DB::table('alanadi')->where('id', 1)->first();
                if ($alanadi) {
                    $alanadi->uzanti = json_decode($alanadi->uzanti ?? '[]', true);
                    $alanadi->kayit = json_decode($alanadi->kayit ?? '[]', true);
                    $alanadi->yenileme = json_decode($alanadi->yenileme ?? '[]', true);
                }
            }
        } catch (\Exception $e) {
            // Tablo yoksa null
        }

        // resim_blob ve resim_mime sütunlarını hariç tut (bellek tasarrufu)
        $yazilimColumns = collect(Schema::getColumnListing('yazilimlar'))
            ->reject(fn($col) => in_array($col, ['resim_blob', 'resim_mime']))
            ->toArray();
        
        // Ana sayfada gosterilecek 3 gercek paket:
        //  - Once anasayfa=1 isaretli olanlar (varsa)
        //  - Sonra en yeni aktif paketler ile 3'e tamamla
        $yazilimQuery = DB::table('yazilimlar')
            ->select($yazilimColumns)
            ->selectSub(function ($q) {
                $q->from('yazilim_hit')->selectRaw('COALESCE(SUM(sayac),0)')->whereColumn('yid', 'yazilimlar.id');
            }, 'hit_count')
            ->selectSub(function ($q) {
                $q->from('satilan')->selectRaw('COUNT(*)')->where('tipi', 2)->whereColumn('adi', 'yazilimlar.adi');
            }, 'satis_count')
            ->where('durum', 1)
            ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            // Musteriye ozel teklif paketlerini (musteri != 0) ana sayfadan gizle
            ->when(Schema::hasColumn('yazilimlar', 'musteri'), function ($query) {
                return $query->where('musteri', 0);
            })
            // "Teklif" iceren basliklari da gizle
            ->where(function ($query) {
                $query->where('adi', 'not like', '%teklif%')
                      ->where('seo', 'not like', '%teklif%');
            });

        // "Öne Çıkan Paketler" artık 3'lü grid değil, yatay şerit (Tatilim Sensin şeması)
        // -> şeridin anlamlı olması için 10 kart.
        $oneCikanAdet = 10;

        // Yonetim panelinden "Anasayfada Goster" isaretli paketleri al
        $oncelikli = collect();
        if (Schema::hasColumn('yazilimlar', 'anasayfa')) {
            $oncelikli = (clone $yazilimQuery)
                ->where('anasayfa', 1)
                ->when(Schema::hasColumn('yazilimlar', 'sira'), fn($q) => $q->orderBy('sira', 'desc'), fn($q) => $q->orderBy('id', 'desc'))
                ->limit($oneCikanAdet)
                ->get();
        }

        // Eksik kalirsa (isaretli paket yeterli degilse) en yeni paketlerle tamamla
        $eksik = $oneCikanAdet - $oncelikli->count();
        if ($eksik > 0) {
            $tamamlama = (clone $yazilimQuery)
                ->when($oncelikli->isNotEmpty(), fn($q) => $q->whereNotIn('id', $oncelikli->pluck('id')->all()))
                ->when(Schema::hasColumn('yazilimlar', 'sira'), fn($q) => $q->orderBy('sira', 'desc'), fn($q) => $q->orderBy('id', 'desc'))
                ->limit($eksik)
                ->get();
            $yazilimlar = $oncelikli->concat($tamamlama);
        } else {
            $yazilimlar = $oncelikli;
        }
        
        // Paket resimlerini webpaketresim tablosundan yükle
        $package_ids = $yazilimlar->pluck('id')->toArray();
        $package_images = [];
        if (!empty($package_ids) && DB::getSchemaBuilder()->hasTable('webpaketresim')) {
            $images = DB::table('webpaketresim')
                ->whereIn('rid', $package_ids)
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('rid');
            
            foreach ($images as $rid => $resimGrubu) {
                $package_images[$rid] = $resimGrubu->first()->resim;
            }
        }
        
        $yazilimlar = $yazilimlar->map(function ($item) use ($package_images) {
            if (isset($item->adi)) {
                $item->adi = TranslationHelper::translateField('yazilimlar', $item->id, 'adi', $item->adi);
            }
            if (isset($item->kisa)) {
                $item->kisa = TranslationHelper::translateField('yazilimlar', $item->id, 'kisa', $item->kisa);
            }
            if (empty($item->resim) && isset($package_images[$item->id])) {
                $item->resim = $package_images[$item->id];
            }
            return $item;
        });

        $kurumsal = DB::table('sayfalar')
            ->where('durum', 1)
            ->when(Schema::hasColumn('sayfalar', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->orderBy('id', 'asc')
            ->first();

        if ($kurumsal) {
            if (isset($kurumsal->baslik)) {
                $kurumsal->baslik = TranslationHelper::translateField('sayfalar', $kurumsal->id, 'baslik', $kurumsal->baslik);
            }
            if (isset($kurumsal->icerik)) {
                $kurumsal->icerik = TranslationHelper::translateField('sayfalar', $kurumsal->id, 'icerik', $kurumsal->icerik);
            }
        }

        $hosting_kategoriler = DB::table('hosting_kategori')
            ->where('durum', 1)
            ->when(Schema::hasColumn('hosting_kategori', 'anasayfa'), function ($query) {
                return $query->where('anasayfa', 1);
            })
            ->when(Schema::hasColumn('hosting_kategori', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->when(Schema::hasColumn('hosting_kategori', 'sira'), function ($query) {
                return $query->orderBy('sira', 'asc');
            }, function ($query) {
                return $query->orderBy('id', 'asc');
            })
            ->get()
            ->map(function ($item) {
                if (isset($item->adi)) {
                    $item->adi = TranslationHelper::translateField('hosting_kategori', $item->id, 'adi', $item->adi);
                }
                return $item;
            });

        foreach ($hosting_kategoriler as $kat) {
            $hostingQuery = DB::table('hostingler')
                ->where('durum', 1)
                ->when(Schema::hasColumn('hostingler', 'anasayfa'), function ($query) {
                    return $query->where('anasayfa', 1);
                })
                ->when(Schema::hasColumn('hostingler', 'kategori'), function ($query) use ($kat) {
                    return $query->where('kategori', $kat->id);
                })
                ->when(Schema::hasColumn('hostingler', 'kategori_id'), function ($query) use ($kat) {
                    return $query->where('kategori_id', $kat->id);
                })
                ->when(Schema::hasColumn('hostingler', 'dil'), function ($query) {
                    return $query->where('dil', 1);
                });
            
            // Eğer kategori kolonu yoksa tüm hostingleri göster
            if (!Schema::hasColumn('hostingler', 'kategori') && !Schema::hasColumn('hostingler', 'kategori_id')) {
                $hostingQuery = DB::table('hostingler')
                    ->where('durum', 1)
                    ->when(Schema::hasColumn('hostingler', 'anasayfa'), function ($query) {
                        return $query->where('anasayfa', 1);
                    })
                    ->when(Schema::hasColumn('hostingler', 'dil'), function ($query) {
                        return $query->where('dil', 1);
                    });
            }
            
            $kat->hostingler = $hostingQuery
                ->when(Schema::hasColumn('hostingler', 'sira'), function ($query) {
                    return $query->orderBy('sira', 'asc');
                })
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($hosting) {
                    if (isset($hosting->adi)) {
                        $hosting->adi = TranslationHelper::translateField('hostingler', $hosting->id, 'adi', $hosting->adi);
                    }
                    return $hosting;
                });
        }

        $referanslar = DB::table('referanslar')
            ->where('durum', 1)
            ->when(Schema::hasColumn('referanslar', 'anasayfa'), function ($query) {
                return $query->where('anasayfa', 1);
            })
            ->when(Schema::hasColumn('referanslar', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->when(Schema::hasColumn('referanslar', 'a_sira'), function ($query) {
                return $query->orderBy('a_sira', 'asc');
            }, function ($query) {
                return $query->when(Schema::hasColumn('referanslar', 'sira'), function ($q) {
                    return $q->orderBy('sira', 'asc');
                }, function ($q) {
                    return $q->orderBy('id', 'asc');
                });
            })
            ->get()
            ->map(function ($referans) {
                if (isset($referans->adi)) {
                    $referans->adi = TranslationHelper::translateField('referanslar', $referans->id, 'adi', $referans->adi);
                }
                if (isset($referans->kisa)) {
                    $referans->kisa = TranslationHelper::translateField('referanslar', $referans->id, 'kisa', $referans->kisa);
                }
                return $referans;
            });

        $locale = app()->getLocale();
        $bloglar = DB::table('blog')
            ->where('durum', 1)
            ->when(Schema::hasColumn('blog', 'anasayfa'), function ($query) {
                return $query->where('anasayfa', 1);
            })
            ->when(Schema::hasColumn('blog', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($blog) use ($locale) {
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

        $moduller = null;
        try {
            if (Schema::hasTable('moduller')) {
                $moduller = DB::table('moduller')->first();
            }
        } catch (\Exception $e) {
            // Tablo yoksa null
        }
        
        $arkaplan = null;
        try {
            if (Schema::hasTable('arka_plan')) {
                $arkaplan = DB::table('arka_plan')->first();
            }
        } catch (\Exception $e) {
            // Tablo yoksa null
        }
        
        $ayarlar = null;
        try {
            if (Schema::hasTable('ayarlar')) {
                $ayarlar = DB::table('ayarlar')->first();
            }
        } catch (\Exception $e) {
            // Tablo yoksa null
        }

        // ── Kategori vitrinleri (yatay carousel — Tatilim Sensin şeması) ────
        // Her kategori icin: en cok incelenen paketler + puan ortalamasi + yorum adedi.
        // Sira = ana sayfada gorunecek sira. Paket sayisi/izlenmesi en yuksek kategoriler.
        $vitrinKategoriler = [];
        try {
            // Ana sayfa bölümleri = VARSAYILAN 4 KATEGORİ (her zaman) + admin'de
            // "Anasayfada Göster" işaretlenen paketlerin kendi kategorileri.
            // Bir paket yalnızca kendi birincil kategorisinin bölümünde görünür
            // (web paketi sosyal medya bölümüne sızmaz). İşaretli paketler kendi
            // bölümlerinin başına geçer; bölümün kalanı o kategorinin en çok
            // incelenen paketleriyle dolar.
            $varsayilanKatIds = [26, 27, 30, 35]; // Sosyal Medya / Dijital Pazarlama / Video Prodüksiyon / Web Hizmetleri
            $baslikKeys = [
                26 => 'vitrin_sosyal',
                27 => 'vitrin_dijital',
                30 => 'vitrin_produksiyon',
                35 => 'vitrin_web',
            ];

            // 1) Admin'de işaretli paketler -> hangi kategoriler bölüm olacak
            $secilenKatIds = [];
            if (Schema::hasColumn('yazilimlar', 'anasayfa')) {
                $isaretliler = DB::table('yazilimlar')
                    ->where('durum', 1)
                    ->when(Schema::hasColumn('yazilimlar', 'dil'), fn ($q) => $q->where('dil', 1))
                    ->when(Schema::hasColumn('yazilimlar', 'musteri'), fn ($q) => $q->where('musteri', 0))
                    ->where('anasayfa', 1)
                    ->get(['id', 'kategori']);

                foreach ($isaretliler as $p) {
                    // Birincil kategori = kategori listesindeki ilk ID
                    $kid = (int) trim(explode(',', (string) $p->kategori)[0]);
                    if ($kid > 0) {
                        $secilenKatIds[$kid] = true;
                    }
                }
            }

            // Varsayılan 4 kategori HER ZAMAN görünür (ana sayfa boşalmasın / eski görünüm
            // korunsun); admin'de işaretlenen paketlerin kategorileri bunlara EKLENİR.
            // SIRA: önce varsayılan 4'ü kendi klasik sırasıyla (Sosyal Medya en başta,
            // "★ Öne Çıkan" rozeti onda kalsın), sonra işaretlemeyle gelen ekstra
            // kategoriler (kategori yönetimindeki "sıra" alanına göre).
            $ekstraKatIds = array_values(array_diff(array_keys($secilenKatIds), $varsayilanKatIds));
            $ekstraSirali = empty($ekstraKatIds) ? [] : DB::table('web_kategori')
                ->whereIn('id', $ekstraKatIds)->where('durum', 1)
                ->orderBy('sira')->orderBy('id')
                ->pluck('id')->all();

            $katIdListesi = array_merge($varsayilanKatIds, $ekstraSirali);

            // 2) Kategori bilgileri (id => satır); sıralamayı yukarıdaki liste belirler
            $katKayitlari = DB::table('web_kategori')
                ->whereIn('id', $katIdListesi)
                ->where('durum', 1)
                ->get(['id', 'adi'])
                ->keyBy('id');

            $vitrinKatlar = collect($katIdListesi)
                ->map(fn ($kid) => $katKayitlari->get($kid))
                ->filter()
                ->values();

            $vitrinCols = collect(Schema::getColumnListing('yazilimlar'))
                ->reject(fn ($c) => in_array($c, ['resim_blob', 'resim_mime']))
                ->map(fn ($c) => 'y.' . $c)->toArray();

            $gosterilenPaketIds = []; // aynı paket iki bölümde çıkmasın

            foreach ($vitrinKatlar as $kat) {
                $kid = (int) $kat->id;
                $paketler = DB::table('yazilimlar as y')
                    ->leftJoin(DB::raw('(SELECT icerik_id, ROUND(AVG(puan),1) AS puan_ort, COUNT(*) AS yorum_adet
                                         FROM yorumlar WHERE tip = "paket" AND durum = 1 AND puan IS NOT NULL
                                         GROUP BY icerik_id) AS r'), 'r.icerik_id', '=', 'y.id')
                    ->leftJoin(DB::raw('(SELECT yid, COUNT(*) AS izlenme FROM yazilim_hit GROUP BY yid) AS h'),
                        'h.yid', '=', 'y.id')
                    ->where('y.durum', 1)
                    ->when(Schema::hasColumn('yazilimlar', 'dil'), fn ($q) => $q->where('y.dil', 1))
                    ->when(Schema::hasColumn('yazilimlar', 'musteri'), fn ($q) => $q->where('y.musteri', 0))
                    // Paket SADECE birincil kategorisinin bölümünde çıkar (çoklu kategori
                    // girilse bile web paketi sosyal medya bölümüne sızmaz)
                    ->whereRaw('CAST(SUBSTRING_INDEX(y.kategori, ",", 1) AS UNSIGNED) = ?', [$kid])
                    ->when(!empty($gosterilenPaketIds), fn ($q) => $q->whereNotIn('y.id', $gosterilenPaketIds))
                    ->select(array_merge($vitrinCols, [
                        'r.puan_ort', 'r.yorum_adet', DB::raw('COALESCE(h.izlenme, 0) AS izlenme'),
                    ]))
                    // Admin "Anasayfada Göster" seçimi önce gelsin, kalanı aynı kategorinin
                    // en çok incelenen paketleriyle tamamlansın
                    ->when(Schema::hasColumn('yazilimlar', 'anasayfa'), fn ($q) => $q->orderByDesc('y.anasayfa'))
                    ->orderByDesc(DB::raw('COALESCE(h.izlenme, 0)'))
                    ->limit(10)->get();

                if ($paketler->count()) {
                    $gosterilenPaketIds = array_merge($gosterilenPaketIds, $paketler->pluck('id')->all());
                    $vitrinKategoriler[] = [
                        'kategori'   => $kat,
                        'paketler'   => $paketler,
                        'baslik_key' => $baslikKeys[$kid] ?? null,
                        // İlk bölüm "★ Öne Çıkan" rozetiyle vurgulanır
                        'oneCikan'   => count($vitrinKategoriler) === 0,
                    ];
                }
            }
        } catch (\Throwable $e) {
            $vitrinKategoriler = [];
        }

        return view('tema.index', compact(
            'vitrinKategoriler',
            'sliders',
            'alanadi',
            'yazilimlar',
            'kurumsal',
            'hosting_kategoriler',
            'referanslar',
            'bloglar',
            'moduller',
            'arkaplan',
            'ayarlar'
        ));
    }
    
    public function iletisim()
    {
        $ayarlar = Ayar::first();
        return view('tema.iletisim', compact('ayarlar'));
    }
    
    public function iletisimPost(Request $request)
    {
        // İletişim formu işlemleri
        $validated = $request->validate([
            'isim' => 'required|string|max:255',
            'email' => 'required|email',
            'telefon' => 'nullable|string',
            'konu' => 'nullable|string',
            'mesaj' => 'required|string',
        ], [
            'isim.required' => 'Adınız soyadınız zorunludur.',
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'mesaj.required' => 'Mesaj alanı zorunludur.',
        ]);
        
        try {
            // İletişim mesajını veritabanına kaydet
            $data = [
                'isim' => $request->isim,
                'email' => $request->email,
                'telefon' => $request->telefon,
                'konu' => $request->konu ?? 'Genel İletişim',
                'mesaj' => $request->mesaj,
                'tarih' => date('Y-m-d H:i:s'),
                'durum' => 0, // Okunmadı
            ];
            
            // İletişim tablosu varsa kaydet
            if (\Schema::hasTable('iletisim')) {
                DB::table('iletisim')->insert($data);
            }
            
            // Mail bildirimi (opsiyonel)
            // Mail::to(config('mail.from.address'))->send(new IletisimMail($data));
            
            // Lang parametresini koru
            $redirectUrl = localized_route('iletisim');
            return redirect($redirectUrl)->with('success', 'Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapılacaktır.');
        } catch (\Exception $e) {
            // Lang parametresini koru
            $redirectUrl = localized_route('iletisim');
            return redirect($redirectUrl)->withInput()->with('error', 'Bir hata oluştu. Lütfen tekrar deneyiniz.');
        }
    }
    
    public function referanslar()
    {
        $ayar = Ayar::first();
        $referanslar = DB::table('referanslar')
            ->where('durum', 1)
            ->when(Schema::hasColumn('referanslar', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->when(Schema::hasColumn('referanslar', 'a_sira'), function ($query) {
                return $query->orderBy('a_sira', 'asc');
            }, function ($query) {
                return $query->when(Schema::hasColumn('referanslar', 'sira'), function ($q) {
                    return $q->orderBy('sira', 'asc');
                }, function ($q) {
                    return $q->orderBy('id', 'asc');
                });
            })
            ->get()
            ->map(function ($referans) {
                $referans->adi = TranslationHelper::translateField('referanslar', $referans->id, 'adi', $referans->adi);
                $referans->kisa = TranslationHelper::translateField('referanslar', $referans->id, 'kisa', $referans->kisa);
                return $referans;
            });

        return view('tema.referanslar', compact('ayar', 'referanslar'));
    }
    
    public function hosting($seo = null)
    {
        $ayar = Ayar::first();
        
        // YENİ TABLO ÖNCELİĞİ: admin 'hosting_paketler' tablosuna yazıyor. Varsa ondan oku.
        if (Schema::hasTable('hosting_paketler')) {
            $hp = DB::table('hosting_paketler')
                ->where('durum', 1)
                ->when(Schema::hasColumn('hosting_paketler', 'sira'), function ($q) {
                    return $q->orderBy('sira', 'asc');
                })
                ->orderBy('id', 'asc')
                ->get();
            if ($hp->count() > 0) {
                $hostingler = $hp->map(function ($item) {
                    // blade'in beklediği kolonlara map et
                    $item->tutar = (float)($item->fiyat ?? 0);
                    $item->zmnt = 1; // yıllık
                    // özellikler: ayrı kolonlardan virgülle üret
                    $parcalar = [];
                    if (!empty($item->disk_alani))  { $parcalar[] = $item->disk_alani . ' Disk'; }
                    if (!empty($item->trafik))      { $parcalar[] = $item->trafik . ' Trafik'; }
                    if (!empty($item->veritabani))  { $parcalar[] = $item->veritabani . ' Veritabanı'; }
                    if (!empty($item->email))       { $parcalar[] = $item->email . ' E-posta'; }
                    $item->ozellikler = implode(', ', $parcalar);
                    if (isset($item->adi)) {
                        $item->adi = TranslationHelper::translateField('hosting_paketler', $item->id, 'adi', $item->adi);
                    }
                    return $item;
                });
                $kategori = (object) [
                    'id' => 0,
                    'adi' => __('messages.web_hosting'),
                    'kisa' => '',
                    'seo' => 'web-hosting',
                    'aciklama' => null,
                ];
                return view('tema.hosting-detay', compact('ayar', 'kategori', 'hostingler'));
            }
        }
        
        // Eğer seo yoksa ilk hosting kategorisine yönlendir
        if (!$seo) {
            $ilkKategori = DB::table('hosting_kategori')
                ->where('durum', 1)
                ->when(Schema::hasColumn('hosting_kategori', 'dil'), function ($query) {
                    return $query->where('dil', 1);
                })
                ->when(Schema::hasColumn('hosting_kategori', 'sira'), function ($query) {
                    return $query->orderBy('sira', 'asc');
                })
                ->orderBy('id', 'asc')
                ->first();
            
            if ($ilkKategori) {
                $seo = $ilkKategori->seo ?? $ilkKategori->id;
                return redirect()->route('hosting.detay', $seo);
            }
            
            // Kategori yoksa direkt hosting paketlerini göster
            $hostingler = DB::table('hostingler')
                ->where('durum', 1)
                ->when(Schema::hasColumn('hostingler', 'dil'), function ($query) {
                    return $query->where(function($q) {
                        $q->where('dil', 1)->orWhereNull('dil');
                    });
                })
                ->when(Schema::hasColumn('hostingler', 'sira'), function ($query) {
                    return $query->orderBy('sira', 'asc');
                })
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($item) {
                    if (isset($item->adi)) {
                        $item->adi = TranslationHelper::translateField('hostingler', $item->id, 'adi', $item->adi);
                    }
                    return $item;
                });

            // Varsayılan kategori objesi oluştur
            $kategori = (object) [
                'id' => 0,
                'adi' => __('messages.web_hosting'),
                'kisa' => '',
                'seo' => 'web-hosting'
            ];
            
            return view('tema.hosting-detay', compact('ayar', 'kategori', 'hostingler'));
        }
        
        // Hosting kategori detayı
        $kategori = DB::table('hosting_kategori')
            ->where('seo', $seo)
            ->where('durum', 1)
            ->when(Schema::hasColumn('hosting_kategori', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->first();
        
        if (!$kategori) {
            abort(404);
        }
        
        if (isset($kategori->adi)) {
            $kategori->adi = TranslationHelper::translateField('web_kategori', $kategori->id, 'adi', $kategori->adi);
        }
        if (isset($kategori->kisa)) {
            $kategori->kisa = TranslationHelper::translateField('web_kategori', $kategori->id, 'kisa', $kategori->kisa);
        }
        if (isset($kategori->aciklama)) {
            $kategori->aciklama = TranslationHelper::translateField('web_kategori', $kategori->id, 'aciklama', $kategori->aciklama);
        }
        
        // Hosting paketlerini al
        $hostinglerQuery = DB::table('hostingler')
            ->where('durum', 1);
        
        // Kategori kontrolü
        $hasKategoriColumn = Schema::hasColumn('hostingler', 'kategori') || Schema::hasColumn('hostingler', 'kategori_id');
        
        if ($hasKategoriColumn) {
            // Kategoriye göre filtrele, ama kategori olmayanları da dahil et
            $hostinglerQuery->where(function($query) use ($kategori) {
                // Bu kategoriye ait olanlar
                if (Schema::hasColumn('hostingler', 'kategori')) {
                    $query->where('kategori', $kategori->id);
                }
                if (Schema::hasColumn('hostingler', 'kategori_id')) {
                    $query->orWhere('kategori_id', $kategori->id);
                }
                // Kategori atanmamış olanlar (null veya 0)
                if (Schema::hasColumn('hostingler', 'kategori')) {
                    $query->orWhereNull('kategori')->orWhere('kategori', 0);
                }
                if (Schema::hasColumn('hostingler', 'kategori_id')) {
                    $query->orWhereNull('kategori_id')->orWhere('kategori_id', 0);
                }
            });
        }
        
        // Dil kontrolü - dil kolonu varsa ve değer 1 değilse filtreleme yapma
        if (Schema::hasColumn('hostingler', 'dil')) {
            $hostinglerQuery->where(function($query) {
                $query->where('dil', 1)->orWhereNull('dil');
            });
        }
        
        $hostingler = $hostinglerQuery
            ->when(Schema::hasColumn('hostingler', 'sira'), function ($query) {
                return $query->orderBy('sira', 'asc');
            })
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($item) {
                if (isset($item->adi)) {
                    $item->adi = TranslationHelper::translateField('hostingler', $item->id, 'adi', $item->adi);
                }
                return $item;
            });
        
        return view('tema.hosting-detay', compact('ayar', 'kategori', 'hostingler'));
    }
    
    public function firsatlar()
    {
        $ayar = Ayar::first();
        
        // Kampanyalar/Fırsatlar
        $kampanyalar = collect([]);
        if (Schema::hasTable('kampanyalar')) {
            $kampanyalar = DB::table('kampanyalar')
                ->where('durum', 1)
                ->where(function($query) {
                    // Bitis tarihi gecmis kampanyalari gizle, diger her seyi goster
                    if (Schema::hasColumn('kampanyalar', 'bitis_tarihi')) {
                        $query->whereNull('bitis_tarihi')
                              ->orWhere('bitis_tarihi', '>=', now());
                    }
                })
                ->when(Schema::hasColumn('kampanyalar', 'sira'), function ($query) {
                    return $query->orderBy('sira', 'asc');
                })
                ->when(Schema::hasColumn('kampanyalar', 'created_at'), function ($query) {
                    return $query->orderBy('created_at', 'desc');
                })
                ->get()
                ->map(function ($kampanya) {
                    // Dil kolonlarını kontrol et ve çevir
                    $currentLocale = app()->getLocale();
                    
                    if ($currentLocale == 'en' && isset($kampanya->baslik_en) && !empty($kampanya->baslik_en)) {
                        $kampanya->baslik = $kampanya->baslik_en;
                    } elseif ($currentLocale == 'ar' && isset($kampanya->baslik_ar) && !empty($kampanya->baslik_ar)) {
                        $kampanya->baslik = $kampanya->baslik_ar;
                    } else {
                        // TR veya dil kolonu yoksa TranslationHelper kullan
                        if (isset($kampanya->baslik)) {
                            $kampanya->baslik = TranslationHelper::translateField('kampanyalar', $kampanya->id, 'baslik', $kampanya->baslik);
                        }
                    }
                    
                    if ($currentLocale == 'en' && isset($kampanya->aciklama_en) && !empty($kampanya->aciklama_en)) {
                        $kampanya->aciklama = $kampanya->aciklama_en;
                    } elseif ($currentLocale == 'ar' && isset($kampanya->aciklama_ar) && !empty($kampanya->aciklama_ar)) {
                        $kampanya->aciklama = $kampanya->aciklama_ar;
                    } else {
                        // TR veya dil kolonu yoksa TranslationHelper kullan
                        if (isset($kampanya->aciklama)) {
                            $kampanya->aciklama = TranslationHelper::translateField('kampanyalar', $kampanya->id, 'aciklama', $kampanya->aciklama);
                        }
                    }
                    
                    return $kampanya;
                });
        }
        
        // Kampanya yoksa bos birak - fake veri gosterme
        
        // İndirimli paketler (fırsat olarak göster) - sadece gerçekten indirimli olanlar
        $firsatPaketler = collect([]);
        
        $hasIndirim = Schema::hasColumn('yazilimlar', 'indirim');
        $hasFirsat = Schema::hasColumn('yazilimlar', 'firsat');
        
        if ($hasIndirim || $hasFirsat) {
            // resim_blob ve resim_mime sütunlarını hariç tut
            $firsatColumns = collect(Schema::getColumnListing('yazilimlar'))
                ->reject(fn($col) => in_array($col, ['resim_blob', 'resim_mime']))
                ->toArray();
            
            $firsatPaketlerQuery = DB::table('yazilimlar')
                ->select($firsatColumns)
                ->where('durum', 1);
            
            // İndirim veya fırsat kontrolü
            $firsatPaketlerQuery->where(function($query) use ($hasIndirim, $hasFirsat) {
                if ($hasIndirim) {
                    $query->whereNotNull('indirim')
                          ->where('indirim', '>', 0);
                }
                if ($hasFirsat) {
                    if ($hasIndirim) {
                        $query->orWhereNotNull('firsat')
                              ->where('firsat', 1);
                    } else {
                        $query->whereNotNull('firsat')
                              ->where('firsat', 1);
                    }
                }
            });
            
            $firsatPaketler = $firsatPaketlerQuery
                ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($query) {
                    return $query->where('dil', 1);
                })
                // Müşteriye özel teklif paketleri fırsatlarda da gösterilmez
                ->when(Schema::hasColumn('yazilimlar', 'musteri'), function ($query) {
                    return $query->where('musteri', 0);
                })
                ->where(function ($query) {
                    $query->where('adi', 'not like', '%teklif%')
                          ->where('seo', 'not like', '%teklif%');
                })
                ->orderBy('id', 'desc')
                ->limit(12)
                ->get()
                ->map(function ($paket) {
                    if (isset($paket->adi)) {
                        $paket->adi = TranslationHelper::translateField('yazilimlar', $paket->id, 'adi', $paket->adi);
                    }
                    if (isset($paket->aciklama)) {
                        $paket->aciklama = TranslationHelper::translateField('yazilimlar', $paket->id, 'aciklama', $paket->aciklama);
                    }
                    return $paket;
                });
        }
        
        // Indirimli paket yoksa bos birak - fake veri gosterme
        
        // Kampanyaları ve paketleri birleştir - tek bir koleksiyon olarak
        $tumFirsatlar = collect();
        
        // Kampanyaları fırsat kartı formatına çevir
        foreach($kampanyalar as $kampanya) {
            $tumFirsatlar->push((object) [
                'id' => 'kampanya_' . ($kampanya->id ?? rand()),
                'adi' => $kampanya->baslik ?? 'Kampanya',
                'aciklama' => $kampanya->aciklama ?? '',
                'fiyat' => 0,
                'tutar' => 0,
                'indirim' => (float)($kampanya->indirim ?? 0),
                'resim' => $kampanya->resim ?? null,
                'link' => $kampanya->link ?? route('paketler'),
                'seo' => $kampanya->seo ?? null,
                'tip' => 'kampanya',
            ]);
        }
        
        // Paketleri ekle
        foreach($firsatPaketler as $paket) {
            $tumFirsatlar->push((object) [
                'id' => $paket->id ?? rand(),
                'adi' => $paket->adi ?? 'Paket',
                'aciklama' => $paket->aciklama ?? '',
                'fiyat' => (float)($paket->fiyat ?? $paket->tutar ?? 0),
                'tutar' => (float)($paket->tutar ?? $paket->fiyat ?? 0),
                'indirim' => (float)($paket->indirim ?? 0),
                'resim' => $paket->resim ?? null, // resim kolonunu kontrol et
                'seo' => $paket->seo ?? null,
                'tip' => 'paket',
            ]);
        }
        
        // CRM Fırsatları (crm_opportunities) - kampanyalarla birlikte göster
        // Sadece 'acik' ve 'beklemede' durumdakiler; müşteri adı gizli, başlık + tutar gösterilir
        if (Schema::hasTable('crm_opportunities')) {
            $crmFirsatlar = DB::table('crm_opportunities')
                ->whereIn('durum', ['acik', 'beklemede'])
                ->when(Schema::hasColumn('crm_opportunities', 'created_at'), function ($query) {
                    return $query->orderBy('created_at', 'desc');
                })
                ->get();
            foreach ($crmFirsatlar as $cf) {
                $tumFirsatlar->push((object) [
                    'id'       => 'crm_' . ($cf->id ?? rand()),
                    'adi'      => $cf->baslik ?? 'Fırsat',
                    'aciklama' => $cf->aciklama ?? '',
                    'fiyat'    => (float)($cf->tutar ?? 0),
                    'tutar'    => (float)($cf->tutar ?? 0),
                    'indirim'  => 0,
                    'resim'    => null,
                    'link'     => route('paketler'),
                    'seo'      => null,
                    'tip'      => 'crm_firsat',
                ]);
            }
        }
        
        // Karıştır (shuffle) - kampanyalar ve paketler karışık görünsün
        $tumFirsatlar = $tumFirsatlar->shuffle();
        
        return view('tema.firsatlar', compact('ayar', 'tumFirsatlar'));
    }
    
    public function firsatDetay($seo)
    {
        $ayar = Ayar::first();
        
        // Kampanya kontrolü
        $kampanya = null;
        if (Schema::hasTable('kampanyalar')) {
            $kampanya = DB::table('kampanyalar')
                ->where('seo', $seo)
                ->where('durum', 1)
                ->first();
        }
        
        if (!$kampanya) {
            abort(404, 'Fırsat bulunamadı');
        }
        
        // Dil kolonlarını kontrol et ve çevir
        $currentLocale = app()->getLocale();
        
        if ($currentLocale == 'en' && isset($kampanya->baslik_en) && !empty($kampanya->baslik_en)) {
            $kampanya->baslik = $kampanya->baslik_en;
        } elseif ($currentLocale == 'ar' && isset($kampanya->baslik_ar) && !empty($kampanya->baslik_ar)) {
            $kampanya->baslik = $kampanya->baslik_ar;
        } else {
            // TR veya dil kolonu yoksa TranslationHelper kullan
            if (isset($kampanya->baslik)) {
                $kampanya->baslik = TranslationHelper::translateField('kampanyalar', $kampanya->id, 'baslik', $kampanya->baslik);
            }
        }
        
        if ($currentLocale == 'en' && isset($kampanya->aciklama_en) && !empty($kampanya->aciklama_en)) {
            $kampanya->aciklama = $kampanya->aciklama_en;
        } elseif ($currentLocale == 'ar' && isset($kampanya->aciklama_ar) && !empty($kampanya->aciklama_ar)) {
            $kampanya->aciklama = $kampanya->aciklama_ar;
        } else {
            // TR veya dil kolonu yoksa TranslationHelper kullan
            if (isset($kampanya->aciklama)) {
                $kampanya->aciklama = TranslationHelper::translateField('kampanyalar', $kampanya->id, 'aciklama', $kampanya->aciklama);
            }
        }
        
        return view('tema.firsat-detay', compact('ayar', 'kampanya'));
    }
    
    public function paketler($kategori = null)
    {
        $applyFilters = function ($query) use ($kategori) {
            if ($kategori) {
                $query->whereRaw("FIND_IN_SET(?, kategori)", [$kategori]);
            }

            if (request('kelime')) {
                $kelime = request('kelime');
                $query->where('adi', 'LIKE', "%{$kelime}%");
            }

            $siralama = request('siralama', 'yeni');
            if ($siralama === 'eski') {
                $query->orderBy('id', 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            return $query;
        };

        // resim_blob ve resim_mime sütunlarını hariç tut (bellek tasarrufu)
        $columns = collect(Schema::getColumnListing('yazilimlar'))
            ->reject(fn($col) => in_array($col, ['resim_blob', 'resim_mime']))
            ->toArray();
        
        $packagesQuery = $applyFilters(
            DB::table('yazilimlar')
                ->select($columns)
                ->where('durum', 1)
                ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($query) {
                    return $query->where('dil', 1);
                })
                // Müşteriye özel teklif paketlerini (musteri != 0) paketler sayfasından gizle
                ->when(Schema::hasColumn('yazilimlar', 'musteri'), function ($query) {
                    return $query->where('musteri', 0);
                })
                // "Teklif" içeren başlıkları da (özel teklif dökümanları) paket listesinden gizle
                ->where(function ($query) {
                    $query->where('adi', 'not like', '%teklif%')
                          ->where('seo', 'not like', '%teklif%');
                })
        );

        // Sayfa başına 15 paket - pagination
        $yazilimlar = $packagesQuery->paginate(15)->withQueryString();
        
        // Kategorileri ve hit_count'ları toplu olarak yükle (N+1 problemini çöz)
        $package_ids = $yazilimlar->pluck('id')->toArray();
        
        // Tüm kategorileri tek sorguda al
        $all_categories = collect([]);
        if (DB::getSchemaBuilder()->hasTable('web_kategori')) {
            $all_categories = DB::table('web_kategori')
                ->where('durum', 1)
                ->when(Schema::hasColumn('web_kategori', 'dil'), function ($query) {
                    return $query->where('dil', 1);
                })
                ->get()
                ->keyBy('id')
                ->map(function ($kategori) {
                    $kategori->adi = TranslationHelper::translateField('web_kategori', $kategori->id, 'adi', $kategori->adi);
                    return $kategori;
                });
        }
        
        // Tüm hit_count'ları tek sorguda al
        $hit_counts = [];
        if (!empty($package_ids) && DB::getSchemaBuilder()->hasTable('yazilim_hit')) {
            $hits = DB::table('yazilim_hit')
                ->whereIn('yid', $package_ids)
                ->selectRaw('yid, COUNT(*) as count')
                ->groupBy('yid')
                ->get()
                ->keyBy('yid');

            foreach ($hits as $hit) {
                $hit_counts[$hit->yid] = $hit->count;
            }
        }

        // Tüm satış sayılarını tek sorguda al
        $satis_counts = [];
        if (!empty($package_ids) && DB::getSchemaBuilder()->hasTable('satilanlar')) {
            try {
                $satislar = DB::table('satilanlar')
                    ->whereIn('tipi', [2]) // 2 = Yazılım/Paket
                    ->selectRaw('adi, COUNT(*) as count')
                    ->groupBy('adi')
                    ->get();

                // Paket adına göre eşleştir
                foreach ($satislar as $satis) {
                    $satis_counts[$satis->adi] = $satis->count;
                }
            } catch (\Exception $e) {
                // Sütun yapısı farklıysa sessizce geç
            }
        }
        
        // Tüm paket resimlerini webpaketresim tablosundan toplu olarak yükle
        $package_images = [];
        if (!empty($package_ids) && DB::getSchemaBuilder()->hasTable('webpaketresim')) {
            $images = DB::table('webpaketresim')
                ->whereIn('rid', $package_ids)
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('rid');
            
            foreach ($images as $rid => $resimGrubu) {
                $package_images[$rid] = $resimGrubu->first()->resim;
            }
        }
        
        // Paketleri map et ve ilişkili verileri ekle (paginator için getCollection)
        $yazilimlar->getCollection()->transform(function ($item) use ($all_categories, $hit_counts, $satis_counts, $package_images) {
            if (isset($item->adi)) {
                $item->adi = TranslationHelper::translateField('yazilimlar', $item->id, 'adi', $item->adi);
            }
            if (isset($item->kisa)) {
                $item->kisa = TranslationHelper::translateField('yazilimlar', $item->id, 'kisa', $item->kisa);
            }

            // Kategori bilgisini ekle (önceden yüklenmiş kategorilerden)
            $kategoriIds = $item->kategori ?? $item->kid ?? null;
            $kategoriler = collect([]);
            if ($kategoriIds && !$all_categories->isEmpty()) {
                // Kategori ID'si string veya integer olabilir, ayrıca virgülle ayrılmış olabilir
                if (is_string($kategoriIds)) {
                    $kategoriArray = array_filter(array_map('trim', explode(',', $kategoriIds)));
                } else {
                    $kategoriArray = [$kategoriIds];
                }
                
                foreach ($kategoriArray as $katId) {
                    // String'i integer'a çevir ve kontrol et
                    $katId = (int) trim($katId);
                    if ($katId > 0 && $all_categories->has($katId)) {
                        $kategoriler->push($all_categories->get($katId));
                    }
                }
            }
            $item->kategoriler = $kategoriler;
            
            // Görüntülenme sayısını ekle (önceden yüklenmiş hit_count'lardan)
            $item->hit_count = $hit_counts[$item->id] ?? 0;

            // Satış sayısını ekle (paket adına göre eşleştir)
            $item->satis_count = $satis_counts[$item->adi] ?? 0;
            
            // Resim bilgisini düzelt: eğer resim boşsa webpaketresim'den al
            if (empty($item->resim) && isset($package_images[$item->id])) {
                $item->resim = $package_images[$item->id];
            }
            
            return $item;
        });

        $kategoriler = collect([]);
        $kategori_sayilari = ['toplam' => 0];
        
        try {
            if (DB::getSchemaBuilder()->hasTable('web_kategori')) {
                $kategoriler = DB::table('web_kategori')
                    ->where('durum', 1)
                    ->when(Schema::hasColumn('web_kategori', 'dil'), function ($query) {
                        return $query->where('dil', 1);
                    })
                    ->when(Schema::hasColumn('web_kategori', 'sira'), function ($query) {
                        return $query->orderBy('sira', 'asc');
                    }, function ($query) {
                        return $query->orderBy('id', 'asc');
                    })
                    ->get()
                    ->map(function ($kategori) {
                        if (isset($kategori->adi)) {
                            $kategori->adi = TranslationHelper::translateField('web_kategori', $kategori->id, 'adi', $kategori->adi);
                        }
                        return $kategori;
                    });
                
                // Kategori sayılarını tek sorguda al (optimize)
                // Burada da paket listesinde kullandığımız aynı filtreleri uygulayalım:
                // - durum = 1
                // - dil = 1 (varsa)
                // - musteri = 0 (varsa)
                // - adi / seo içinde "teklif" geçenleri hariç tut
                $kategori_sayilari['toplam'] = DB::table('yazilimlar')
                    ->where('durum', 1)
                    ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($query) {
                        return $query->where('dil', 1);
                    })
                    ->when(Schema::hasColumn('yazilimlar', 'musteri'), function ($query) {
                        return $query->where('musteri', 0);
                    })
                    ->where(function ($query) {
                        $query->where('adi', 'not like', '%teklif%')
                              ->where('seo', 'not like', '%teklif%');
                    })
                    ->count();
                
                foreach ($kategoriler as $kat) {
                    $kategori_sayilari[$kat->id] = DB::table('yazilimlar')
                        ->where('durum', 1)
                        ->whereRaw("FIND_IN_SET(?, kategori)", [$kat->id])
                        ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($query) {
                            return $query->where('dil', 1);
                        })
                        ->when(Schema::hasColumn('yazilimlar', 'musteri'), function ($query) {
                            return $query->where('musteri', 0);
                        })
                        ->where(function ($query) {
                            $query->where('adi', 'not like', '%teklif%')
                                  ->where('seo', 'not like', '%teklif%');
                        })
                        ->count();
                }

                // Sadece içinde en az 1 paket kalan kategorileri göster
                $kategoriler = $kategoriler->filter(function ($kat) use ($kategori_sayilari) {
                    return ($kategori_sayilari[$kat->id] ?? 0) > 0;
                })->values();
            }
        } catch (\Exception $e) {
            // Tablo yoksa boş bırak
        }

        $arkaplan = null;
        try {
            if (DB::getSchemaBuilder()->hasTable('arka_plan')) {
                $arkaplan = DB::table('arka_plan')->first();
            }
        } catch (\Exception $e) {
            // Tablo yoksa null
        }

        // Tema alt-kategorileri: "Php Web Site Tasarım" gibi web-site kategorilerinin İÇİNDE
        // tema türlerine (Kurumsal, Otel, İnşaat...) göre alt-filtre çipleri.
        $temaKategorileri = [];
        $_aktifKatAdi = '';
        if ($kategori) {
            $_k = collect($kategoriler)->firstWhere('id', $kategori);
            $_aktifKatAdi = $_k->adi ?? '';
        }
        if ($kategori && stripos($_aktifKatAdi, 'web site') !== false) {
            $_temaAdaylar = [
                'Kurumsal'   => 'Kurumsal',        'Otel'     => 'Otel & Tur',
                'Restaurant' => 'Restoran & Kafe', 'İnşaat'   => 'İnşaat',
                'Belediye'   => 'Dernek & Belediye','E-Ticaret'=> 'E-Ticaret',
                'Rent'       => 'Rent A Car',      'Eğitim'    => 'Eğitim',
                'Kuaför'     => 'Güzellik & Kuaför','Spor'     => 'Spor Salonu',
                'Anaokulu'   => 'Anaokulu',        'Mobilya'   => 'Mobilya',
            ];
            foreach ($_temaAdaylar as $kw => $label) {
                $cnt = DB::table('yazilimlar')->where('durum', 1)
                    ->when(Schema::hasColumn('yazilimlar', 'musteri'), fn ($q) => $q->where('musteri', 0))
                    ->whereRaw('FIND_IN_SET(?, kategori)', [$kategori])
                    ->where('adi', 'like', '%' . $kw . '%')->count();
                if ($cnt > 0) {
                    $temaKategorileri[] = ['kelime' => $kw, 'label' => $label, 'adet' => $cnt];
                }
            }
        }

        return view('tema.paketler', compact('yazilimlar', 'kategoriler', 'kategori', 'kategori_sayilari', 'arkaplan', 'temaKategorileri'));
    }
    
    /**
     * Paketler sayfası için sonsuz kaydırma (infinite scroll) endpoint'i
     * AJAX ile: aynı filtrelerle offset/limit alıp paketleri HTML olarak döndürür.
     */
    public function paketlerLoad(Request $request)
    {
        $kategori = $request->input('kategori');
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = (int) $request->input('limit', 9);
        $limit = max(3, min(12, $limit));
        
        $applyFilters = function ($query) use ($kategori) {
            if ($kategori) {
                $query->whereRaw("FIND_IN_SET(?, kategori)", [$kategori]);
            }
            
            if ($request->filled('kelime')) {
                $kelime = $request->input('kelime');
                $query->where('adi', 'LIKE', "%{$kelime}%");
            }
            
            $siralama = $request->input('siralama', 'yeni');
            if ($siralama === 'eski') {
                $query->orderBy('id', 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }
            
            return $query;
        };
        
        // resim_blob ve resim_mime sütunlarını hariç tut
        $columns = collect(Schema::getColumnListing('yazilimlar'))
            ->reject(fn($col) => in_array($col, ['resim_blob', 'resim_mime']))
            ->toArray();
        
        $baseQuery = DB::table('yazilimlar')
            ->select($columns)
            ->where('durum', 1)
            ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->when(Schema::hasColumn('yazilimlar', 'musteri'), function ($query) {
                return $query->where('musteri', 0);
            })
            ->where(function ($query) {
                $query->where('adi', 'not like', '%teklif%')
                      ->where('seo', 'not like', '%teklif%');
            });
        
        $packagesQuery = $applyFilters($baseQuery);
        
        // Toplam adet (duruma göre hasMore döndürmek için)
        try {
            $totalQuery = clone $packagesQuery;
            $totalCount = (int) $totalQuery->count();
        } catch (\Exception $e) {
            $totalCount = 0;
        }
        
        $yazilimlar = $packagesQuery
            ->skip($offset)
            ->take($limit)
            ->get();
        
        $package_ids = $yazilimlar->pluck('id')->toArray();
        
        // Kategorileri ve hit_count'lardan gelen ek alanları hazırla
        $all_categories = collect([]);
        if (DB::getSchemaBuilder()->hasTable('web_kategori') && !empty($package_ids)) {
            // dönecek paketlerin kategori id'lerini topla
            $kategoriIds = [];
            foreach ($yazilimlar as $item) {
                $kategoriVal = $item->kategori ?? $item->kid ?? null;
                if (!$kategoriVal) continue;
                if (is_string($kategoriVal)) {
                    $parts = array_filter(array_map('trim', explode(',', $kategoriVal)));
                    foreach ($parts as $p) $kategoriIds[] = (int) $p;
                } else {
                    $kategoriIds[] = (int) $kategoriVal;
                }
            }
            $kategoriIds = array_values(array_unique(array_filter($kategoriIds)));
            
            if (!empty($kategoriIds)) {
                $all_categories = DB::table('web_kategori')
                    ->where('durum', 1)
                    ->whereIn('id', $kategoriIds)
                    ->when(Schema::hasColumn('web_kategori', 'dil'), function ($query) {
                        return $query->where('dil', 1);
                    })
                    ->when(Schema::hasColumn('web_kategori', 'sira'), function ($query) {
                        return $query->orderBy('sira', 'asc');
                    }, function ($query) {
                        return $query->orderBy('id', 'asc');
                    })
                    ->get()
                    ->keyBy('id')
                    ->map(function ($kategori) {
                        if (isset($kategori->adi)) {
                            $kategori->adi = TranslationHelper::translateField('web_kategori', $kategori->id, 'adi', $kategori->adi);
                        }
                        return $kategori;
                    });
            }
        }
        
        $hit_counts = [];
        if (!empty($package_ids) && DB::getSchemaBuilder()->hasTable('yazilim_hit')) {
            $hits = DB::table('yazilim_hit')
                ->whereIn('yid', $package_ids)
                ->selectRaw('yid, COUNT(*) as count')
                ->groupBy('yid')
                ->get()
                ->keyBy('yid');
            
            foreach ($hits as $hit) {
                $hit_counts[$hit->yid] = $hit->count;
            }
        }
        
        // Satış sayılarını al
        $satis_counts = [];
        if (!empty($package_ids) && DB::getSchemaBuilder()->hasTable('satilanlar')) {
            try {
                $satislar = DB::table('satilanlar')
                    ->whereIn('tipi', [2])
                    ->selectRaw('adi, COUNT(*) as count')
                    ->groupBy('adi')
                    ->get();
                foreach ($satislar as $satis) {
                    $satis_counts[$satis->adi] = $satis->count;
                }
            } catch (\Exception $e) {}
        }

        // Paket resimlerini webpaketresim tablosundan hazırla (opsiyonel, partial yine fallback yapabilir)
        $package_images = [];
        if (!empty($package_ids) && DB::getSchemaBuilder()->hasTable('webpaketresim')) {
            $images = DB::table('webpaketresim')
                ->whereIn('rid', $package_ids)
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('rid');
            
            foreach ($images as $rid => $resimGrubu) {
                $package_images[$rid] = $resimGrubu->first()->resim;
            }
        }
        
        $yazilimlar = $yazilimlar->map(function ($item) use ($all_categories, $hit_counts, $satis_counts, $package_images) {
            if (isset($item->adi)) {
                $item->adi = TranslationHelper::translateField('yazilimlar', $item->id, 'adi', $item->adi);
            }
            if (isset($item->kisa)) {
                $item->kisa = TranslationHelper::translateField('yazilimlar', $item->id, 'kisa', $item->kisa);
            }

            $kategoriIds = $item->kategori ?? $item->kid ?? null;
            $kategoriler = collect([]);
            if ($kategoriIds && !$all_categories->isEmpty()) {
                if (is_string($kategoriIds)) {
                    $kategoriArray = array_filter(array_map('trim', explode(',', $kategoriIds)));
                } else {
                    $kategoriArray = [$kategoriIds];
                }

                foreach ($kategoriArray as $katId) {
                    $katId = (int) trim((string) $katId);
                    if ($katId > 0 && $all_categories->has($katId)) {
                        $kategoriler->push($all_categories->get($katId));
                    }
                }
            }

            $item->kategoriler = $kategoriler;
            $item->hit_count = $hit_counts[$item->id] ?? 0;
            $item->satis_count = $satis_counts[$item->adi] ?? 0;

            if (empty($item->resim) && isset($package_images[$item->id])) {
                $item->resim = $package_images[$item->id];
            }

            return $item;
        });
        
        $html = view('tema.partials.paketler-infinite-items', [
            'yazilimlar' => $yazilimlar,
        ])->render();
        
        $nextOffset = $offset + $yazilimlar->count();
        $hasMore = $totalCount > 0 ? $nextOffset < $totalCount : $yazilimlar->count() === $limit;
        
        return response()->json([
            'html' => $html,
            'nextOffset' => $nextOffset,
            'hasMore' => $hasMore,
        ]);
    }
    
    public function paketDetay($seo)
    {
        // resim_blob ve resim_mime sütunlarını hariç tut
        $paketColumns = collect(Schema::getColumnListing('yazilimlar'))
            ->reject(fn($col) => in_array($col, ['resim_blob', 'resim_mime']))
            ->toArray();
        
        $paket = DB::table('yazilimlar')
            ->select($paketColumns)
            ->where('seo', $seo)
            ->where('durum', 1)
            ->when(Schema::hasColumn('yazilimlar', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            // Müşteriye özel teklif paketleri (musteri != 0) genel /detay/{seo} linkinden AÇILMAZ.
            // Müşteri kendi teklifini yalnızca hesabım (uye.crm.teklif.detay) veya token'lı
            // teklif linkinden görür; public slug'dan erişim 404 döner.
            ->when(Schema::hasColumn('yazilimlar', 'musteri'), function ($query) {
                return $query->where('musteri', 0);
            })
            ->first();

        if (!$paket) {
            abort(404);
        }

        if (isset($paket->adi)) {
            $paket->adi = TranslationHelper::translateField('yazilimlar', $paket->id, 'adi', $paket->adi);
        }
        if (isset($paket->kisa)) {
            $paket->kisa = TranslationHelper::translateField('yazilimlar', $paket->id, 'kisa', $paket->kisa);
        }
        if (isset($paket->aciklama)) {
            $paket->aciklama = TranslationHelper::translateField('yazilimlar', $paket->id, 'aciklama', $paket->aciklama);
        }
        if (isset($paket->ozellik)) {
            $paket->ozellik = TranslationHelper::translateField('yazilimlar', $paket->id, 'ozellik', $paket->ozellik);
        }

        // Paket resimleri
        $resimler = DB::table('webpaketresim')
            ->where('rid', $paket->id)
            ->orderBy('id', 'asc')
            ->get();
        
        // İzlenme sayısı
        $izlenme = DB::table('yazilim_hit')
            ->where('yid', $paket->id)
            ->count();
        
        // Kategoriler - Sadece bu pakete ait olanlar
        $kategoriler = collect([]);
        try {
            if (DB::getSchemaBuilder()->hasTable('web_kategori') && isset($paket->kategori) && !empty($paket->kategori)) {
                // Paketteki kategori ID'lerini al (virgülle ayrılmış)
                $kategoriIdleri = array_filter(array_map('trim', explode(',', $paket->kategori)));
                
                if (!empty($kategoriIdleri)) {
                    $kategoriler = DB::table('web_kategori')
                        ->whereIn('id', $kategoriIdleri)
                        ->where('durum', 1)
                        ->when(Schema::hasColumn('web_kategori', 'dil'), function ($query) {
                            return $query->where('dil', 1);
                        })
                        ->when(Schema::hasColumn('web_kategori', 'sira'), function ($query) {
                            return $query->orderBy('sira', 'asc');
                        }, function ($query) {
                            return $query->orderBy('id', 'asc');
                        })
                        ->get()
                        ->map(function ($kategori) {
                            if (isset($kategori->adi)) {
                                $kategori->adi = TranslationHelper::translateField('web_kategori', $kategori->id, 'adi', $kategori->adi);
                            }
                            return $kategori;
                        });
                }
            }
        } catch (\Exception $e) {
            // Tablo yoksa boş bırak
        }
        
        // Arka plan
        $arkaplan = null;
        try {
            if (DB::getSchemaBuilder()->hasTable('arka_plan')) {
                $arkaplan = DB::table('arka_plan')->first();
            }
        } catch (\Exception $e) {
            // Tablo yoksa null
        }
        
        // Bu paketin kategori id'leri — hem benzer paketler hem ek hizmet kapsamı kullanır
        $katIdleri = array_filter(array_map('trim', explode(',', (string) ($paket->kategori ?? ''))));

        // Benzer paketler ("Bunu alanlar bunları da aldı") — aynı kategoriden, kendisi hariç (madde 13)
        $benzerPaketler = collect([]);
        try {
            if (!empty($katIdleri)) {
                $benzerPaketler = DB::table('yazilimlar')
                    ->select($paketColumns)
                    ->where('id', '!=', $paket->id)
                    ->where('durum', 1)
                    ->when(Schema::hasColumn('yazilimlar', 'dil'), fn ($q) => $q->where('dil', 1))
                    // Müşteriye özel teklif paketleri (musteri != 0) benzer/önerilen listesinde gösterilmez —
                    // yalnızca gönderildiği müşteriye özel linkinden erişilebilir.
                    ->when(Schema::hasColumn('yazilimlar', 'musteri'), fn ($q) => $q->where('musteri', 0))
                    ->where(function ($w) {
                        $w->where('adi', 'not like', '%teklif%')
                          ->where('seo', 'not like', '%teklif%');
                    })
                    ->where(function ($w) use ($katIdleri) {
                        foreach ($katIdleri as $kid) {
                            $w->orWhereRaw('FIND_IN_SET(?, kategori)', [$kid]);
                        }
                    })
                    ->inRandomOrder()
                    ->limit(4)
                    ->get()
                    ->map(function ($p) {
                        if (isset($p->adi)) {
                            $p->adi = TranslationHelper::translateField('yazilimlar', $p->id, 'adi', $p->adi);
                        }
                        return $p;
                    });
            }
        } catch (\Throwable $e) {
            // sorun olursa öneri gösterme
        }

        // Ek hizmetler ("Yanında Satın Alınabilecekler" — madde 15)
        // Sadece ek hizmetin kapsamındaki kategorilerde görünür (ör. Web Master -> Php Web Site Tasarım).
        $ekHizmetler = collect([]);
        try {
            if (Schema::hasTable('ek_hizmetler')) {
                $q = DB::table('ek_hizmetler')->where('durum', 1);

                if (Schema::hasColumn('ek_hizmetler', 'kategoriler')) {
                    // $katIdleri: bu paketin kategori id'leri (benzer paketler için yukarıda çıkarıldı)
                    $q->where(function ($w) use ($katIdleri) {
                        // Kapsam boş bırakılmışsa: tüm kategorilerde göster
                        $w->whereNull('kategoriler')->orWhere('kategoriler', '');
                        foreach ($katIdleri as $kid) {
                            $w->orWhereRaw('FIND_IN_SET(?, kategoriler)', [$kid]);
                        }
                    });
                }

                $ekHizmetler = $q->orderBy('sira')->orderBy('id')->get();
            }
        } catch (\Throwable $e) {
        }

        // ── Değerlendirmeler (puan + yorum) ─────────────────────────────────
        $yorumlar = collect([]);
        $puanOzet = ['ortalama' => null, 'adet' => 0, 'dagilim' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
        $yorumYaptiMi = false;

        try {
            if (Schema::hasTable('yorumlar') && Schema::hasColumn('yorumlar', 'puan')) {
                $yorumlar = DB::table('yorumlar')
                    ->where('tip', 'paket')
                    ->where('icerik_id', $paket->id)
                    ->where('durum', 1)                 // sadece admin onaylılar
                    ->orderByDesc('id')
                    ->get(['id', 'adi', 'yorum', 'puan', 'tarih']);

                if ($yorumlar->isNotEmpty()) {
                    $puanli = $yorumlar->whereNotNull('puan');
                    $puanOzet['adet'] = $puanli->count();
                    $puanOzet['ortalama'] = $puanOzet['adet'] ? round($puanli->avg('puan'), 1) : null;
                    foreach ($puanli as $y) {
                        $p = (int) $y->puan;
                        if (isset($puanOzet['dagilim'][$p])) {
                            $puanOzet['dagilim'][$p]++;
                        }
                    }
                }

                // Üye bu paketi daha önce değerlendirdi mi? (formu tekrar göstermemek için)
                if ($uyeId = \Illuminate\Support\Facades\Auth::guard('uye')->id()) {
                    $yorumYaptiMi = DB::table('yorumlar')
                        ->where('tip', 'paket')->where('icerik_id', $paket->id)
                        ->where('uyeid', $uyeId)->exists();
                }
            }
        } catch (\Throwable $e) {
            // yorum bolumu gosterilmez, sayfa calismaya devam eder
        }

        return view('tema.paket-detay', compact('paket', 'resimler', 'izlenme', 'kategoriler', 'arkaplan', 'benzerPaketler', 'ekHizmetler', 'yorumlar', 'puanOzet', 'yorumYaptiMi'));
    }

    public function webPaketlerim()
    {
        $ayarlar = Ayar::first();
        $paketler = Satilan::where('uyeid', Auth::guard('uye')->id())
            ->where('tipi', 2) // 2: Web paketleri
            ->orderByDesc('tarih')
            ->paginate(20);

        return view('tema.web-paketlerim', compact('ayarlar', 'paketler'));
    }

    /**
     * BİRLEŞİK HİZMETLERİM — satın alınan her şey tek listede:
     * web paketleri (tipi 2) + hosting (tipi 1) + alan adları (tipi 3).
     * Panel ana sayfasındaki "Hizmetlerim" kartı buraya bağlanır; kartın
     * sayacı ile bu sayfadaki kayıt sayısı birebir aynı kaynaktan gelir.
     *
     * NOT: Mevcut /hizmetlerim sayfasına DOKUNULMADI — orası CRM'den atanan
     * hizmet sözleşmelerini (musteri_hizmetler) gösteriyor, farklı bir şey.
     */
    public function aldigimHizmetler()
    {
        $ayarlar = Ayar::first();
        $uyeId   = Auth::guard('uye')->id();

        $kayitlar = Satilan::where('uyeid', $uyeId)
            ->whereIn('tipi', [1, 2, 3])
            ->orderByDesc('tarih')
            ->paginate(20);

        // Tip bazında özet (sayfa başındaki rozetler için)
        $ozet = ['hosting' => 0, 'paket' => 0, 'domain' => 0];
        try {
            $sayim = Satilan::where('uyeid', $uyeId)
                ->whereIn('tipi', [1, 2, 3])
                ->selectRaw('tipi, COUNT(*) as adet')
                ->groupBy('tipi')->pluck('adet', 'tipi');

            $ozet['hosting'] = (int) ($sayim[1] ?? 0);
            $ozet['paket']   = (int) ($sayim[2] ?? 0);
            $ozet['domain']  = (int) ($sayim[3] ?? 0);
        } catch (\Throwable $e) {
            \Log::warning('aldigimHizmetler ozet hatasi', ['error' => $e->getMessage()]);
        }

        return view('tema.aldigim-hizmetler', compact('ayarlar', 'kayitlar', 'ozet'));
    }
    
    public function domainTescil()
    {
        $ayar = Ayar::first();
        $alanadi = DB::table('alanadi')->where('id', 1)->first();
        return view('tema.domain-tescil', compact('ayar', 'alanadi'));
    }

    /**
     * Marka Tescil tanıtım sayfası.
     *
     * NOT (11.08.2026): Bu metod EKSİKTİ. Route (web.php, 'marka.tescil')
     * ve görünüm (tema/marka-tescil.blade.php, 213 satır + SEO açıklaması)
     * mevcuttu ama controller karşılığı yoktu -> /marka-tescil adresi
     * ziyaretçilere 500 hatası veriyordu. Görünüm kendi kendine yeterli
     * (tüm içeriği @foreach dizileriyle inline tanımlı), bu yüzden sadece
     * layout'un beklediği $ayar yeterli.
     */
    public function markaTescil()
    {
        $ayar = Ayar::first();
        return view('tema.marka-tescil', compact('ayar'));
    }
    
    public function alanadiSatinal($seo = null)
    {
        $ayar = Ayar::first();
        return view('tema.alanadi-satinal', compact('ayar', 'seo'));
    }
    
    public function hostingSatinal($id)
    {
        // Kullanıcı girişi kontrolü
        if (!Auth::guard('uye')->check()) {
            return redirect()->route('giris')->with('error', 'Sepete ürün eklemek için giriş yapmalısınız.');
        }
        
        $userId = Auth::guard('uye')->id();

        // Listeleme 'hosting_paketler' tablosunu öncelikli kullanıyor; satın alma da aynı kaynağa bakmalı.
        // Önce hosting_paketler'de ara, bulunamazsa eski 'hostingler' tablosuna düş.
        $hosting = null;
        if (Schema::hasTable('hosting_paketler')) {
            $hosting = DB::table('hosting_paketler')->where('id', $id)->where('durum', 1)->first();
            if ($hosting && !isset($hosting->tutar)) {
                // hosting_paketler 'fiyat' kolonu kullanıyor; tutar'a normalize et
                $hosting->tutar = $hosting->fiyat ?? 0;
            }
        }
        if (!$hosting) {
            $hosting = DB::table('hostingler')->where('id', $id)->where('durum', 1)->first();
        }
        
        if (!$hosting) {
            abort(404, 'Hosting paketi bulunamadı');
        }
        
        try {
            // Hosting fiyatını al
            $fiyat = $hosting->tutar ?? $hosting->fiyat ?? 0;
            $hosting_adi = $hosting->adi ?? 'Hosting Paketi';
            
            // Sepette aynı ürün var mı kontrol et
            $mevcut_sepet = null;
            if (Schema::hasColumn('sepet', 'user_id')) {
                $mevcut_sepet = DB::table('sepet')
                    ->where('user_id', $userId)
                    ->where('urun_id', $id)
                    ->where(function($query) {
                        if (Schema::hasColumn('sepet', 'urun_tipi')) {
                            $query->where('urun_tipi', 'hosting');
                        } elseif (Schema::hasColumn('sepet', 'tipi')) {
                            $query->where('tipi', 1); // 1: hosting
                        }
                    })
                    ->first();
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                $mevcut_sepet = DB::table('sepet')
                    ->where('uyeid', $userId)
                    ->where('urun_id', $id)
                    ->where(function($query) {
                        if (Schema::hasColumn('sepet', 'urun_tipi')) {
                            $query->where('urun_tipi', 'hosting');
                        } elseif (Schema::hasColumn('sepet', 'tipi')) {
                            $query->where('tipi', 1);
                        }
                    })
                    ->first();
            }
            
            if ($mevcut_sepet) {
                return redirect()->to(localized_route('sepet'))->with('info', 'Bu hosting paketi zaten sepetinizde.');
            }
            
            // Sepete ekleme verilerini hazırla
            $insertData = [];
            
            // User ID
            if (Schema::hasColumn('sepet', 'user_id')) {
                $insertData['user_id'] = $userId;
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                $insertData['uyeid'] = $userId;
            }
            
            // urun_id
            if (Schema::hasColumn('sepet', 'urun_id')) {
                $insertData['urun_id'] = $id;
            }
            
            // who
            if (Schema::hasColumn('sepet', 'who')) {
                $insertData['who'] = $userId;
            }
            
            // Hosting adı
            if (Schema::hasColumn('sepet', 'name')) {
                $insertData['name'] = $hosting_adi;
            }
            if (Schema::hasColumn('sepet', 'urun_adi')) {
                $insertData['urun_adi'] = $hosting_adi;
            }
            if (Schema::hasColumn('sepet', 'adi')) {
                $insertData['adi'] = $hosting_adi;
            }
            
            // Ürün tipi
            if (Schema::hasColumn('sepet', 'urun_tipi')) {
                $insertData['urun_tipi'] = 'hosting';
            } elseif (Schema::hasColumn('sepet', 'tipi')) {
                $insertData['tipi'] = 1; // 1: hosting
            }
            
            // Fiyat
            if (Schema::hasColumn('sepet', 'fiyat')) {
                $insertData['fiyat'] = $fiyat;
            }
            if (Schema::hasColumn('sepet', 'tutar')) {
                $insertData['tutar'] = $fiyat;
            }
            if (Schema::hasColumn('sepet', 'price')) {
                $insertData['price'] = $fiyat;
            }
            
            // Miktar
            if (Schema::hasColumn('sepet', 'miktar')) {
                $insertData['miktar'] = 1;
            } elseif (Schema::hasColumn('sepet', 'sure')) {
                $insertData['sure'] = 1;
            }
            
            // Açıklama
            if (Schema::hasColumn('sepet', 'aciklama')) {
                $insertData['aciklama'] = 'Hosting Paketi';
            }
            
            // Tarih
            if (Schema::hasColumn('sepet', 'created_at')) {
                $insertData['created_at'] = now();
            }
            if (Schema::hasColumn('sepet', 'updated_at')) {
                $insertData['updated_at'] = now();
            }
            if (Schema::hasColumn('sepet', 'tarih')) {
                $insertData['tarih'] = date('Y-m-d H:i:s');
            }
            
            // Sepete ekle
            if (count($insertData) > 0) {
                DB::table('sepet')->insert($insertData);
                Log::info('Hosting paketi sepete eklendi', [
                    'user_id' => $userId,
                    'hosting_id' => $id,
                    'hosting_adi' => $hosting_adi,
                    'fiyat' => $fiyat
                ]);
                
                return redirect()->to(localized_route('sepet'))->with('success', 'Hosting paketi sepete eklendi!');
            } else {
                throw new \Exception('Sepet tablosunda uygun kolon bulunamadı');
            }
            
        } catch (\Exception $e) {
            Log::error('Hosting paketi sepete ekleme hatası', [
                'error' => $e->getMessage(),
                'hosting_id' => $id,
                'user_id' => $userId
            ]);
            
            return redirect()->back()->with('error', 'Sepete eklenirken hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function webPaketSatinal(Request $request, $id)
    {
        // Kullanıcı girişi kontrolü
        if (!Auth::guard('uye')->check()) {
            return redirect()->route('giris')->with('error', 'Sepete ürün eklemek için giriş yapmalısınız.');
        }
        
        $userId = Auth::guard('uye')->id();
        
        // resim_blob ve resim_mime sütunlarını hariç tut
        $satinalColumns = collect(Schema::getColumnListing('yazilimlar'))
            ->reject(fn($col) => in_array($col, ['resim_blob', 'resim_mime']))
            ->toArray();
        
        $paket = DB::table('yazilimlar')->select($satinalColumns)->where('id', $id)->where('durum', 1)->first();
        
        if (!$paket) {
            abort(404, 'Web paketi bulunamadı');
        }
        
        // Paket verilerini logla - TÜM kolonları göster
        $paket_array = (array) $paket;
        Log::info('Web paketi ham veri - TÜM KOLONLAR', [
            'paket_id' => $id,
            'all_columns' => $paket_array,
            'column_names' => array_keys($paket_array)
        ]);
        
        // Debug için dd() kullan (sadece debug modunda)
        if (config('app.debug')) {
            \Log::info('PAKET DEBUG - Tüm veriler:', $paket_array);
        }
        
        try {
            // Önce tüm kolonları kontrol et ve logla
            $paket_array = (array) $paket;
            $fiyat_kolonlari = ['tutar', 'fiyat', 'price', 'ucret', 'cost', 'amount', 'tutar_fiyat', 'fiyat_tutar'];
            $ad_kolonlari = ['adi', 'name', 'baslik', 'title', 'isim'];
            
            // Paket adını al - tüm olası kolonları kontrol et
            $paket_adi = 'Web Paketi';
            foreach ($ad_kolonlari as $kolon) {
                if (isset($paket->$kolon) && !empty($paket->$kolon) && $paket->$kolon !== 'Ürün') {
                    $paket_adi = $paket->$kolon;
                    Log::info('Paket adı bulundu', ['kolon' => $kolon, 'deger' => $paket_adi]);
                    break;
                }
            }
            
            // Paket fiyatını al - TUTAR kolonunu öncelikle kontrol et (paket-detay.blade.php'de tutar kullanılıyor)
            $temel_fiyat = 0;
            $fiyat_kolon = null;
            
            // Önce tutar kolonunu kontrol et (en yaygın)
            if (isset($paket->tutar) && $paket->tutar > 0) {
                $temel_fiyat = (float) $paket->tutar;
                $fiyat_kolon = 'tutar';
                Log::info('Fiyat bulundu (tutar)', ['deger' => $temel_fiyat]);
            } else {
                // Diğer fiyat kolonlarını kontrol et
                foreach ($fiyat_kolonlari as $kolon) {
                    if ($kolon === 'tutar') continue; // Zaten kontrol ettik
                    if (isset($paket->$kolon)) {
                        $deger = (float) $paket->$kolon;
                        if ($deger > 0) {
                            $temel_fiyat = $deger;
                            $fiyat_kolon = $kolon;
                            Log::info('Fiyat bulundu', ['kolon' => $kolon, 'deger' => $temel_fiyat]);
                            break;
                        }
                    }
                }
            }
            
            // Ödeme tipi: aylik paketlerde süre = AY (1-3-6-9-12), tek seferliklerde süre = 1
            $odemeTipi = $paket->odeme_tipi ?? 'tek';

            // Süre seçimi ve fiyat hesaplama
            $sure = (int) $request->input('sure', 1);
            if ($odemeTipi === 'aylik') {
                // Sadece izinli ay değerleri; geçersizse en yakını 1'e düşür
                $izinliAylar = [1, 3, 6, 9, 12];
                if (!in_array($sure, $izinliAylar, true)) {
                    $sure = 1;
                }
            } else {
                $sure = 1; // tek seferlik: süre yok
            }

            // İndirim oranları (aylık taahhüt — uzun döneme indirim)
            $indirim_oranlari = [
                1  => 0,   // 1 ay:  %0
                3  => 10,  // 3 ay:  %10
                6  => 15,  // 6 ay:  %15
                9  => 18,  // 9 ay:  %18
                12 => 20,  // 12 ay: %20
            ];

            $indirim = $indirim_oranlari[$sure] ?? 0;
            $toplam_fiyat = $temel_fiyat * $sure;
            $fiyat = $toplam_fiyat * (1 - $indirim / 100); // İndirimli toplam fiyat

            // Aylık paket + 1 aydan fazla ise sepette ismine süreyi yaz
            if ($odemeTipi === 'aylik' && $sure > 1) {
                $paket_adi .= " ({$sure} Ay)";
            }

            // Eğer fiyat bulunamadıysa, tüm kolonları logla
            if ($temel_fiyat <= 0) {
                Log::error('Fiyat bulunamadı - Tüm kolonlar:', [
                    'paket_id' => $id,
                    'tum_kolonlar' => $paket_array,
                    'kontrol_edilen_kolonlar' => $fiyat_kolonlari,
                    'tutar_degeri' => $paket->tutar ?? 'N/A',
                    'tutar_type' => isset($paket->tutar) ? gettype($paket->tutar) : 'N/A'
                ]);
                $fiyat = 0;
            }
            
            Log::info('Web paketi sepete ekleme başlatıldı', [
                'paket_id' => $id,
                'paket_adi' => $paket_adi,
                'temel_fiyat' => $temel_fiyat,
                'sure' => $sure,
                'indirim' => $indirim,
                'toplam_fiyat' => $toplam_fiyat,
                'hesaplanan_fiyat' => $fiyat,
                'fiyat_kolon' => $fiyat_kolon,
                'tum_kolonlar' => $paket_array
            ]);
            
            // Sepette aynı ürün var mı kontrol et
            $mevcut_sepet = null;
            if (Schema::hasColumn('sepet', 'user_id')) {
                $mevcut_sepet = DB::table('sepet')
                    ->where('user_id', $userId)
                    ->where('urun_id', $id)
                    ->where(function($query) {
                        if (Schema::hasColumn('sepet', 'urun_tipi')) {
                            $query->where('urun_tipi', 'web-paket');
                        } elseif (Schema::hasColumn('sepet', 'tipi')) {
                            $query->where('tipi', 2); // 2: web paketi
                        }
                    })
                    ->first();
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                $mevcut_sepet = DB::table('sepet')
                    ->where('uyeid', $userId)
                    ->where('urun_id', $id)
                    ->where(function($query) {
                        if (Schema::hasColumn('sepet', 'urun_tipi')) {
                            $query->where('urun_tipi', 'web-paket');
                        } elseif (Schema::hasColumn('sepet', 'tipi')) {
                            $query->where('tipi', 2);
                        }
                    })
                    ->first();
            }
            
            if ($mevcut_sepet) {
                return redirect()->to(localized_route('sepet'))->with('info', 'Bu web paketi zaten sepetinizde.');
            }
            
            // Sepete ekleme verilerini hazırla
            $insertData = [];
            
            // User ID
            if (Schema::hasColumn('sepet', 'user_id')) {
                $insertData['user_id'] = $userId;
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                $insertData['uyeid'] = $userId;
            }
            
            // urun_id
            if (Schema::hasColumn('sepet', 'urun_id')) {
                $insertData['urun_id'] = $id;
            }
            
            // who
            if (Schema::hasColumn('sepet', 'who')) {
                $insertData['who'] = $userId;
            }
            
            // Paket adı - tüm olası kolonlara kaydet
            if (Schema::hasColumn('sepet', 'name')) {
                $insertData['name'] = $paket_adi;
            }
            if (Schema::hasColumn('sepet', 'urun_adi')) {
                $insertData['urun_adi'] = $paket_adi;
            }
            if (Schema::hasColumn('sepet', 'adi')) {
                $insertData['adi'] = $paket_adi;
            }
            
            // Ürün tipi
            if (Schema::hasColumn('sepet', 'urun_tipi')) {
                $insertData['urun_tipi'] = 'web-paket';
            } elseif (Schema::hasColumn('sepet', 'tipi')) {
                $insertData['tipi'] = 2; // 2: web paketi
            }
            
            // Fiyat
            if (Schema::hasColumn('sepet', 'fiyat')) {
                $insertData['fiyat'] = $fiyat;
            }
            if (Schema::hasColumn('sepet', 'tutar')) {
                $insertData['tutar'] = $fiyat;
            }
            if (Schema::hasColumn('sepet', 'price')) {
                $insertData['price'] = $fiyat;
            }
            
            // Miktar/Süre
            // ÖNEMLİ: $fiyat zaten (indirimli) TOPLAM tutar. Sepet satırı fiyat×miktar
            // hesapladığı için miktar=1 olmalı; aksi halde çift çarpma olur.
            // Ay bilgisi paket adına "(N Ay)" olarak eklendi.
            if (Schema::hasColumn('sepet', 'miktar')) {
                $insertData['miktar'] = 1;
            }
            if (Schema::hasColumn('sepet', 'sure')) {
                $insertData['sure'] = $sure; // Seçilen süre (ay) — bilgi amaçlı
            }
            
            // Açıklama - süre bilgisiyle birlikte
            if (Schema::hasColumn('sepet', 'aciklama')) {
                if ($sure > 1) {
                    $indirim_text = $indirim > 0 ? ' (%' . $indirim . ' indirimli)' : '';
                    $insertData['aciklama'] = 'Web Paketi - ' . $sure . ' Yıl' . $indirim_text;
                } else {
                    $insertData['aciklama'] = 'Web Paketi - 1 Yıl';
                }
            }
            
            // Tarih
            if (Schema::hasColumn('sepet', 'created_at')) {
                $insertData['created_at'] = now();
            }
            if (Schema::hasColumn('sepet', 'updated_at')) {
                $insertData['updated_at'] = now();
            }
            if (Schema::hasColumn('sepet', 'tarih')) {
                $insertData['tarih'] = date('Y-m-d H:i:s');
            }
            
            // Sepete ekle
            if (count($insertData) > 0) {
                DB::table('sepet')->insert($insertData);
                
                Log::info('Web paketi sepete eklendi', [
                    'user_id' => $userId,
                    'paket_id' => $id,
                    'paket_adi' => $paket_adi,
                    'fiyat' => $fiyat,
                    'fiyat_kolon' => $fiyat_kolon,
                    'insert_data' => $insertData,
                    'insert_data_count' => count($insertData)
                ]);
                
                // "Sepete Ekle" (mode=sepet) ise sepeti açma, aynı sayfada kal + bildirim ver.
                // "Satın Al" (varsayılan) ise sepete yönlendir.
                if ($request->input('mode') === 'sepet') {
                    return redirect()->back()->with('success', 'Web paketi sepete eklendi!');
                }
                return redirect()->to(localized_route('sepet'))->with('success', 'Web paketi sepete eklendi!');
            } else {
                Log::error('Sepet tablosunda uygun kolon bulunamadı', [
                    'paket_id' => $id,
                    'user_id' => $userId
                ]);
                throw new \Exception('Sepet tablosunda uygun kolon bulunamadı');
            }
            
        } catch (\Exception $e) {
            Log::error('Web paketi sepete ekleme hatası', [
                'error' => $e->getMessage(),
                'paket_id' => $id,
                'user_id' => $userId
            ]);
            
            return redirect()->back()->with('error', 'Sepete eklenirken hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function hizmetSatinal($id)
    {
        $ayar = Ayar::first();
        $hizmet = DB::table('hizmetler')->where('id', $id)->where('durum', 1)->first();
        
        if (!$hizmet) {
            abort(404, 'Hizmet bulunamadı');
        }
        
        // Hizmeti sepete ekle veya satın alma sayfasına yönlendir
        return redirect()->to(localized_route('sepet'))->with('info', 'Hizmet sepete eklenecek');
    }
    
    public function hizmet($seo)
    {
        $ayar = Ayar::first();
        // Hizmet detayı — seo'ya göre getir; yoksa 404 (view $hizmet bekliyor)
        $hizmet = DB::table('hizmetler')->where('seo', $seo)->where('durum', 1)->first();
        abort_unless($hizmet, 404, 'Hizmet bulunamadı');
        return view('tema.hizmet', compact('ayar', 'seo', 'hizmet'));
    }
    
    public function referansDetay($seo)
    {
        $referans = DB::table('referanslar')
            ->where('seo', $seo)
            ->where('durum', 1)
            ->when(Schema::hasColumn('referanslar', 'dil'), function ($query) {
                return $query->where('dil', 1);
            })
            ->first();
        
        if (!$referans) {
            abort(404);
        }
        
        if (isset($referans->adi)) {
            $referans->adi = TranslationHelper::translateField('referanslar', $referans->id, 'adi', $referans->adi);
        }
        if (isset($referans->kisa)) {
            $referans->kisa = TranslationHelper::translateField('referanslar', $referans->id, 'kisa', $referans->kisa);
        }
        if (isset($referans->aciklama)) {
            $referans->aciklama = TranslationHelper::translateField('referanslar', $referans->id, 'aciklama', $referans->aciklama);
        }
        
        // Arka plan
        $arkaplan = DB::table('arka_plan')->first();
        
        return view('tema.referans-detay', compact('referans', 'arkaplan'));
    }
    
    public function alanAdlarim()
    {
        $user = Auth::guard('uye')->user();
        
        if (!$user) {
            return redirect()->route('giris')->with('error', 'Bu sayfayı görüntülemek için giriş yapmalısınız.');
        }
        
        $userId = $user->id;
        
        // DomainOrder tablosundan aktif domainleri al
        $domain_orders = DB::table('domain_orders')
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'paid'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Eski alan_adlarim tablosundan da al (varsa)
        $eski_domainler = collect([]);
        if (Schema::hasTable('alan_adlarim')) {
            $eski_domainler = DB::table('alan_adlarim')
                ->where('uyeid', $userId)
                ->where('durum', 1)
                ->orderBy('kayit_tarihi', 'desc')
                ->get();
        }
        
        // İkisini birleştir
        $tum_domainler = $domain_orders->map(function($order) {
            return (object) [
                'id' => $order->id,
                'domain' => $order->domain,
                'kayit_tarihi' => $order->registered_at ?? $order->created_at,
                'bitis_tarihi' => $order->expires_at,
                'durum' => $order->status === 'active' ? 1 : 0,
                'order_id' => $order->reseller_order_id,
                'tip' => 'yeni', // Yeni sistem
            ];
        });
        
        $eski_domainler->each(function($domain) use ($tum_domainler) {
            // Eski domain zaten yeni sistemde varsa ekleme
            $var_mi = $tum_domainler->contains(function($item) use ($domain) {
                return $item->domain === $domain->domain;
            });

            if (!$var_mi) {
                $tum_domainler->push((object) [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'kayit_tarihi' => $domain->kayit_tarihi,
                    'bitis_tarihi' => $domain->bitis_tarihi,
                    'durum' => $domain->durum,
                    'order_id' => $domain->order_id,
                    'tip' => 'eski', // Eski sistem
                ]);
            }
        });

        // ── ASIL KAYNAK: satilanlar tipi=3 ────────────────────────────────
        // Müşterilerin domainlerinin büyük çoğunluğu burada duruyor. Eskiden
        // yalnızca domain_orders + alan_adlarim okunuyordu; ikisi de pratikte
        // boş olduğu için domaini olan müşteriler panelde "0" görüyordu.
        try {
            $satilanDomainler = DB::table('satilanlar')
                ->where('uyeid', $userId)
                ->where('tipi', 3)
                ->whereNotNull('domain')->where('domain', '!=', '')
                ->orderByDesc('tarih')
                ->get();

            foreach ($satilanDomainler as $d) {
                $var_mi = $tum_domainler->contains(fn ($item) => strcasecmp((string) $item->domain, (string) $d->domain) === 0);
                if ($var_mi) {
                    continue;
                }

                $tum_domainler->push((object) [
                    'id'           => $d->id,
                    'domain'       => $d->domain,
                    'kayit_tarihi' => $d->baslangic_tarih ?? $d->tarih,
                    'bitis_tarihi' => $d->bitis_tarih ?? null,
                    'durum'        => (int) ($d->durum ?? 1) === 1 ? 1 : 0,
                    'order_id'     => null,
                    'tip'          => 'satis', // satilanlar kaydı
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('alanAdlarim satilanlar okuma hatasi', ['error' => $e->getMessage()]);
        }

        return view('tema.alan-adlarim', compact('tum_domainler', 'user'));
    }
    
    public function sitemap()
    {
        // Sitemap XML oluşturma
        $sayfalar = Sayfa::where('durum', true)->get();
        $bloglar = Blog::where('durum', true)->get();
        
        return response()->view('sitemap', compact('sayfalar', 'bloglar'))
            ->header('Content-Type', 'text/xml');
    }
    
    public function paytrOdeme(Request $request)
    {
        try {
            $userId = Auth::guard('uye')->id();
            
            // Sepet verilerini çek
            $sepet_raw = null;
            if (Schema::hasColumn('sepet', 'user_id')) {
                $sepet_raw = DB::table('sepet')->where('user_id', $userId)->get();
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                $sepet_raw = DB::table('sepet')->where('uyeid', $userId)->get();
            } else {
                return redirect()->to(localized_route('sepet'))->with('error', 'Sepetiniz boş.');
            }
            
            if ($sepet_raw->isEmpty()) {
                return redirect()->to(localized_route('sepet'))->with('error', 'Sepetiniz boş.');
            }
            
            // Toplam tutarı hesapla
            $toplam = 0;
            foreach ($sepet_raw as $item) {
                $fiyat = $item->fiyat ?? $item->tutar ?? $item->price ?? 0;
                $miktar = $item->miktar ?? $item->sure ?? 1;
                $toplam += (float) $fiyat * (int) $miktar;
            }
            
            // Fatura oluştur (eğer Fatura modeli varsa)
            if (class_exists('App\Models\Fatura')) {
                $fatura = \App\Models\Fatura::create([
                    'uye_id' => $userId,
                    'tutar' => $toplam,
                    'toplam' => $toplam,
                    'durum' => 'beklemede',
                    'fatura_no' => 'FAT' . time() . $userId,
                ]);
                
                $paytrService = new PaytrService();
                $result = $paytrService->createPayment(
                    $fatura->id,
                    route('siparis.sonuc', ['status' => 'success']),
                    route('siparis.sonuc', ['status' => 'cancel'])
                );
                
                if ($result['status'] === 'success') {
                    return view('tema.paytr-iframe', ['iframe_url' => $result['iframe_url']]);
                } else {
                    return redirect()->to(localized_route('sepet'))->with('error', 'Ödeme başlatılamadı: ' . ($result['message'] ?? 'Bilinmeyen hata'));
                }
            } else {
                // Fatura modeli yoksa, basit bir ödeme sayfası göster
                return redirect()->to(localized_route('sepet'))->with('info', 'Ödeme sistemi henüz aktif değil. Lütfen yöneticiye başvurun.');
            }
            
        } catch (\Exception $e) {
            Log::error('PayTR ödeme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->to(localized_route('sepet'))->with('error', 'Ödeme başlatılırken hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function iyzicoOdeme(Request $request)
    {
        try {
            $userId = Auth::guard('uye')->id();
            
            // Sepet verilerini çek
            $sepet_raw = null;
            if (Schema::hasColumn('sepet', 'user_id')) {
                $sepet_raw = DB::table('sepet')->where('user_id', $userId)->get();
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                $sepet_raw = DB::table('sepet')->where('uyeid', $userId)->get();
            } else {
                return redirect()->to(localized_route('sepet'))->with('error', 'Sepetiniz boş.');
            }
            
            if ($sepet_raw->isEmpty()) {
                return redirect()->to(localized_route('sepet'))->with('error', 'Sepetiniz boş.');
            }
            
            // Toplam tutarı hesapla
            $toplam = 0;
            foreach ($sepet_raw as $item) {
                $fiyat = $item->fiyat ?? $item->tutar ?? $item->price ?? 0;
                $miktar = $item->miktar ?? $item->sure ?? 1;
                $toplam += (float) $fiyat * (int) $miktar;
            }
            
            // Fatura oluştur (eğer Fatura modeli varsa)
            if (class_exists('App\Models\Fatura')) {
                $fatura = \App\Models\Fatura::create([
                    'uye_id' => $userId,
                    'tutar' => $toplam,
                    'toplam' => $toplam,
                    'durum' => 'beklemede',
                    'fatura_no' => 'FAT' . time() . $userId,
                ]);
                
                $iyzicoService = new IyzicoService();
                $result = $iyzicoService->createPayment(
                    $fatura->id,
                    route('payment.iyzico.callback')
                );
                
                if ($result['status'] === 'success') {
                    return redirect($result['checkout_form_content']);
                } else {
                    return redirect()->to(localized_route('sepet'))->with('error', 'Ödeme başlatılamadı: ' . ($result['message'] ?? 'Bilinmeyen hata'));
                }
            } else {
                // Fatura modeli yoksa, basit bir ödeme sayfası göster
                return redirect()->to(localized_route('sepet'))->with('info', 'Ödeme sistemi henüz aktif değil. Lütfen yöneticiye başvurun.');
            }
            
        } catch (\Exception $e) {
            Log::error('Iyzico ödeme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->to(localized_route('sepet'))->with('error', 'Ödeme başlatılırken hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function bakiyem()
    {
        $userId = Auth::guard('uye')->id();
        $uye = Auth::guard('uye')->user();
        
        // Mevcut bakiye
        $bakiye = floatval($uye->bakiye ?? 0);
        
        // Bakiye geçmişi - paginate ile
        $bakiye_gecmisi = DB::table('bakiye_gecmisi')
            ->where('uye_id', $userId)
            ->orderByDesc('tarih')
            ->paginate(20);
        
        // Toplam yükleme
        $toplam_yukleme = DB::table('bakiye_gecmisi')
            ->where('uye_id', $userId)
            ->where('tip', 'yukleme')
            ->sum('tutar') ?? 0;
        
        // Toplam harcama
        $toplam_harcama = DB::table('bakiye_gecmisi')
            ->where('uye_id', $userId)
            ->where('tip', 'harcama')
            ->sum('tutar') ?? 0;
        
        return view('tema.bakiyem', compact('uye', 'bakiye', 'bakiye_gecmisi', 'toplam_yukleme', 'toplam_harcama'));
    }
    
    public function bakiyeYukle(Request $request)
    {
        // Stajyer: özel tutar desteği — radio yerine kullanıcı kendi tutarını girebilir
        $tutar = $request->filled('ozel_tutar') ? $request->ozel_tutar : $request->tutar;
        $request->merge(['tutar' => $tutar]);

        $request->validate([
            'tutar' => 'required|numeric|min:10|max:50000'
        ], [], ['tutar' => 'Tutar']);
        
        $userId = Auth::guard('uye')->id();
        $tutar = $request->tutar;
        
        try {
            // Fatura oluştur — kolonları schema'ya göre güvenli ekle (stajyer)
            $insert = [
                'uyeid' => $userId,
                'tutar' => $tutar,
                'durum' => 0, // Ödenmedi
                'fatura_no' => 'BAK-' . time() . $userId,
                'aciklama' => 'Bakiye Yükleme',
                'tarih' => now(),
            ];
            if (Schema::hasColumn('faturalar', 'toplam')) $insert['toplam'] = $tutar;
            if (Schema::hasColumn('faturalar', 'tip'))    $insert['tip']    = 'bakiye_yukleme';
            $fatura = DB::table('faturalar')->insertGetId($insert);
            
            // PayTR ile ödeme başlat
            $paytrService = new PaytrService();
            $result = $paytrService->createPayment(
                $fatura,
                route('bakiye.yukleme.sonuc', ['status' => 'success']),
                route('bakiye.yukleme.sonuc', ['status' => 'cancel'])
            );
            
            if ($result['status'] === 'success') {
                return view('tema.paytr-iframe', ['iframe_url' => $result['iframe_url']]);
            } else {
                return redirect()->route('bakiyem')->with('error', 'Ödeme başlatılamadı: ' . ($result['message'] ?? 'Bilinmeyen hata'));
            }
        } catch (\Exception $e) {
            Log::error('Bakiye yükleme hatası', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return redirect()->route('bakiyem')->with('error', 'Bakiye yükleme başlatılırken hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function bakiyeYuklemeSonuc(Request $request)
    {
        $status = $request->get('status');
        $userId = Auth::guard('uye')->id();
        
        if ($status === 'success') {
            // PayTR callback'inden gelen verileri kontrol et
            $hash = $request->get('hash');
            $merchant_oid = $request->get('merchant_oid');
            
            if ($merchant_oid) {
                // Fatura ID'yi merchant_oid'den çıkar
                $fatura_id = str_replace('BAK-', '', explode('-', $merchant_oid)[0]);
                
                // Faturayı kontrol et ve ödeme onaylandıysa bakiyeyi güncelle
                $fatura = DB::table('faturalar')->where('id', $fatura_id)->first();
                
                if ($fatura && $fatura->durum == 0) {
                    DB::beginTransaction();
                    try {
                        // Faturayı ödendi olarak işaretle
                        $update_data = ['durum' => 1];
                        // TAHSILAT TARIHI
                        // Burada 'odeme_tarihi' yaziliyordu ama faturalar
                        // tablosunda BOYLE BIR KOLON YOK; hasColumn kontrolu
                        // hep false donuyor ve hicbir tarih yazilmiyordu.
                        // Raporlar/gunluk hareketler 'odenen_tarih' uzerinden
                        // calisir -> musterinin bakiyeyle odedigi faturalar
                        // hicbir gune dusmuyordu.
                        if (Schema::hasColumn('faturalar', 'odenen_tarih')) {
                            $update_data['odenen_tarih'] = now()->toDateString();
                        }
                        if (Schema::hasColumn('faturalar', 'odeme_tarihi')) {
                            $update_data['odeme_tarihi'] = now();
                        }
                        DB::table('faturalar')
                            ->where('id', $fatura_id)
                            ->update($update_data);
                        
                        // Bakiyeyi güncelle
                        $uye = DB::table('uyeler')->where('id', $userId)->first();
                        $yeni_bakiye = ($uye->bakiye ?? 0) + $fatura->tutar;
                        
                        DB::table('uyeler')
                            ->where('id', $userId)
                            ->update(['bakiye' => $yeni_bakiye]);
                        
                        // Bakiye geçmişine ekle
                        DB::table('bakiye_gecmisi')->insert([
                            'uye_id' => $userId,
                            'tip' => 'yukleme',
                            'tutar' => $fatura->tutar,
                            'bakiye_once' => $uye->bakiye ?? 0,
                            'bakiye_sonra' => $yeni_bakiye,
                            'aciklama' => 'Bakiye Yükleme - Fatura: ' . $fatura->fatura_no,
                            'tarih' => now()
                        ]);
                        
                        DB::commit();

                        // 📧 Bakiye yüklendi maili (transaction sonrası, fail-safe)
                        try {
                            \App\Services\CustomerNotifier::bakiyeYuklendi(
                                $userId,
                                (float) $fatura->tutar,
                                (float) $yeni_bakiye
                            );
                            // Ödeme onaylandı mailı da gönder (fatura ödendi)
                            \App\Services\CustomerNotifier::odemeOnaylandi(
                                $userId,
                                $fatura_id,
                                (float) $fatura->tutar
                            );
                        } catch (\Throwable $e) {
                            \Log::warning('Bakiye/ödeme mail bildirim hatası', ['err' => $e->getMessage()]);
                        }
                        
                        return redirect()->route('bakiyem')->with('success', 'Bakiyeniz başarıyla yüklendi!');
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Bakiye güncelleme hatası', ['error' => $e->getMessage()]);
                        return redirect()->route('bakiyem')->with('error', 'Bakiye güncellenirken hata oluştu.');
                    }
                }
            }
            
            return redirect()->route('bakiyem')->with('success', 'Ödeme başarılı!');
        } else {
            return redirect()->route('bakiyem')->with('error', 'Ödeme iptal edildi.');
        }
    }

    /**
     * PayTR ödeme dönüş sayfası (kullanıcının yönlendirildiği sayfa).
     *
     * ÖNEMLİ: Asıl ödeme onayı SUNUCUDAN-SUNUCUYA gelen PaymentWebhookController::paytrCallback
     * içinde yapılır (fatura durum=1, satilanlar paytronay=1, sepet temizleme). Bu metod yalnızca
     * kullanıcıya sonucu GÖSTERİR; burada hiçbir bakiye/fatura yazma mantığı yoktur (çift işleme olmaz).
     * Callback ile bu sayfa arasında küçük bir gecikme olabileceğinden, fatura henüz "ödendi" değilse
     * "işleniyor" mesajı gösterilir.
     */
    public function siparisSonuc(Request $request)
    {
        $status = $request->get('status');
        $userId = Auth::guard('uye')->id();

        // Başarısız / iptal
        if ($status !== 'success') {
            return redirect()->to(localized_route('sepet'))
                ->with('error', 'Ödeme tamamlanmadı veya iptal edildi. Dilerseniz tekrar deneyebilirsiniz.');
        }

        // status=success → callback faturayı işlemiş olmalı. Son işlemi kontrol et.
        $odendi = false;
        try {
            if ($userId && Schema::hasTable('faturalar')) {
                // Bu kullanıcının en son kredi kartı faturası (son 30 dk içinde)
                $sonFatura = DB::table('faturalar')
                    ->where('uyeid', $userId)
                    ->where(function ($q) {
                        $q->where('odeme_yontemi', 'like', '%Online%')
                          ->orWhere('tip', 'sepet_odeme');
                    })
                    ->orderByDesc('id')
                    ->first();

                if ($sonFatura) {
                    // durum 1 = ödendi (callback işlemiş)
                    $odendi = isset($sonFatura->durum) && (string) $sonFatura->durum === '1';
                }
            }
        } catch (\Throwable $e) {
            Log::warning('siparisSonuc fatura kontrol hatası', ['err' => $e->getMessage()]);
        }

        if ($odendi) {
            return redirect()->route('web.paketlerim')
                ->with('success', 'Ödemeniz başarıyla alındı. Siparişiniz hesabınıza tanımlandı.');
        }

        // Callback henüz ulaşmamış olabilir — güvenli "işleniyor" mesajı
        return redirect()->route('web.paketlerim')
            ->with('info', 'Ödemeniz alındı ve işleniyor. Siparişiniz birkaç dakika içinde "Paketlerim" ve "Faturalarım" sayfanıza yansıyacaktır. Sorun yaşarsanız destek ile iletişime geçebilirsiniz.');
    }

    private function uyeListele(string $tablo, string $view)
    {
        $ayarlar = Ayar::first();
        $uyeId = Auth::guard('uye')->id();
        $kayitlar = collect();
        try {
            if (Schema::hasTable($tablo)) {
                $kayitlar = DB::table($tablo)
                    ->where('uyeid', $uyeId)
                    ->orderByDesc('id')
                    ->paginate(20);
            }
        } catch (\Throwable $e) {
            \Log::warning("$tablo listele hatasi", ['error' => $e->getMessage()]);
        }
        return view($view, compact('ayarlar', 'kayitlar'));
    }

    public function hizmetlerim()    { return $this->uyeListele('musteri_hizmetler',   'tema.hizmetlerim'); }
    public function sozlesmelerim()  { return $this->uyeListele('musteri_sozlesmeler', 'tema.sozlesmelerim'); }
    public function raporlarim()     { return $this->uyeListele('musteri_raporlar',    'tema.raporlarim'); }
    public function efaturalarim()   { return $this->uyeListele('musteri_efaturalar',  'tema.efaturalarim'); }
    public function referanslarim()  { return $this->uyeListele('musteri_referanslar', 'tema.referanslarim'); }

    public function dosyalarim()
    {
        return view('tema.dosyalarim');
    }
    
    public function favorilerim()
    {
        return view('tema.favorilerim');
    }
}