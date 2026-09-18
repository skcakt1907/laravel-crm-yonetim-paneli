<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sepet;
use App\Models\Ayar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Services\PaytrService;

class SepetController extends Controller
{
    public function index()
    {
        try {
            $ayarlar = Ayar::first();
            $userId = Auth::guard('uye')->id();
            
            // DB::table ile direkt sorgula - TÜM kolonları getir
            $sepet_raw = null;
            
            if (Schema::hasColumn('sepet', 'user_id')) {
                // Yeni format - TÜM kolonları getir
                $sepet_raw = DB::table('sepet')->where('user_id', $userId)->get();
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                // Eski format - TÜM kolonları getir
                $sepet_raw = DB::table('sepet')->where('uyeid', $userId)->get();
            } else {
                $sepet_raw = collect([]);
            }
            
            // Her item için tüm kolonları logla
            foreach ($sepet_raw as $item) {
                Log::info('Sepet item - TÜM KOLONLAR', [
                    'item_id' => $item->id,
                    'all_columns' => (array) $item,
                    'column_names' => array_keys((array) $item)
                ]);
            }
            
            // Önce geçersiz kayıtları bul ve sil
            $gecersiz_kayitlar = [];
            foreach ($sepet_raw as $item) {
                $domain_adi_check = $item->name ?? $item->urun_adi ?? $item->adi ?? $item->domain ?? null;
                $fiyat_check = $item->fiyat ?? $item->tutar ?? $item->price ?? 0;
                
                if (!$domain_adi_check && $fiyat_check <= 0) {
                    $gecersiz_kayitlar[] = $item->id;
                }
            }
            
            // Geçersiz kayıtları sil
            if (count($gecersiz_kayitlar) > 0) {
                Log::warning('Geçersiz sepet kayıtları siliniyor', [
                    'item_ids' => $gecersiz_kayitlar,
                    'reason' => 'Domain adı ve fiyat yok'
                ]);
                
                $query = DB::table('sepet')->whereIn('id', $gecersiz_kayitlar);
                if (Schema::hasColumn('sepet', 'user_id')) {
                    $query->where('user_id', $userId);
                } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                    $query->where('uyeid', $userId);
                }
                $query->delete();
                
                // Geçersiz kayıtları collection'dan çıkar
                $sepet_raw = $sepet_raw->reject(function($item) use ($gecersiz_kayitlar) {
                    return in_array($item->id, $gecersiz_kayitlar);
                });
            }
            
            // Önce tüm fiyatları güncelle (eğer 0 ise)
            foreach ($sepet_raw as $item) {
                $fiyat_mevcut = 0;
                if (isset($item->fiyat) && $item->fiyat > 0) {
                    $fiyat_mevcut = (float) $item->fiyat;
                } elseif (isset($item->tutar) && $item->tutar > 0) {
                    $fiyat_mevcut = (float) $item->tutar;
                } elseif (isset($item->price) && $item->price > 0) {
                    $fiyat_mevcut = (float) $item->price;
                }
                
                $tipi = $item->urun_tipi ?? ($item->tipi ?? null);
                $urun_id = $item->urun_id ?? null;
                
                // Eğer tipi yoksa ama urun_id varsa, urun_id'den tipi belirle
                if (!$tipi && $urun_id) {
                    // urun_id bir sayı ise, yazilimlar veya hostingler tablosundan kontrol et
                    $paket_check = DB::table('yazilimlar')->where('id', $urun_id)->first();
                    if ($paket_check) {
                        $tipi = 'web-paket';
                        // Tipi kaydet
                        $update_tipi = [];
                        if (Schema::hasColumn('sepet', 'urun_tipi')) {
                            $update_tipi['urun_tipi'] = 'web-paket';
                        } elseif (Schema::hasColumn('sepet', 'tipi')) {
                            $update_tipi['tipi'] = 2;
                        }
                        if (count($update_tipi) > 0) {
                            DB::table('sepet')->where('id', $item->id)->update($update_tipi);
                            $item->urun_tipi = 'web-paket';
                            $item->tipi = 2;
                        }
                    } else {
                        $hosting_check = DB::table('hostingler')->where('id', $urun_id)->first();
                        if ($hosting_check) {
                            $tipi = 'hosting';
                            // Tipi kaydet
                            $update_tipi = [];
                            if (Schema::hasColumn('sepet', 'urun_tipi')) {
                                $update_tipi['urun_tipi'] = 'hosting';
                            } elseif (Schema::hasColumn('sepet', 'tipi')) {
                                $update_tipi['tipi'] = 1;
                            }
                            if (count($update_tipi) > 0) {
                                DB::table('sepet')->where('id', $item->id)->update($update_tipi);
                                $item->urun_tipi = 'hosting';
                                $item->tipi = 1;
                            }
                        }
                    }
                }
                
                // Eğer fiyat 0 ise ve web paket/hosting ise, veritabanından fiyatı çek
                if ($fiyat_mevcut <= 0 && ($tipi == 'web-paket' || $tipi == 2 || $tipi == 'hosting' || $tipi == 1)) {
                    // Web paket veya hosting ise, veritabanından fiyatı çek
                    if ($tipi == 'web-paket' || $tipi == 2) {
                        if ($urun_id) {
                            $paket = DB::table('yazilimlar')->where('id', $urun_id)->first();
                            if ($paket) {
                                // TUTAR kolonunu öncelikle kontrol et
                                $yeni_fiyat = 0;
                                if (isset($paket->tutar) && $paket->tutar > 0) {
                                    $yeni_fiyat = (float) $paket->tutar;
                                } elseif (isset($paket->fiyat) && $paket->fiyat > 0) {
                                    $yeni_fiyat = (float) $paket->fiyat;
                                } elseif (isset($paket->price) && $paket->price > 0) {
                                    $yeni_fiyat = (float) $paket->price;
                                } elseif (isset($paket->ucret) && $paket->ucret > 0) {
                                    $yeni_fiyat = (float) $paket->ucret;
                                }
                                
                                Log::info('Sepet fiyat güncelleme - Web paket', [
                                    'item_id' => $item->id,
                                    'urun_id' => $urun_id,
                                    'tutar' => $paket->tutar ?? 'N/A',
                                    'fiyat' => $paket->fiyat ?? 'N/A',
                                    'yeni_fiyat' => $yeni_fiyat
                                ]);
                                
                                if ($yeni_fiyat > 0) {
                                    // TÜM olası fiyat kolonlarını güncelle
                                    $update_data = [];
                                    if (Schema::hasColumn('sepet', 'fiyat')) {
                                        $update_data['fiyat'] = $yeni_fiyat;
                                    }
                                    if (Schema::hasColumn('sepet', 'tutar')) {
                                        $update_data['tutar'] = $yeni_fiyat;
                                    }
                                    if (Schema::hasColumn('sepet', 'price')) {
                                        $update_data['price'] = $yeni_fiyat;
                                    }
                                    
                                    // Eğer hiçbir fiyat kolonu yoksa, ALTER TABLE ile ekle
                                    if (count($update_data) == 0) {
                                        // Fiyat kolonu yok, o zaman ekle
                                        Log::warning('Sepet tablosunda fiyat kolonu yok - ekleniyor!', [
                                            'item_id' => $item->id,
                                            'urun_id' => $urun_id,
                                            'yeni_fiyat' => $yeni_fiyat
                                        ]);
                                        
                                        // Fiyat kolonunu ekle
                                        try {
                                            // Direkt SQL ile ekle (Schema cache'i bypass et)
                                            DB::statement('ALTER TABLE `sepet` ADD COLUMN IF NOT EXISTS `fiyat` DECIMAL(10,2) DEFAULT 0');
                                            DB::statement('ALTER TABLE `sepet` ADD COLUMN IF NOT EXISTS `tutar` DECIMAL(10,2) DEFAULT 0');
                                            
                                            Log::info('Sepet tablosuna fiyat kolonları eklendi');
                                            
                                            // Direkt güncelle (Schema kontrolü yapmadan)
                                            $update_data = ['fiyat' => $yeni_fiyat, 'tutar' => $yeni_fiyat];
                                            
                                            Log::info('Fiyat kolonları eklendi, güncelleme yapılıyor', [
                                                'update_data' => $update_data
                                            ]);
                                        } catch (\Exception $e) {
                                            // IF NOT EXISTS desteklenmiyorsa, try-catch ile kontrol et
                                            try {
                                                // Önce kontrol et
                                                $columns = DB::select("SHOW COLUMNS FROM `sepet` LIKE 'fiyat'");
                                                if (empty($columns)) {
                                                    DB::statement('ALTER TABLE `sepet` ADD COLUMN `fiyat` DECIMAL(10,2) DEFAULT 0');
                                                }
                                                
                                                $columns = DB::select("SHOW COLUMNS FROM `sepet` LIKE 'tutar'");
                                                if (empty($columns)) {
                                                    DB::statement('ALTER TABLE `sepet` ADD COLUMN `tutar` DECIMAL(10,2) DEFAULT 0');
                                                }
                                                
                                                $update_data = ['fiyat' => $yeni_fiyat, 'tutar' => $yeni_fiyat];
                                                
                                                Log::info('Fiyat kolonları eklendi (fallback)', [
                                                    'update_data' => $update_data
                                                ]);
                                            } catch (\Exception $e2) {
                                                Log::error('Sepet tablosuna kolon eklenemedi', [
                                                    'error' => $e2->getMessage(),
                                                    'trace' => $e2->getTraceAsString()
                                                ]);
                                            }
                                        }
                                    }
                                    
                                    if (count($update_data) > 0) {
                                        $query = DB::table('sepet')->where('id', $item->id);
                                        if (Schema::hasColumn('sepet', 'user_id')) {
                                            $query->where('user_id', $userId);
                                        } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                                            $query->where('uyeid', $userId);
                                        }
                                        $query->update($update_data);
                                        
                                        // Item'i güncelle
                                        foreach ($update_data as $kolon => $deger) {
                                            $item->$kolon = $deger;
                                        }
                                        
                                        $fiyat_mevcut = $yeni_fiyat;
                                    }
                                }
                            }
                        }
                    } elseif ($tipi == 'hosting' || $tipi == 1) {
                        if ($urun_id) {
                            $hosting = DB::table('hostingler')->where('id', $urun_id)->first();
                            if ($hosting) {
                                $yeni_fiyat = 0;
                                if (isset($hosting->tutar) && $hosting->tutar > 0) {
                                    $yeni_fiyat = (float) $hosting->tutar;
                                } elseif (isset($hosting->fiyat) && $hosting->fiyat > 0) {
                                    $yeni_fiyat = (float) $hosting->fiyat;
                                } elseif (isset($hosting->price) && $hosting->price > 0) {
                                    $yeni_fiyat = (float) $hosting->price;
                                }
                                
                                if ($yeni_fiyat > 0) {
                                    $update_data = [];
                                    if (Schema::hasColumn('sepet', 'fiyat')) {
                                        $update_data['fiyat'] = $yeni_fiyat;
                                    }
                                    if (Schema::hasColumn('sepet', 'tutar')) {
                                        $update_data['tutar'] = $yeni_fiyat;
                                    }
                                    if (Schema::hasColumn('sepet', 'price')) {
                                        $update_data['price'] = $yeni_fiyat;
                                    }
                                    
                                    if (count($update_data) > 0) {
                                        $query = DB::table('sepet')->where('id', $item->id);
                                        if (Schema::hasColumn('sepet', 'user_id')) {
                                            $query->where('user_id', $userId);
                                        } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                                            $query->where('uyeid', $userId);
                                        }
                                        $query->update($update_data);
                                        
                                        foreach ($update_data as $kolon => $deger) {
                                            $item->$kolon = $deger;
                                        }
                                        
                                        $fiyat_mevcut = $yeni_fiyat;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Eğer tipi web-paket veya hosting ise, domain kontrolü yapma!
                // Sadece tipi null veya domain ise domain kontrolü yap
                if ($fiyat_mevcut <= 0 && ($tipi == null || $tipi == 'domain' || $tipi == 0 || $tipi === '0')) {
                    // Önce tüm kolonları logla
                    Log::info('Sepet item tüm kolonlar', [
                        'item_id' => $item->id,
                        'all_columns' => (array) $item
                    ]);
                    
                    // Domain adını bul - tüm olası kolonları kontrol et
                    $domain_adi = null;
                    if (isset($item->name) && $item->name && $item->name !== 'Ürün') {
                        $domain_adi = $item->name;
                    } elseif (isset($item->urun_adi) && $item->urun_adi && $item->urun_adi !== 'Ürün') {
                        $domain_adi = $item->urun_adi;
                    } elseif (isset($item->adi) && $item->adi && $item->adi !== 'Ürün') {
                        $domain_adi = $item->adi;
                    } elseif (isset($item->domain) && $item->domain) {
                        $domain_adi = $item->domain;
                    }
                    
                    // Domain kontrolü - daha esnek
                    $is_domain = false;
                    
                    // Tipi kontrolü - tüm olasılıkları kontrol et
                    if ($tipi == 'domain' || $tipi == 0 || $tipi === '0' || $tipi === 0 || $tipi == '0' || $tipi == null) {
                        // Tipi domain veya null ise, domain adına bak
                        if ($domain_adi && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', trim($domain_adi))) {
                            $is_domain = true;
                        }
                    }
                    
                    // Domain adı formatı kontrolü (tipi ne olursa olsun)
                    if (!$is_domain && $domain_adi) {
                        // Domain formatı: example.com, test.net, vb.
                        if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', trim($domain_adi))) {
                            $is_domain = true;
                        }
                    }
                    
                    // Eğer domain adı varsa ve tipi null/0 ise, domain olarak kabul et
                    if (!$is_domain && $domain_adi && ($tipi == null || $tipi == 0 || $tipi === '0')) {
                        if (strpos($domain_adi, '.') !== false) {
                            $is_domain = true;
                        }
                    }
                    
                    if ($is_domain && $domain_adi) {
                        Log::info('Sepet fiyat güncelleme başlatıldı', [
                            'item_id' => $item->id,
                            'domain' => $domain_adi,
                            'tipi' => $tipi,
                            'mevcut_fiyat' => $fiyat_mevcut
                        ]);
                        
                        $yeni_fiyat = $this->getDomainPriceFromCart($domain_adi);
                        
                        Log::info('Sepet fiyat çekme sonucu', [
                            'item_id' => $item->id,
                            'domain' => $domain_adi,
                            'yeni_fiyat' => $yeni_fiyat
                        ]);
                        
                        if ($yeni_fiyat > 0) {
                            $update_data = [];
                            if (Schema::hasColumn('sepet', 'fiyat')) {
                                $update_data['fiyat'] = $yeni_fiyat;
                            }
                            if (Schema::hasColumn('sepet', 'tutar')) {
                                $update_data['tutar'] = $yeni_fiyat;
                            }
                            if (Schema::hasColumn('sepet', 'price')) {
                                $update_data['price'] = $yeni_fiyat;
                            }
                            
                            if (count($update_data) > 0) {
                                $query = DB::table('sepet')->where('id', $item->id);
                                if (Schema::hasColumn('sepet', 'user_id')) {
                                    $query->where('user_id', $userId);
                                } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                                    $query->where('uyeid', $userId);
                                }
                                $updated = $query->update($update_data);
                                
                                Log::info('Sepet fiyat güncellendi', [
                                    'item_id' => $item->id,
                                    'updated' => $updated,
                                    'update_data' => $update_data
                                ]);
                                
                                // Item'i güncelle (memory'de)
                                if (Schema::hasColumn('sepet', 'fiyat')) {
                                    $item->fiyat = $yeni_fiyat;
                                }
                                if (Schema::hasColumn('sepet', 'tutar')) {
                                    $item->tutar = $yeni_fiyat;
                                }
                                if (Schema::hasColumn('sepet', 'price')) {
                                    $item->price = $yeni_fiyat;
                                }
                            }
                        } else {
                            Log::warning('Sepet fiyat bulunamadı', [
                                'item_id' => $item->id,
                                'domain' => $domain_adi
                            ]);
                        }
                    } else {
                        Log::debug('Sepet item domain değil', [
                            'item_id' => $item->id,
                            'domain_adi' => $domain_adi,
                            'tipi' => $tipi,
                            'is_domain' => $is_domain
                        ]);
                    }
                }
            }
            
            // Sepet verilerini view formatına çevir
            $sepet = $sepet_raw->map(function($item) use ($userId) {
                // Eski format: adi, tutar, sure, tipi
                // Yeni format: urun_adi, fiyat, miktar, aciklama
                // Fiyat değerini bul - önce fiyat, sonra tutar, sonra price, en son 0
                $fiyat_degeri = 0;
                if (isset($item->fiyat) && $item->fiyat > 0) {
                    $fiyat_degeri = (float) $item->fiyat;
                } elseif (isset($item->tutar) && $item->tutar > 0) {
                    $fiyat_degeri = (float) $item->tutar;
                } elseif (isset($item->price) && $item->price > 0) {
                    $fiyat_degeri = (float) $item->price;
                }
                
                // Domain adını bul - tüm olası kolonları kontrol et
                $domain_adi = null;
                if (isset($item->name) && $item->name && $item->name !== 'Ürün') {
                    $domain_adi = $item->name;
                } elseif (isset($item->urun_adi) && $item->urun_adi && $item->urun_adi !== 'Ürün') {
                    $domain_adi = $item->urun_adi;
                } elseif (isset($item->adi) && $item->adi && $item->adi !== 'Ürün') {
                    $domain_adi = $item->adi;
                } elseif (isset($item->domain) && $item->domain) {
                    $domain_adi = $item->domain;
                }
                
                // Eğer domain adı bulunamadıysa, tüm kolonları logla
                if (!$domain_adi) {
                    Log::warning('Sepet item domain adı bulunamadı', [
                        'item_id' => $item->id,
                        'name' => $item->name ?? 'N/A',
                        'urun_adi' => $item->urun_adi ?? 'N/A',
                        'adi' => $item->adi ?? 'N/A',
                        'domain' => $item->domain ?? 'N/A',
                        'all_columns' => (array) $item
                    ]);
                }
                
                // Eğer fiyat 0 ise, sadece domain için fiyat çek (web paket ve hosting için fiyat zaten kaydedilmiş olmalı)
                if ($fiyat_degeri <= 0) {
                    $tipi = $item->urun_tipi ?? ($item->tipi ?? null);
                    $urun_adi = $item->name ?? $item->urun_adi ?? $item->adi ?? '';
                    
                    // Domain kontrolü - sadece domain için fiyat çek
                    $is_domain = false;
                    if ($tipi == 'domain' || $tipi == 0 || $tipi === '0') {
                        $is_domain = true;
                    } elseif ($tipi === null && $urun_adi) {
                        // Tipi null ise, ürün adına bak - eğer domain formatı değilse web paketi olabilir
                        if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $urun_adi)) {
                            $is_domain = true;
                        }
                    } elseif ($urun_adi && strpos($urun_adi, '.') !== false) {
                        // Domain formatı kontrolü (örn: example.com)
                        if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $urun_adi)) {
                            $is_domain = true;
                        }
                    }
                    
                    // Sadece domain ise fiyat çek
                    if ($is_domain && $urun_adi) {
                        Log::info('Sepet domain fiyat çekme başlatıldı', [
                            'domain' => $urun_adi,
                            'tipi' => $tipi,
                            'item_id' => $item->id
                        ]);
                        
                        $fiyat_degeri = $this->getDomainPriceFromCart($urun_adi);
                        
                        Log::info('Sepet domain fiyat çekme sonucu', [
                            'domain' => $urun_adi,
                            'fiyat' => $fiyat_degeri
                        ]);
                        
                        // Eğer fiyat bulunduysa veritabanını güncelle
                        if ($fiyat_degeri > 0) {
                            $update_data = [];
                            if (Schema::hasColumn('sepet', 'fiyat')) {
                                $update_data['fiyat'] = $fiyat_degeri;
                            }
                            if (Schema::hasColumn('sepet', 'tutar')) {
                                $update_data['tutar'] = $fiyat_degeri;
                            }
                            if (Schema::hasColumn('sepet', 'price')) {
                                $update_data['price'] = $fiyat_degeri;
                            }
                            
                            if (count($update_data) > 0) {
                                $query = DB::table('sepet')->where('id', $item->id);
                                if (Schema::hasColumn('sepet', 'user_id')) {
                                    $query->where('user_id', $userId);
                                } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                                    $query->where('uyeid', $userId);
                                }
                                $updated = $query->update($update_data);
                                Log::info('Sepet fiyat güncellendi', [
                                    'item_id' => $item->id,
                                    'updated' => $updated,
                                    'update_data' => $update_data
                                ]);
                                
                                // Item'i güncelle (memory'de)
                                if (Schema::hasColumn('sepet', 'fiyat')) {
                                    $item->fiyat = $fiyat_degeri;
                                }
                                if (Schema::hasColumn('sepet', 'tutar')) {
                                    $item->tutar = $fiyat_degeri;
                                }
                                if (Schema::hasColumn('sepet', 'price')) {
                                    $item->price = $fiyat_degeri;
                                }
                            }
                        }
                    } else {
                        // Web paket veya hosting için fiyat zaten kaydedilmiş olmalı
                        // Eğer 0 ise, logla ama fiyat çekme
                        Log::warning('Sepet item fiyat 0 ama domain değil', [
                            'item_id' => $item->id,
                            'tipi' => $tipi,
                            'urun_adi' => $urun_adi,
                            'fiyat_mevcut' => $fiyat_degeri,
                            'all_columns' => (array) $item
                        ]);
                    }
                }
                
                // Ürün adını bul
                $urun_adi_final = $item->name ?? $item->urun_adi ?? $item->adi ?? 'Ürün';
                
                // Tüm kolonları return et (view'da kullanılabilir)
                $return_data = [
                    'id' => $item->id,
                    'urun_adi' => $urun_adi_final,
                    'fiyat' => $fiyat_degeri,
                    'miktar' => $item->miktar ?? $item->sure ?? 1,
                    'aciklama' => $item->aciklama ?? $this->getUrunAciklama($item),
                ];
                
                // Tüm kolonları ekle (debug için)
                if (config('app.debug')) {
                    $return_data['urun_id'] = $item->urun_id ?? null;
                    $return_data['urun_tipi'] = $item->urun_tipi ?? null;
                    $return_data['tipi'] = $item->tipi ?? null;
                    $return_data['tutar'] = $item->tutar ?? null;
                    $return_data['price'] = $item->price ?? null;
                }
                
                return (object) $return_data;
            });
            
            $toplam = $sepet->sum(function($item) {
                return ($item->fiyat ?? 0) * ($item->miktar ?? 1);
            });
            
            // Kupon kontrolü
            $kupon_kod = session('kupon_kod', '');
            $kupon_indirim = 0;
            
            if ($kupon_kod) {
                $kupon_sonuc = \App\Services\KuponService::validateAndApply($kupon_kod, $toplam);
                if ($kupon_sonuc['success']) {
                    $kupon_indirim = $kupon_sonuc['indirim'];
                } else {
                    // Geçersiz kupon varsa session'dan sil
                    session()->forget('kupon_kod');
                }
            }
            
            // Bayi kodu kontrolü
            $bayi_bilgi = \App\Services\BayiKodService::getSessionBayi();
            $bayi_kodu = '';
            $bayi_indirim = 0;
            $bayi_indirim_orani = 0;
            
            if ($bayi_bilgi && isset($bayi_bilgi['bayi_kodu'])) {
                // Sepet toplamı değişmiş olabilir, yeniden hesapla
                $bayi_sonuc = \App\Services\BayiKodService::validateAndApply($bayi_bilgi['bayi_kodu'], $toplam);
                if ($bayi_sonuc['success']) {
                    $bayi_kodu = $bayi_bilgi['bayi_kodu'];
                    $bayi_indirim = $bayi_sonuc['indirim'];
                    $bayi_indirim_orani = $bayi_sonuc['indirim_orani'];
                    
                    // Güncellenmiş bilgiyi session'a kaydet
                    \App\Services\BayiKodService::setSessionBayi([
                        'bayi_kodu' => $bayi_kodu,
                        'bayi_id' => $bayi_sonuc['bayi_id'],
                        'indirim' => $bayi_indirim,
                        'indirim_orani' => $bayi_indirim_orani,
                        'komisyon_orani' => $bayi_sonuc['komisyon_orani'],
                    ]);
                } else {
                    // Geçersiz bayi kodu varsa session'dan sil
                    \App\Services\BayiKodService::clearSessionBayi();
                }
            }
            
            // KDV %20 (dışarıdan eklenir): önce indirimli ara toplam, sonra üstüne KDV
            $kdv_orani = 20;
            $ara_toplam = max(0, $toplam - $kupon_indirim - $bayi_indirim);
            $kdv = round($ara_toplam * $kdv_orani / 100, 2);
            $genel_toplam = round($ara_toplam + $kdv, 2);
            
            $uye = Auth::guard('uye')->user();
            $bakiye = $uye ? (float) ($uye->bakiye ?? 0) : 0.0;
            $indirimli_toplam = $genel_toplam;
            $yeterli_bakiye = $bakiye >= $indirimli_toplam;

            // DN Bank coin bakiyesi
            $dnbank_bakiye  = $uye ? (float) ($uye->dnbank_bakiye ?? 0) : 0.0;
            $dnbank_yeterli = $dnbank_bakiye >= $indirimli_toplam;
            
            // Debug: Sepet verilerini logla
            Log::info('Sepet sayfası yüklendi', [
                'user_id' => $userId,
                'sepet_count' => $sepet->count(),
                'toplam' => $toplam,
                'sepet_items' => $sepet->map(function($item) {
                    return [
                        'id' => $item->id,
                        'urun_adi' => $item->urun_adi,
                        'fiyat' => $item->fiyat
                    ];
                })->toArray()
            ]);
            
            // Kupon mesajı için tekrar kontrol (view'da kullanılmak üzere)
            $kupon_mesaj = '';
            if ($kupon_kod && $kupon_indirim > 0) {
                $kupon_sonuc = \App\Services\KuponService::validateAndApply($kupon_kod, $toplam);
                $kupon_mesaj = $kupon_sonuc['message'] ?? '';
            }
            
            // Bayi mesajı
            $bayi_mesaj = '';
            if ($bayi_kodu && $bayi_indirim > 0) {
                $bayi_mesaj = '%' . number_format($bayi_indirim_orani, 0) . ' bayi indirimi uygulandı!';
            }

            // ════════════════════════════════════════════════════════════
            // ÖNERİLEN PAKETLER (madde 1+2)
            // Önce sepet_oner=1 olan paketler; yoksa sepetteki ürünlerle
            // AYNI kategoriden otomatik 4-5 paket (sepettekiler hariç).
            // ════════════════════════════════════════════════════════════
            $onerilen_paketler = collect();
            try {
                // Sepetteki paket id'lerini topla (tekrar önerme)
                $sepettekiIdler = $sepet_raw->pluck('urun_id')->filter()->map(function($v){ return (int) $v; })->toArray();

                $kolonSec = ['id', 'adi', 'seo', 'resim', 'tutar', 'kategori', 'kisa'];
                $varKolon = function($c) { return Schema::hasColumn('yazilimlar', $c); };

                // Müşteriye özel teklif paketlerini (musteri != 0 / adı "teklif") her öneri
                // sorgusundan gizle — bunlar yalnızca gönderildiği müşteriye özel linkinden erişilir.
                $gizleOzel = function($q) use ($varKolon) {
                    if ($varKolon('musteri')) {
                        $q->where('musteri', 0);
                    }
                    $q->where(function ($w) {
                        $w->where('adi', 'not like', '%teklif%')
                          ->where('seo', 'not like', '%teklif%');
                    });
                    return $q;
                };

                // 1) Öne çıkarılanlar (sepet_oner=1)
                if ($varKolon('sepet_oner')) {
                    $q = DB::table('yazilimlar')
                        ->where('durum', 1)
                        ->where('sepet_oner', 1);
                    $gizleOzel($q);
                    if (!empty($sepettekiIdler)) $q->whereNotIn('id', $sepettekiIdler);
                    $onerilen_paketler = $q->limit(8)->get();
                }

                // 2) Yetersizse: sepetteki ürünlerin kategorilerinden benzer paketler
                if ($onerilen_paketler->count() < 8 && !empty($sepettekiIdler)) {
                    $kategoriler = DB::table('yazilimlar')
                        ->whereIn('id', $sepettekiIdler)
                        ->pluck('kategori')->filter()->unique()->toArray();

                    if (!empty($kategoriler)) {
                        $halihazirIdler = array_merge($sepettekiIdler, $onerilen_paketler->pluck('id')->toArray());
                        $benzer = DB::table('yazilimlar')
                            ->where('durum', 1)
                            ->whereIn('kategori', $kategoriler)
                            ->whereNotIn('id', $halihazirIdler);
                        $gizleOzel($benzer);
                        $benzer = $benzer
                            ->inRandomOrder()
                            ->limit(8 - $onerilen_paketler->count())
                            ->get();
                        $onerilen_paketler = $onerilen_paketler->concat($benzer);
                    }
                }

                // 3) Hâlâ boşsa: rastgele aktif paketler (sepettekiler hariç)
                if ($onerilen_paketler->count() === 0) {
                    $q = DB::table('yazilimlar')->where('durum', 1);
                    $gizleOzel($q);
                    if (!empty($sepettekiIdler)) $q->whereNotIn('id', $sepettekiIdler);
                    $onerilen_paketler = $q->inRandomOrder()->limit(8)->get();
                }
            } catch (\Throwable $e) {
                $onerilen_paketler = collect();
            }

            return response()
                ->view('tema.sepet', compact(
                    'ayarlar', 'sepet', 'toplam', 'ara_toplam', 'kdv', 'kdv_orani', 'genel_toplam',
                    'bakiye', 'indirimli_toplam', 'yeterli_bakiye',
                    'dnbank_bakiye', 'dnbank_yeterli',
                    'kupon_kod', 'kupon_indirim', 'kupon_mesaj',
                    'bayi_kodu', 'bayi_indirim', 'bayi_indirim_orani', 'bayi_mesaj',
                    'onerilen_paketler'
                ))
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
            
        } catch (\Exception $e) {
            Log::error('Sepet yükleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('hesabim')->with('error', 'Sepet yüklenirken hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * Sepetteki domain için fiyat çek
     */
    private function getDomainPriceFromCart($domain)
    {
        try {
            // Domain uzantısını çıkar
            $parts = explode('.', $domain);
            if (count($parts) < 2) {
                return 0;
            }
            
            $extension = '.' . end($parts);
            
            // Önce domain_fiyatlar tablosundan dene
            if (Schema::hasTable('domain_fiyatlar')) {
                $record = DB::table('domain_fiyatlar')
                    ->where('uzanti', $extension)
                    ->first();
                
                if ($record && isset($record->kayit_fiyat)) {
                    return (float) $record->kayit_fiyat;
                }
            }
            
            // Eski alanadi tablosundan fiyat çek (JSON array yapısı)
            if (Schema::hasTable('alanadi')) {
                $rows = DB::table('alanadi')->get();
                
                Log::info('Alanadi tablosu okunuyor', [
                    'rows_count' => $rows->count(),
                    'domain' => $domain,
                    'extension' => $extension
                ]);
                
                foreach ($rows as $row_index => $row) {
                    $uzantilar_raw = $row->uzanti ?? '[]';
                    $kayitlar_raw = $row->kayit ?? '[]';
                    
                    Log::debug('Alanadi row işleniyor', [
                        'row_index' => $row_index,
                        'uzanti_raw_length' => strlen($uzantilar_raw),
                        'kayit_raw_length' => strlen($kayitlar_raw),
                        'uzanti_raw_preview' => substr($uzantilar_raw, 0, 100),
                        'kayit_raw_preview' => substr($kayitlar_raw, 0, 100)
                    ]);
                    
                    // JSON decode et
                    $uzantilar = json_decode($uzantilar_raw, true);
                    $kayitlar = json_decode($kayitlar_raw, true);
                    
                    // JSON decode hatası kontrolü
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('JSON decode hatası', [
                            'error' => json_last_error_msg(),
                            'uzanti_raw' => substr($uzantilar_raw, 0, 200),
                            'kayit_raw' => substr($kayitlar_raw, 0, 200)
                        ]);
                    }
                    
                    // Eğer decode başarısız olduysa, string olarak dene
                    if (!is_array($uzantilar)) {
                        $uzantilar = json_decode($uzantilar_raw, true) ?: [];
                    }
                    if (!is_array($kayitlar)) {
                        $kayitlar = json_decode($kayitlar_raw, true) ?: [];
                    }
                    
                    Log::info('Alanadi decode sonucu', [
                        'row_index' => $row_index,
                        'uzantilar_count' => is_array($uzantilar) ? count($uzantilar) : 0,
                        'kayitlar_count' => is_array($kayitlar) ? count($kayitlar) : 0,
                        'first_uzanti' => is_array($uzantilar) && isset($uzantilar[0]) ? $uzantilar[0] : 'N/A',
                        'first_kayit' => is_array($kayitlar) && isset($kayitlar[0]) ? $kayitlar[0] : 'N/A',
                        'uzantilar' => is_array($uzantilar) ? array_slice($uzantilar, 0, 5) : []
                    ]);
                    
                    // Uzantıyı array içinde bul
                    if (is_array($uzantilar) && is_array($kayitlar) && count($uzantilar) > 0) {
                        foreach ($uzantilar as $index => $uzanti) {
                            // Uzantıyı normalize et (hem .com hem com formatını kabul et)
                            $uzanti_normalized = trim($uzanti, " \t\n\r\0\x0B\"'");
                            if (substr($uzanti_normalized, 0, 1) !== '.') {
                                $uzanti_normalized = '.' . $uzanti_normalized;
                            }
                            
                            $extension_normalized = trim($extension, " \t\n\r\0\x0B");
                            if (substr($extension_normalized, 0, 1) !== '.') {
                                $extension_normalized = '.' . $extension_normalized;
                            }
                            
                            $match = ($uzanti_normalized === $extension_normalized);
                            
                            Log::debug('Uzanti karşılaştırma', [
                                'index' => $index,
                                'uzanti_original' => $uzanti,
                                'uzanti_normalized' => $uzanti_normalized,
                                'extension_original' => $extension,
                                'extension_normalized' => $extension_normalized,
                                'match' => $match
                            ]);
                            
                            // Karşılaştır
                            if ($match) {
                                // Aynı index'teki fiyatı al
                                if (isset($kayitlar[$index])) {
                                    $fiyat = (float) $kayitlar[$index];
                                    if ($fiyat > 0) {
                                        Log::info('Domain fiyat bulundu!', [
                                            'domain' => $domain,
                                            'extension' => $extension,
                                            'uzanti' => $uzanti_normalized,
                                            'index' => $index,
                                            'fiyat' => $fiyat
                                        ]);
                                        return $fiyat;
                                    } else {
                                        Log::warning('Fiyat 0 veya geçersiz', [
                                            'index' => $index,
                                            'kayit_value' => $kayitlar[$index]
                                        ]);
                                    }
                                } else {
                                    Log::warning('Kayit index bulunamadı', [
                                        'index' => $index,
                                        'kayitlar_count' => count($kayitlar)
                                    ]);
                                }
                            }
                        }
                    } else {
                        Log::warning('Alanadi array boş veya geçersiz', [
                            'uzantilar_is_array' => is_array($uzantilar),
                            'kayitlar_is_array' => is_array($kayitlar)
                        ]);
                    }
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            Log::error('Domain fiyat çekme hatası: ' . $e->getMessage(), [
                'domain' => $domain,
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }
    
    /**
     * Ürün açıklaması oluştur
     */
    private function getUrunAciklama($item)
    {
        // Önce aciklama kolonu varsa onu kullan
        if (isset($item->aciklama) && $item->aciklama && $item->aciklama !== 'Ürün') {
            return $item->aciklama;
        }
        
        // Yeni format kontrolü
        if (isset($item->urun_tipi)) {
            switch($item->urun_tipi) {
                case 'domain':
                    return 'Domain Kayıt (' . ($item->miktar ?? $item->sure ?? 1) . ' yıl)';
                case 'hosting':
                    return 'Web Hosting Paketi';
                case 'web-paket':
                    return 'Web Paketi';
                default:
                    return 'Ürün';
            }
        }
        
        // Eski format kontrolü
        $tipi = $item->tipi ?? null;
        $urun_tipi = $item->urun_tipi ?? null;
        
        // Önce urun_tipi kontrolü yap
        if ($urun_tipi) {
            switch($urun_tipi) {
                case 'domain':
                    return 'Domain Kayıt (' . ($item->miktar ?? $item->sure ?? 1) . ' yıl)';
                case 'hosting':
                    return 'Web Hosting Paketi';
                case 'web-paket':
                    return 'Web Paketi';
                default:
                    return 'Ürün';
            }
        }
        
        // Tipi kontrol et - null ise ürün adına bak
        if ($tipi === null) {
            $urun_adi = $item->name ?? $item->urun_adi ?? $item->adi ?? '';
            // Eğer domain formatı ise domain olarak kabul et
            if ($urun_adi && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $urun_adi)) {
                return 'Domain Kayıt (' . ($item->miktar ?? $item->sure ?? 1) . ' yıl)';
            }
            // Aksi halde web paketi olabilir
            return 'Web Paketi';
        }
        
        switch($tipi) {
            case 0: // Domain
                return 'Domain Kayıt (' . ($item->sure ?? $item->miktar ?? 1) . ' yıl)';
            case 1: // Hosting
                return 'Web Hosting Paketi';
            case 2: // Web Paketi
                return 'Web Paketi';
            default:
                return 'Ürün';
        }
    }
    
    public function ekle(Request $request)
    {
        $validated = $request->validate([
            'urun_id' => 'required|integer',
            'urun_tipi' => 'required|string',
            'urun_adi' => 'required|string',
            'fiyat' => 'required|numeric',
            'miktar' => 'nullable|integer|min:1',
            'aciklama' => 'nullable|string',
        ]);

        $userId = Auth::guard('uye')->id();

        // Sepette aynı ürün var mı kontrol et
        $mevcutSepet = DB::table('sepet')
            ->where('user_id', $userId)
            ->where('urun_id', $validated['urun_id'])
            ->where('urun_tipi', $validated['urun_tipi'])
            ->first();

        if ($mevcutSepet) {
            // Varsa miktarı artır
            DB::table('sepet')->where('id', $mevcutSepet->id)->update([
                'miktar' => ($mevcutSepet->miktar ?? 1) + ($validated['miktar'] ?? 1),
            ]);
        } else {
            // ========== Schema-aware INSERT (29 Mayıs 2026 fix) ==========
            // sepet tablosu kolonları: id, urun_id, urun_tipi, user_id, who, name, fiyat, miktar, tutar
            // urun_adi kolonu OLMAYABİLİR — Schema kontrolüyle güvenli yazım
            $kolonlar = Schema::getColumnListing('sepet');
            $insertData = [];

            if (in_array('user_id', $kolonlar, true)) {
                $insertData['user_id'] = $userId;
            } elseif (in_array('uyeid', $kolonlar, true)) {
                $insertData['uyeid'] = $userId;
            }

            if (in_array('who', $kolonlar, true)) {
                $insertData['who'] = $userId;
            }

            if (in_array('urun_id', $kolonlar, true)) {
                $insertData['urun_id'] = $validated['urun_id'];
            }

            if (in_array('urun_tipi', $kolonlar, true)) {
                $insertData['urun_tipi'] = $validated['urun_tipi'];
            }

            // Ürün adı - birden çok kolon olabilir
            $urunAdi = $validated['urun_adi'];
            if (in_array('name', $kolonlar, true)) {
                $insertData['name'] = $urunAdi;
            }
            if (in_array('urun_adi', $kolonlar, true)) {
                $insertData['urun_adi'] = $urunAdi;
            }
            if (in_array('adi', $kolonlar, true)) {
                $insertData['adi'] = $urunAdi;
            }

            // Fiyat
            if (in_array('fiyat', $kolonlar, true)) {
                $insertData['fiyat'] = $validated['fiyat'];
            }
            if (in_array('tutar', $kolonlar, true)) {
                $insertData['tutar'] = $validated['fiyat'];
            }
            if (in_array('price', $kolonlar, true)) {
                $insertData['price'] = $validated['fiyat'];
            }

            // Miktar
            if (in_array('miktar', $kolonlar, true)) {
                $insertData['miktar'] = $validated['miktar'] ?? 1;
            }

            // Açıklama
            if (in_array('aciklama', $kolonlar, true) && !empty($validated['aciklama'])) {
                $insertData['aciklama'] = $validated['aciklama'];
            }

            // Timestamps
            if (in_array('created_at', $kolonlar, true)) {
                $insertData['created_at'] = now();
            }
            if (in_array('updated_at', $kolonlar, true)) {
                $insertData['updated_at'] = now();
            }
            if (in_array('tarih', $kolonlar, true)) {
                $insertData['tarih'] = now();
            }

            if (count($insertData) > 0) {
                DB::table('sepet')->insert($insertData);
            }
        }

        return redirect()->to(localized_route('sepet'))->with('success', 'Ürün sepete eklendi.');
    }
    
    public function sil(Request $request, $id)
    {
        $userId = Auth::guard('uye')->id();

        \Log::info('SEPET SIL request', [
            'id'      => $id,
            'user_id' => $userId,
            'method'  => $request->method(),
            'referer' => $request->headers->get('referer'),
            'ip'      => $request->ip(),
        ]);

        if (!$userId) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Oturum bulunamadı.'], 401);
            }
            return redirect()->to(localized_route('uye-giris'))->with('error', 'Önce giriş yapın.');
        }

        // Sepet kaydını bul (user_id veya uyeid kolonu hangisi varsa) — guncelle() ile birebir pattern
        $query = DB::table('sepet')->where('id', $id);
        if (Schema::hasColumn('sepet', 'user_id')) {
            $query->where('user_id', $userId);
        } elseif (Schema::hasColumn('sepet', 'uyeid')) {
            $query->where('uyeid', $userId);
        }
        $sepetItem = $query->first();

        if (!$sepetItem) {
            \Log::warning('SEPET SIL: kayıt bulunamadı', ['id' => $id, 'user_id' => $userId]);
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Ürün bulunamadı veya size ait değil.'], 404);
            }
            return redirect()->to(localized_route('sepet'))->with('error', 'Ürün bulunamadı veya size ait değil.');
        }

        // Sil
        $silindiSayisi = DB::table('sepet')->where('id', $id)->delete();

        \Log::info('SEPET SIL sonuç', ['id' => $id, 'silindi' => $silindiSayisi]);

        if ($silindiSayisi === 0) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Ürün silinemedi.'], 500);
            }
            return redirect()->to(localized_route('sepet'))->with('error', 'Ürün silinemedi, lütfen tekrar deneyin.');
        }

        // AJAX yanıtı
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Ürün sepetten çıkarıldı.',
            ]);
        }

        return redirect()->to(localized_route('sepet'))->with('success', 'Ürün sepetten çıkarıldı.');
    }
    
    public function guncelle(Request $request, $id)
    {
        $validated = $request->validate([
            'miktar' => 'required|integer|min:1',
        ]);
        $yeniMiktar = (int) $validated['miktar'];

        $userId = Auth::guard('uye')->id();

        // Sepet kaydını bul (user_id veya uyeid hangisi varsa)
        $query = DB::table('sepet')->where('id', $id);
        if (Schema::hasColumn('sepet', 'user_id')) {
            $query->where('user_id', $userId);
        } elseif (Schema::hasColumn('sepet', 'uyeid')) {
            $query->where('uyeid', $userId);
        }
        $sepetItem = $query->first();

        if (!$sepetItem) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Ürün bulunamadı.'], 404);
            }
            return redirect()->to(localized_route('sepet'))->with('error', 'Ürün bulunamadı.');
        }

        // Miktar kolonu adı: önce 'miktar', yoksa 'sure', yoksa 'quantity'
        $miktarKolon = null;
        if (Schema::hasColumn('sepet', 'miktar')) {
            $miktarKolon = 'miktar';
        } elseif (Schema::hasColumn('sepet', 'sure')) {
            $miktarKolon = 'sure';
        } elseif (Schema::hasColumn('sepet', 'quantity')) {
            $miktarKolon = 'quantity';
        }

        if ($miktarKolon) {
            $updateData = [$miktarKolon => $yeniMiktar];
            if (Schema::hasColumn('sepet', 'updated_at')) {
                $updateData['updated_at'] = now();
            }
            DB::table('sepet')->where('id', $id)->update($updateData);
        }

        // AJAX isteğiyse JSON dön (sayfa yenilenmeden güncellensin)
        $isAjax = $request->ajax() || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($isAjax) {
            // Bu ürünün birim fiyatı
            $birimFiyat = (float) ($sepetItem->fiyat ?? $sepetItem->tutar ?? $sepetItem->price ?? 0);
            $satirToplam = $birimFiyat * $yeniMiktar;

            // Sepetin genel ara toplamı (tüm satırlar: fiyat * miktar)
            $kullaniciKolon = Schema::hasColumn('sepet', 'user_id') ? 'user_id'
                : (Schema::hasColumn('sepet', 'uyeid') ? 'uyeid' : null);
            $araToplam = 0.0;
            if ($kullaniciKolon) {
                $tumU = DB::table('sepet')->where($kullaniciKolon, $userId)->get();
                foreach ($tumU as $u) {
                    $uf = (float) ($u->fiyat ?? $u->tutar ?? $u->price ?? 0);
                    $um = (int) ($u->{$miktarKolon} ?? 1);
                    if ($um < 1) $um = 1;
                    $araToplam += $uf * $um;
                }
            }

            $currency = session('currency', 'TRY');

            // Kupon ve bayi indirimlerini güncel ara toplama göre yeniden hesapla
            $kuponIndirim = 0;
            $kuponKod = session('kupon_kod', '');
            if ($kuponKod) {
                try {
                    $ks = \App\Services\KuponService::validateAndApply($kuponKod, $araToplam);
                    if (!empty($ks['success'])) { $kuponIndirim = $ks['indirim']; }
                } catch (\Exception $e) {}
            }
            $bayiIndirim = 0;
            try {
                $bayiBilgi = \App\Services\BayiKodService::getSessionBayi();
                if ($bayiBilgi && isset($bayiBilgi['bayi_kodu'])) {
                    $bs = \App\Services\BayiKodService::validateAndApply($bayiBilgi['bayi_kodu'], $araToplam);
                    if (!empty($bs['success'])) { $bayiIndirim = $bs['indirim']; }
                }
            } catch (\Exception $e) {}

            $genelToplam = max(0, $araToplam - $kuponIndirim - $bayiIndirim);

            return response()->json([
                'success'           => true,
                'miktar'            => $yeniMiktar,
                'satir_toplam'      => $satirToplam,
                'satir_toplam_str'  => \App\Helpers\CurrencyHelper::format($satirToplam, $currency),
                'ara_toplam'        => $araToplam,
                'ara_toplam_str'    => \App\Helpers\CurrencyHelper::format($araToplam, $currency),
                'genel_toplam'      => $genelToplam,
                'genel_toplam_str'  => \App\Helpers\CurrencyHelper::format($genelToplam, $currency),
            ]);
        }

        return redirect()->to(localized_route('sepet'))->with('success', 'Sepet güncellendi.');
    }
    
    public function temizle()
    {
        Sepet::where('user_id', Auth::guard('uye')->id())->delete();
        
        return redirect()->to(localized_route('sepet'))->with('success', 'Sepet temizlendi.');
    }
    
    public function onayla(Request $request)
    {
        $userId = Auth::guard('uye')->id();

        // Sepet verilerini çek
        $sepet_raw = null;
        if (Schema::hasColumn('sepet', 'user_id')) {
            $sepet_raw = DB::table('sepet')->where('user_id', $userId)->get();
        } elseif (Schema::hasColumn('sepet', 'uyeid')) {
            $sepet_raw = DB::table('sepet')->where('uyeid', $userId)->get();
        } else {
            $sepet_raw = collect([]);
        }

        if ($sepet_raw->isEmpty()) {
            return redirect()->to(localized_route('sepet'))->with('error', 'Sepetiniz boş.');
        }

        // ════════════════════════════════════════════════════════════
        // PROFİL/FATURA BİLGİSİ KONTROLÜ (madde 4)
        // Bireysel (utipi=0): TC + Ad + Soyad + Adres zorunlu
        // Kurumsal (utipi=1): Fatura Ünvanı + Fatura TC/Vergi No + Fatura Adresi zorunlu
        // Eksikse ödeme TAMAMEN engellenir, bilgilerim sayfasına yönlendirilir.
        // ════════════════════════════════════════════════════════════
        $uyeProfil = DB::table('uyeler')->where('id', $userId)->first();
        if ($uyeProfil) {
            $eksikler = [];
            $bos = function ($v) { return $v === null || trim((string) $v) === ''; };

            if ((int) ($uyeProfil->utipi ?? 0) === 1) {
                // KURUMSAL
                if ($bos($uyeProfil->fatura_unvan ?? null)) $eksikler[] = 'Fatura Ünvanı';
                if ($bos($uyeProfil->fatura_tc ?? null))    $eksikler[] = 'Vergi No / TC';
                if ($bos($uyeProfil->fatura_adres ?? null)) $eksikler[] = 'Fatura Adresi';
            } else {
                // BİREYSEL
                if ($bos($uyeProfil->tc ?? null))    $eksikler[] = 'TC Kimlik No';
                if ($bos($uyeProfil->ad ?? null))    $eksikler[] = 'Ad';
                if ($bos($uyeProfil->soyad ?? null)) $eksikler[] = 'Soyad';
                if ($bos($uyeProfil->adres ?? null)) $eksikler[] = 'Adres';
            }

            if (!empty($eksikler)) {
                // Flash session redirect zincirinde kaybolabiliyor → query parametresi ile taşı (garantili)
                $hedef = localized_route('bilgilerim');
                $ayrac = (strpos($hedef, '?') !== false) ? '&' : '?';
                $hedef .= $ayrac . 'profil_eksik=1&eksik=' . urlencode(implode(', ', $eksikler));
                return redirect()->to($hedef);
            }
        }

        // Sepet verilerini formatla (görünüm için)
        $sepet = $sepet_raw->map(function($item) {
            $fiyat_degeri = 0;
            if (isset($item->fiyat) && $item->fiyat > 0) {
                $fiyat_degeri = (float) $item->fiyat;
            } elseif (isset($item->tutar) && $item->tutar > 0) {
                $fiyat_degeri = (float) $item->tutar;
            } elseif (isset($item->price) && $item->price > 0) {
                $fiyat_degeri = (float) $item->price;
            }

            $urun_adi_final = $item->name ?? $item->urun_adi ?? $item->adi ?? 'Ürün';

            return (object) [
                'id' => $item->id,
                'urun_adi' => $urun_adi_final,
                'fiyat' => $fiyat_degeri,
                'miktar' => $item->miktar ?? $item->sure ?? 1,
                'aciklama' => $item->aciklama ?? $this->getUrunAciklama($item),
            ];
        });

        $toplam = $sepet->sum(function($item) {
            return ($item->fiyat ?? 0) * ($item->miktar ?? 1);
        });

        // Kupon ve bayi indirimlerini hesaba kat (bakiyeOdeme ile aynı mantık)
        $kupon_kod = session('kupon_kod', '');
        $kupon_indirim = 0;
        if ($kupon_kod) {
            $kupon_sonuc = \App\Services\KuponService::validateAndApply($kupon_kod, $toplam);
            if (!empty($kupon_sonuc['success'])) {
                $kupon_indirim = $kupon_sonuc['indirim'];
            } else {
                session()->forget('kupon_kod');
                $kupon_kod = '';
            }
        }

        $bayi_bilgi = \App\Services\BayiKodService::getSessionBayi();
        $bayi_indirim = 0;
        if ($bayi_bilgi && isset($bayi_bilgi['bayi_kodu'])) {
            $bayi_sonuc = \App\Services\BayiKodService::validateAndApply($bayi_bilgi['bayi_kodu'], $toplam);
            if (!empty($bayi_sonuc['success'])) {
                $bayi_indirim = $bayi_sonuc['indirim'];
            }
        }

        // KDV %20 (dışarıdan): indirimli ara toplam + KDV = genel toplam (coin bundan düşülür)
        $ara_toplam = max(0, $toplam - $kupon_indirim - $bayi_indirim);
        $kdv_orani = 20;
        $kdv = round($ara_toplam * $kdv_orani / 100, 2);
        $genel_toplam = round($ara_toplam + $kdv, 2);

        // ════════════════════════════════════════════════════════════
        // DN BANK COIN (kısmi/tam): müşteri coin kullandıysa genel_toplam'dan düş
        // ════════════════════════════════════════════════════════════
        $dnbankKullanilan = 0.0;
        $dnbankCoinIstek = (float) $request->input('dnbank_coin', 0);
        if ($dnbankCoinIstek > 0) {
            $uyeCoin = 0.0;
            try {
                if (class_exists('\App\Services\DnBankService')) {
                    $uyeCoin = (float) \App\Services\DnBankService::bakiye((int) $userId);
                } elseif (Schema::hasColumn('uyeler', 'dnbank_bakiye')) {
                    $uyeCoin = (float) (DB::table('uyeler')->where('id', $userId)->value('dnbank_bakiye') ?? 0);
                }
            } catch (\Throwable $e) {}

            // Coin'i hem bakiyeyle hem sepet toplamıyla sınırla
            $dnbankKullanilan = min($dnbankCoinIstek, $uyeCoin, $genel_toplam);
            $dnbankKullanilan = round(max(0, $dnbankKullanilan), 2);

            if ($dnbankKullanilan > 0) {
                // Coin'i düş + deftere işle
                try {
                    if (class_exists('\App\Services\DnBankService')) {
                        \App\Services\DnBankService::bakiyeHarca(
                            (int) $userId,
                            $dnbankKullanilan,
                            'Sepet ödemesinde coin kullanıldı',
                            null
                        );
                    } elseif (Schema::hasColumn('uyeler', 'dnbank_bakiye')) {
                        $yeniCoin = round($uyeCoin - $dnbankKullanilan, 2);
                        DB::table('uyeler')->where('id', $userId)->update(['dnbank_bakiye' => $yeniCoin]);
                    }
                    // Kalan tutarı düş
                    $genel_toplam = round($genel_toplam - $dnbankKullanilan, 2);
                    session(['son_dnbank_kullanilan' => $dnbankKullanilan]);
                } catch (\Throwable $e) {
                    \Log::warning('Sepet coin harcanamadi', ['uye' => $userId, 'err' => $e->getMessage()]);
                    $dnbankKullanilan = 0.0;
                }
            }
        }

        // ════════════════════════════════════════════════════════════
        // ÜCRETSİZ SİPARİŞ (genel_toplam = 0) → PayTR'ye gitmeden direkt tamamla
        // (Coin tüm sepeti karşıladıysa da buraya düşer)
        // ════════════════════════════════════════════════════════════
        if ($genel_toplam <= 0) {
            return $this->tamamlaUcretsizSiparis($userId, $sepet_raw, $kupon_kod, $kupon_indirim, $bayi_bilgi, $bayi_indirim, $toplam);
        }

        // PayTR token'ı al ve iframe URL'ini hazırla
        $paytr_iframe_url = null;
        try {
            // Kullanıcı bilgilerini al
            $uye = DB::table('uyeler')->where('id', $userId)->first();

            if ($uye && $genel_toplam > 0) {
                // PayTR ayarlarını al — önce yeni şema (ayarlar), yoksa eski (paytr)
                $ayar = DB::table('ayarlar')->first();
                $merchant_id   = $ayar->paytr_merchant_id   ?? null;
                $merchant_key  = $ayar->paytr_merchant_key  ?? null;
                $merchant_salt = $ayar->paytr_merchant_salt ?? null;
                $test_mode     = isset($ayar->paytr_test_mode) ? (int) $ayar->paytr_test_mode : null;
                $paytr_aktif   = isset($ayar->paytr_aktif)     ? (int) $ayar->paytr_aktif     : 1;

                if (!$merchant_id || !$merchant_key || !$merchant_salt) {
                    try {
                        $paytr = DB::table('paytr')->first();
                        if ($paytr) {
                            $merchant_id   = $merchant_id   ?: ($paytr->magaza_no      ?? '');
                            $merchant_key  = $merchant_key  ?: ($paytr->magaza_parola  ?? '');
                            $merchant_salt = $merchant_salt ?: ($paytr->magaza_anahtar ?? '');
                            if ($test_mode === null) $test_mode = $paytr->test_modu ?? 1;
                        }
                    } catch (\Throwable $e) {}
                }
                $test_mode = (int) ($test_mode ?? 1);

                if (!$paytr_aktif || !$merchant_id || !$merchant_key || !$merchant_salt) {
                    Log::warning('PayTR ayarları bulunamadı veya pasif', [
                        'aktif' => $paytr_aktif, 'has_id' => (bool)$merchant_id,
                    ]);
                } else {
                    // Sepet bilgilerini hazırla
                    $user_basket_items = [];
                    foreach ($sepet as $item) {
                        $user_basket_items[] = [
                            $item->urun_adi,
                            number_format($item->fiyat, 2, '.', ''),
                            $item->miktar ?? 1
                        ];
                    }
                    $user_basket = base64_encode(json_encode($user_basket_items));

                    $email = $uye->email ?? '';
                    $payment_amount = intval(round($genel_toplam * 100)); // Kuruş cinsinden
                    $user_name = ($uye->ad ?? '') . ' ' . ($uye->soyad ?? '');
                    $user_address = $uye->adres ?? 'Adres Bilgisi Yok';
                    $user_phone = $uye->telefon ?? '0000000000';
                    $user_ip = request()->ip();

                    // ════════════════════════════════════════════════════════
                    // 1) BEKLEMEDE FATURA + SATILANLAR OLUŞTUR (ödeme öncesi)
                    //    merchant_oid = 'FAT' . fatura_id  → callback bununla bulur
                    // ════════════════════════════════════════════════════════
                    $aciklamaOdeme = 'Kredi Kartı (Online Ödeme)';
                    if ($kupon_kod && $kupon_indirim > 0) {
                        $aciklamaOdeme .= ' - Kupon: ' . $kupon_kod . ' (-' . number_format($kupon_indirim, 2) . ' TL)';
                    }

                    $fatura_data = [
                        'uyeid'         => $userId,
                        'tutar'         => $genel_toplam,
                        'toplam'        => $genel_toplam,
                        'durum'         => 0, // 0 = Ödenmedi / Beklemede
                        'fatura_no'     => 'FAT-' . time() . $userId,
                        'aciklama'      => 'Sepet Ödemesi (Kredi Kartı)',
                        'tarih'         => now(),
                        'tip'           => 'sepet_odeme',
                        'odeme_yontemi' => 'Kredi Kartı (Online Ödeme)',
                    ];
                    if ($bayi_bilgi && isset($bayi_bilgi['bayi_kodu']) && Schema::hasColumn('faturalar', 'bayi_kodu')) {
                        $fatura_data['bayi_kodu'] = $bayi_bilgi['bayi_kodu'];
                    }
                    // Sadece var olan kolonları yaz (Schema güvenliği)
                    foreach (array_keys($fatura_data) as $kol) {
                        if (!Schema::hasColumn('faturalar', $kol)) {
                            unset($fatura_data[$kol]);
                        }
                    }
                    $fatura_id = DB::table('faturalar')->insertGetId($fatura_data);

                    // merchant_oid: SADECE harf+rakam olmalı (PayTR kuralı). Fatura ID'ye bağla.
                    $merchant_oid = 'FAT' . $fatura_id;
                    // spno (eski sistem uyumu): fatura ile aynı referans
                    $spno = '#' . $merchant_oid;

                    // faturalar.spno alanı varsa eşle (callback fallback için)
                    if (Schema::hasColumn('faturalar', 'spno')) {
                        DB::table('faturalar')->where('id', $fatura_id)->update(['spno' => $spno]);
                    }

                    // Her sepet item için BEKLEMEDE satilanlar kaydı oluştur
                    if (DB::getSchemaBuilder()->hasTable('satilanlar')) {
                        foreach ($sepet_raw as $item) {
                            try {
                                $this->satilanKaydiOlustur($item, $userId, $fatura_id, $spno, $aciklamaOdeme, $user_ip, 0, 0);
                            } catch (\Throwable $e) {
                                Log::warning('Onayla: satilanlar kaydı eklenemedi', ['error' => $e->getMessage()]);
                            }
                        }
                    }

                    $no_installment = 0;
                    $max_installment = 0;
                    $currency = 'TL';
                    $timeout_limit = 30;
                    $debug_on = $test_mode ? 1 : 0;
                    $test_mode_val = $test_mode ? 1 : 0;

                    // Hash oluşturma
                    $hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket .
                                $no_installment . $max_installment . $currency . $test_mode_val;
                    $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

                    // PayTR API'ye gönderilecek veriler
                    $post_data = [
                        'merchant_id' => $merchant_id,
                        'user_ip' => $user_ip,
                        'merchant_oid' => $merchant_oid,
                        'email' => $email,
                        'payment_amount' => $payment_amount,
                        'paytr_token' => $paytr_token,
                        'user_basket' => $user_basket,
                        'debug_on' => $debug_on,
                        'no_installment' => $no_installment,
                        'max_installment' => $max_installment,
                        'user_name' => $user_name,
                        'user_address' => $user_address,
                        'user_phone' => $user_phone,
                        'merchant_ok_url' => route('siparis.sonuc', ['status' => 'success']),
                        'merchant_fail_url' => route('siparis.sonuc', ['status' => 'cancel']),
                        // Bildirim (callback) URL — PayTR ödeme sonucunu BU adrese POST eder.
                        // Hash hesabına dahil DEĞİLDİR, eklemek hash'i bozmaz.
                        'merchant_notify_url' => route('payment.paytr.callback'),
                        'timeout_limit' => $timeout_limit,
                        'currency' => $currency,
                        'test_mode' => $test_mode_val,
                    ];

                    // cURL ile PayTR API'ye istek
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

                    $result = curl_exec($ch);
                    $curl_errno = curl_errno($ch);

                    if ($curl_errno) {
                        $curl_error = curl_error($ch);
                        Log::error('PayTR cURL hatası', [
                            'error' => $curl_error,
                            'curl_errno' => $curl_errno
                        ]);
                        // Token alınamadı → beklemede kayıtları iptal et (kullanıcı tekrar denesin)
                        $this->beklemedeSiparisiTemizle($fatura_id, $spno);
                    } else {
                        $result = json_decode($result, true);

                        if (isset($result['status']) && $result['status'] == 'success') {
                            $paytr_iframe_url = 'https://www.paytr.com/odeme/guvenli/' . $result['token'];

                            Log::info('PayTR token başarıyla alındı', [
                                'merchant_oid' => $merchant_oid,
                                'fatura_id' => $fatura_id,
                                'notify_url' => route('payment.paytr.callback'),
                                'test_mode' => $test_mode_val,
                            ]);
                        } else {
                            Log::error('PayTR token alma başarısız', [
                                'result' => $result,
                                'reason' => $result['reason'] ?? 'Bilinmeyen hata',
                                'merchant_id' => $merchant_id,
                                'payment_amount' => $payment_amount
                            ]);
                            // Token alınamadı → beklemede kayıtları iptal et
                            $this->beklemedeSiparisiTemizle($fatura_id, $spno);
                        }
                    }

                    curl_close($ch);
                }
            }
        } catch (\Exception $e) {
            Log::error('PayTR token alma hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        if (!$paytr_iframe_url) {
            return redirect()->to(localized_route('sepet'))
                ->with('error', 'Ödeme başlatılamadı. Lütfen tekrar deneyin veya bakiye ile ödemeyi kullanın.');
        }

        $genel_toplam_view = $genel_toplam;
        return view('tema.odeme', [
            'sepet' => $sepet,
            'toplam' => $toplam,
            'genel_toplam' => $genel_toplam_view,
            'paytr_iframe_url' => $paytr_iframe_url,
        ]);
    }

    /**
     * Beklemede oluşturulan fatura + satilanlar kayıtlarını temizler
     * (PayTR token alınamazsa çağrılır — yarım kayıt kalmasın).
     */
    private function beklemedeSiparisiTemizle($fatura_id, $spno)
    {
        try {
            if ($fatura_id && DB::getSchemaBuilder()->hasTable('faturalar')) {
                DB::table('faturalar')->where('id', $fatura_id)->where('durum', 0)->delete();
            }
            if ($spno && DB::getSchemaBuilder()->hasTable('satilanlar')) {
                DB::table('satilanlar')->where('spno', $spno)->where('paytronay', 0)->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('Beklemede sipariş temizlenemedi', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Tek bir sepet item'ı için satilanlar kaydı oluşturur (akıllı tipi tespiti ile).
     * $paytronay / $durum: 0 = beklemede, 1 = onaylı. Onaylandığında domain kayıtları da işlenir.
     * @return int|null Oluşturulan satilanlar kaydının ID'si
     */
    private function satilanKaydiOlustur($item, $userId, $fatura_id, $spno, $aciklamaOdeme, $user_ip, $paytronay = 0, $durum = 0)
    {
        $urunId      = $item->urun_id ?? 0;
        $urunTipiRaw = $item->urun_tipi ?? $item->who ?? $item->name ?? '';
        $urunTipiRaw = is_string($urunTipiRaw) ? strtolower($urunTipiRaw) : (string) $urunTipiRaw;
        $urunAdi     = $item->urun_adi ?? $item->name ?? '';
        $itemDomain  = $item->domain ?? null;

        // ========== AKILLI TİPİ TESPİTİ ==========
        // tipi: 0=domain, 1=hosting, 2=web paket
        $tipi = null;
        if (in_array($urunTipiRaw, ['domain', '0'], true)) {
            $tipi = 0;
        } elseif (in_array($urunTipiRaw, ['hosting', 'hosting_yenileme', 'hosting-paket', 'web-hosting', '1'], true)) {
            $tipi = 1;
        } elseif (in_array($urunTipiRaw, ['web-paket', 'paket', 'yazilim', 'web_paket', '2'], true)) {
            $tipi = 2;
        }
        if ($tipi === null && !empty($itemDomain)) {
            $tipi = 0;
        }
        if ($tipi === null && !empty($urunAdi)) {
            if (preg_match('/\.(com|net|org|info|biz|tr|com\.tr|net\.tr|org\.tr|web|io|co|app|dev|tech|store|online|site)$/i', $urunAdi)) {
                $tipi = 0;
                $itemDomain = $urunAdi;
            }
        }
        if ($tipi === null && $urunId > 0) {
            try {
                if (Schema::hasTable('hosting_paketler')) {
                    $hp = DB::table('hosting_paketler')->where('id', $urunId)->first();
                    if ($hp) { $tipi = 1; }
                }
                if ($tipi === null && Schema::hasTable('yazilimlar')) {
                    $yz = DB::table('yazilimlar')->where('id', $urunId)->first();
                    if ($yz) { $tipi = 2; }
                }
                if ($tipi === null && Schema::hasTable('domain_orders')) {
                    $do = DB::table('domain_orders')->where('id', $urunId)->first();
                    if ($do) {
                        $tipi = 0;
                        if (empty($itemDomain) && !empty($do->domain)) {
                            $itemDomain = $do->domain;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Tipi tespit hatasi', ['urun_id' => $urunId, 'err' => $e->getMessage()]);
            }
        }
        if ($tipi === null) {
            $tipi = 2;
        }

        // Paket bilgilerini çek
        $paketBaslik = $item->urun_adi ?? $item->name ?? null;
        $domainAdi   = $itemDomain ?? ($item->domain ?? null);

        if ($tipi === 2 && $urunId) {
            $yazilim = DB::table('yazilimlar')->where('id', $urunId)->first();
            if ($yazilim) {
                $paketBaslik = $yazilim->adi ?? $paketBaslik ?? 'Web Paket';
            }
        } elseif ($tipi === 1 && $urunId) {
            $hostingPaket = DB::table('hosting_paketler')->where('id', $urunId)->first();
            if ($hostingPaket) {
                $paketBaslik = $hostingPaket->adi ?? $paketBaslik ?? 'Hosting';
            }
        }
        if ($tipi === 0) {
            if (empty($domainAdi)) {
                $domainAdi = $paketBaslik ?: ('Ürün #' . $urunId);
            }
            $paketBaslik = $domainAdi;
        }
        $paketBaslik = $paketBaslik ?: 'Ürün #' . $urunId;

        $itemFiyat = (float) ($item->fiyat ?? $item->tutar ?? 0);
        $miktar    = (int) ($item->miktar ?? 1);
        $sureYil   = max(1, (int) ($item->sure ?? $miktar));
        $baslangic = now();
        $bitis     = (clone $baslangic)->addYears($sureYil);

        // ========== satilanlar tablosu (ana kayıt) ==========
        $insertData = [
            'uyeid'           => $userId,
            'tutar'           => $itemFiyat * $miktar,
            'tipi'            => $tipi,
            'paket'           => $urunId ?: null,
            'tarih'           => now(),
            'baslangic_tarih' => $baslangic,
            'bitis_tarih'     => $bitis,
            'spno'            => $spno,
            'odeme_yontemi'   => $aciklamaOdeme,
            'paytronay'       => $paytronay,
            'durum'           => $durum,
            'ip'              => $user_ip,
        ];
        if (Schema::hasColumn('satilanlar', 'baslangic_tarihi')) {
            $insertData['baslangic_tarihi'] = $baslangic;
        }
        if (Schema::hasColumn('satilanlar', 'bitis_tarihi')) {
            $insertData['bitis_tarihi'] = $bitis;
        }
        if (Schema::hasColumn('satilanlar', 'paket_baslik')) {
            $insertData['paket_baslik'] = $paketBaslik;
        }
        if (Schema::hasColumn('satilanlar', 'paket_adi')) {
            $insertData['paket_adi'] = $paketBaslik;
        }
        if ($tipi === 1 && Schema::hasColumn('satilanlar', 'hosting_baslik')) {
            $insertData['hosting_baslik'] = $paketBaslik;
        }
        if (Schema::hasColumn('satilanlar', 'domain')) {
            $insertData['domain'] = $domainAdi ?? '';
        }
        if (Schema::hasColumn('satilanlar', 'hosting') && $tipi === 1) {
            $insertData['hosting'] = $urunId;
        }
        if (Schema::hasColumn('satilanlar', 'adi')) {
            $insertData['adi'] = $paketBaslik;
        }
        if (Schema::hasColumn('satilanlar', 'fiyat')) {
            $insertData['fiyat'] = $itemFiyat * $miktar;
        }
        if (Schema::hasColumn('satilanlar', 'sure')) {
            $insertData['sure'] = $sureYil;
        }
        if ($fatura_id && Schema::hasColumn('satilanlar', 'fatura_id')) {
            $insertData['fatura_id'] = $fatura_id;
        }

        $satilanId = DB::table('satilanlar')->insertGetId($insertData);

        // Domain kayıtları SADECE onaylı satışta (durum=1) işlenir
        if ($durum === 1 && $tipi === 0 && !empty($domainAdi)) {
            $uye = DB::table('uyeler')->where('id', $userId)->first();
            // 1) alan_adlarim
            try {
                if (Schema::hasTable('alan_adlarim')) {
                    $alanData = [
                        'uyeid'        => $userId,
                        'domain'       => $domainAdi,
                        'kayit_tarihi' => $baslangic,
                        'bitis_tarihi' => $bitis,
                        'durum'        => 1,
                    ];
                    if (Schema::hasColumn('alan_adlarim', 'siparis_id')) {
                        $alanData['siparis_id'] = $satilanId;
                    }
                    if (Schema::hasColumn('alan_adlarim', 'order_id')) {
                        $alanData['order_id'] = $satilanId;
                    }
                    if (Schema::hasColumn('alan_adlarim', 'created_at')) {
                        $alanData['created_at'] = now();
                        $alanData['updated_at'] = now();
                    }
                    DB::table('alan_adlarim')->insert($alanData);
                }
            } catch (\Throwable $e) {
                Log::warning('alan_adlarim insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
            }
            // 2) domain_satislar
            try {
                if (Schema::hasTable('domain_satislar')) {
                    $satisData = [
                        'uyeid'           => $userId,
                        'domain'          => $domainAdi,
                        'tutar'           => $itemFiyat * $miktar,
                        'baslangic_tarih' => $baslangic,
                        'bitis_tarih'     => $bitis,
                        'durum'           => 1,
                    ];
                    if (Schema::hasColumn('domain_satislar', 'musteri_email') && !empty($uye->email)) {
                        $satisData['musteri_email'] = $uye->email;
                    }
                    if (Schema::hasColumn('domain_satislar', 'saglayici')) {
                        $satisData['saglayici'] = 'manuel';
                    }
                    if (Schema::hasColumn('domain_satislar', 'created_at')) {
                        $satisData['created_at'] = now();
                        $satisData['updated_at'] = now();
                    }
                    DB::table('domain_satislar')->insert($satisData);
                }
            } catch (\Throwable $e) {
                Log::warning('domain_satislar insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
            }
            // 3) domain_orders
            try {
                if (Schema::hasTable('domain_orders') && class_exists('App\Models\DomainOrder')) {
                    \App\Models\DomainOrder::create([
                        'user_id'        => $userId,
                        'domain'         => $domainAdi,
                        'price'          => $itemFiyat,
                        'years'          => $sureYil,
                        'status'         => \App\Models\DomainOrder::STATUS_PAID,
                        'payment_method' => 'paytr',
                        'fatura_id'      => $fatura_id ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('domain_orders insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
            }
        }

        return $satilanId;
    }

    /**
     * Ücretsiz sipariş (genel_toplam=0) → PayTR'ye gitmeden direkt tamamlar.
     */
    private function tamamlaUcretsizSiparis($userId, $sepet_raw, $kupon_kod, $kupon_indirim, $bayi_bilgi, $bayi_indirim, $toplam)
    {
        DB::beginTransaction();
        try {
            $fatura_data = [
                'uyeid'         => $userId,
                'tutar'         => 0,
                'toplam'        => 0,
                'durum'         => 1, // Ödendi (ücretsiz)
                'fatura_no'     => 'FAT-' . time() . $userId,
                'aciklama'      => 'Ücretsiz Paket',
                'tarih'         => now(),
                'odenen_tarih'  => now(),
                'tip'           => 'sepet_odeme',
                'odeme_yontemi' => 'ucretsiz',
            ];
            if ($bayi_bilgi && isset($bayi_bilgi['bayi_kodu']) && Schema::hasColumn('faturalar', 'bayi_kodu')) {
                $fatura_data['bayi_kodu'] = $bayi_bilgi['bayi_kodu'];
            }
            foreach (array_keys($fatura_data) as $kol) {
                if (!Schema::hasColumn('faturalar', $kol)) {
                    unset($fatura_data[$kol]);
                }
            }
            $fatura_id = DB::table('faturalar')->insertGetId($fatura_data);
            $spno = '#FAT' . $fatura_id;
            if (Schema::hasColumn('faturalar', 'spno')) {
                DB::table('faturalar')->where('id', $fatura_id)->update(['spno' => $spno]);
            }

            if (DB::getSchemaBuilder()->hasTable('satilanlar')) {
                foreach ($sepet_raw as $item) {
                    $this->satilanKaydiOlustur($item, $userId, $fatura_id, $spno, 'Ücretsiz', request()->ip(), 1, 1);
                }
            }

            if ($kupon_kod) {
                \App\Services\KuponService::incrementUsage($kupon_kod);
                session()->forget('kupon_kod');
            }
            if ($bayi_bilgi && isset($bayi_bilgi['bayi_id'])) {
                \App\Services\BayiKodService::recordCommission(
                    $bayi_bilgi['bayi_id'], $userId, $fatura_id, $toplam,
                    $bayi_bilgi['komisyon_orani'] ?? 10, $bayi_indirim
                );
                \App\Services\BayiKodService::clearSessionBayi();
            }

            // Sepeti temizle
            if (Schema::hasColumn('sepet', 'user_id')) {
                DB::table('sepet')->where('user_id', $userId)->delete();
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                DB::table('sepet')->where('uyeid', $userId)->delete();
            }

            DB::commit();

            // Admine panel bildirimi — ücretsiz sipariş
            try {
                $musteri = DB::table('uyeler')->where('id', $userId)->first();
                $musteriAd = $musteri ? (trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? '')) ?: ($musteri->email ?? 'Müşteri')) : 'Müşteri';
                admin_bildirim_gonder(
                    '🛒 Yeni Sipariş (Ücretsiz Paket)',
                    $musteriAd . ' ücretsiz bir paket aldı.' . (isset($fatura_id) ? ' Fatura #' . $fatura_id : ''),
                    'odeme',
                    'faturalar',
                    isset($fatura_id) ? (int) $fatura_id : null
                );
            } catch (\Throwable $e) {
                Log::warning('Ücretsiz sipariş admin bildirimi: ' . $e->getMessage());
            }

            return redirect()->route('web.paketlerim')->with('success', 'Ücretsiz paketiniz başarıyla hesabınıza eklendi.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Ücretsiz sipariş hatası', ['error' => $e->getMessage()]);
            return redirect()->to(localized_route('sepet'))->with('error', 'İşlem sırasında hata oluştu.');
        }
    }

    public function bakiyeOdeme(Request $request)
    {
        try {
            $userId = Auth::guard('uye')->id();
            $uye = Auth::guard('uye')->user();
            
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
                $fiyat = $item->fiyat ?? $item->tutar ?? $item->price ?? $item->ucret ?? 0;
                $miktar = $item->miktar ?? 1;
                $toplam += (float) $fiyat * (int) $miktar;
            }
            
            // Kupon kontrolü
            $kupon_kod = session('kupon_kod', '');
            $kupon_indirim = 0;
            
            if ($kupon_kod) {
                $kupon_sonuc = \App\Services\KuponService::validateAndApply($kupon_kod, $toplam);
                if ($kupon_sonuc['success']) {
                    $kupon_indirim = $kupon_sonuc['indirim'];
                } else {
                    session()->forget('kupon_kod');
                }
            }
            
            // Bayi kodu kontrolü
            $bayi_bilgi = \App\Services\BayiKodService::getSessionBayi();
            $bayi_indirim = 0;
            
            if ($bayi_bilgi && isset($bayi_bilgi['bayi_kodu'])) {
                $bayi_sonuc = \App\Services\BayiKodService::validateAndApply($bayi_bilgi['bayi_kodu'], $toplam);
                if ($bayi_sonuc['success']) {
                    $bayi_indirim = $bayi_sonuc['indirim'];
                }
            }
            
            $genel_toplam = max(0, $toplam - $kupon_indirim - $bayi_indirim);
            
            // Bakiye kontrolü
            $bakiye = $uye->bakiye ?? 0;
            if ($bakiye < $genel_toplam) {
                return redirect()->to(localized_route('sepet'))->with('error', 'Yetersiz bakiye! Mevcut bakiyeniz: ' . number_format($bakiye, 2, ',', '.') . ' ₺');
            }
            
            DB::beginTransaction();

            try {
                // Bakiye değişikliği sadece tutar > 0 ise
                if ($genel_toplam > 0) {
                    $yeni_bakiye = $bakiye - $genel_toplam;
                    DB::table('uyeler')
                        ->where('id', $userId)
                        ->update(['bakiye' => $yeni_bakiye]);

                    DB::table('bakiye_gecmisi')->insert([
                        'uye_id' => $userId,
                        'tip' => 'harcama',
                        'tutar' => $genel_toplam,
                        'bakiye_once' => $bakiye,
                        'bakiye_sonra' => $yeni_bakiye,
                        'aciklama' => 'Sepet Ödemesi',
                        'tarih' => now()
                    ]);
                }

                // Fatura oluştur
                $fatura_data = [
                    'uyeid' => $userId,
                    'tutar' => $genel_toplam,
                    'toplam' => $genel_toplam,
                    'durum' => 1, // Ödendi
                    'fatura_no' => 'FAT-' . time() . $userId,
                    'aciklama' => $genel_toplam > 0 ? 'Sepet Ödemesi (Bakiye)' : 'Ücretsiz Paket',
                    'tarih' => now(),
                    'odenen_tarih' => now(),
                    'tip' => 'sepet_odeme',
                    'odeme_yontemi' => $genel_toplam > 0 ? 'bakiye' : 'ucretsiz',
                ];
                if ($bayi_bilgi && isset($bayi_bilgi['bayi_kodu'])) {
                    $fatura_data['bayi_kodu'] = $bayi_bilgi['bayi_kodu'];
                }
                $fatura_id = DB::table('faturalar')->insertGetId($fatura_data);

                // SEPET HER ITEM İÇİN ayrı bir satilanlar kaydı oluştur
                if (DB::getSchemaBuilder()->hasTable('satilanlar')) {
                    $aciklamaOdeme = $genel_toplam > 0 ? 'Bakiye' : 'Ücretsiz';
                    if ($kupon_kod && $kupon_indirim > 0) {
                        $aciklamaOdeme .= ' - Kupon: ' . $kupon_kod . ' (-' . number_format($kupon_indirim, 2) . ' TL)';
                    }

                    foreach ($sepet_raw as $item) {
                        $urunId      = $item->urun_id ?? 0;
                        $urunTipiRaw = $item->urun_tipi ?? $item->who ?? $item->name ?? '';
                        $urunTipiRaw = is_string($urunTipiRaw) ? strtolower($urunTipiRaw) : (string)$urunTipiRaw;
                        $urunAdi     = $item->urun_adi ?? $item->name ?? '';
                        $itemDomain  = $item->domain ?? null;

                        // ========== AKILLI TİPİ TESPİTİ ==========
                        // tipi: 0=domain, 1=hosting, 2=web paket
                        $tipi = null;

                        // 1) urun_tipi string olarak gelmişse direkt anla
                        if (in_array($urunTipiRaw, ['domain', '0'], true)) {
                            $tipi = 0;
                        } elseif (in_array($urunTipiRaw, ['hosting', 'hosting_yenileme', 'hosting-paket', 'web-hosting', '1'], true)) {
                            $tipi = 1;
                        } elseif (in_array($urunTipiRaw, ['web-paket', 'paket', 'yazilim', 'web_paket', '2'], true)) {
                            $tipi = 2;
                        }

                        // 2) Hala bilinmiyorsa, item->domain kolonuna bak (domain işareti)
                        if ($tipi === null && !empty($itemDomain)) {
                            $tipi = 0;
                        }

                        // 3) Hala bilinmiyorsa, urun_adi domain pattern'ine uyuyor mu? (.com, .net, .org, .tr)
                        if ($tipi === null && !empty($urunAdi)) {
                            if (preg_match('/\.(com|net|org|info|biz|tr|com\.tr|net\.tr|org\.tr|web|io|co|app|dev|tech|store|online|site)$/i', $urunAdi)) {
                                $tipi = 0;
                                $itemDomain = $urunAdi;
                            }
                        }

                        // 4) urun_id ile gerçek tabloları kontrol et (en güvenilir)
                        if ($tipi === null && $urunId > 0) {
                            try {
                                if (Schema::hasTable('hosting_paketler')) {
                                    $hp = DB::table('hosting_paketler')->where('id', $urunId)->first();
                                    if ($hp) { $tipi = 1; }
                                }
                                if ($tipi === null && Schema::hasTable('yazilimlar')) {
                                    $yz = DB::table('yazilimlar')->where('id', $urunId)->first();
                                    if ($yz) { $tipi = 2; }
                                }
                                if ($tipi === null && Schema::hasTable('domain_orders')) {
                                    $do = DB::table('domain_orders')->where('id', $urunId)->first();
                                    if ($do) {
                                        $tipi = 0;
                                        if (empty($itemDomain) && !empty($do->domain)) {
                                            $itemDomain = $do->domain;
                                        }
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::warning('Tipi tespit hatasi', ['urun_id' => $urunId, 'err' => $e->getMessage()]);
                            }
                        }

                        // 5) Hala bulunamadıysa default paket
                        if ($tipi === null) {
                            $tipi = 2;
                        }

                        // Paket bilgilerini yazilimlar tablosundan çek
                        $paketBaslik = $item->urun_adi ?? $item->name ?? null;
                        $domainAdi   = $itemDomain ?? ($item->domain ?? null);

                        if ($tipi === 2 && $urunId) {
                            $yazilim = DB::table('yazilimlar')->where('id', $urunId)->first();
                            if ($yazilim) {
                                $paketBaslik = $yazilim->adi ?? $paketBaslik ?? 'Web Paket';
                            }
                        } elseif ($tipi === 1 && $urunId) {
                            $hostingPaket = DB::table('hosting_paketler')->where('id', $urunId)->first();
                            if ($hostingPaket) {
                                $paketBaslik = $hostingPaket->adi ?? $paketBaslik ?? 'Hosting';
                            }
                        }

                        // Domain için: paket başlığı yoksa domain'in kendisini kullan
                        if ($tipi === 0) {
                            if (empty($domainAdi)) {
                                $domainAdi = $paketBaslik ?: ('Ürün #' . $urunId);
                            }
                            $paketBaslik = $domainAdi;
                        }
                        $paketBaslik = $paketBaslik ?: 'Ürün #' . $urunId;

                        $itemFiyat = (float) ($item->fiyat ?? $item->tutar ?? 0);
                        $miktar    = (int) ($item->miktar ?? 1);

                        // Yıl bazlı süre: domain/hosting için miktar = yıl sayısı
                        $sureYil   = max(1, (int) ($item->sure ?? $miktar));
                        $baslangic = now();
                        $bitis     = (clone $baslangic)->addYears($sureYil);

                        // ========== satilanlar tablosu (ana kayıt) ==========
                        $insertData = [
                            'uyeid'           => $userId,
                            'tutar'           => $itemFiyat * $miktar,
                            'tipi'            => $tipi,
                            'paket'           => $urunId ?: null,
                            'tarih'           => now(),
                            'baslangic_tarih' => $baslangic,
                            'bitis_tarih'     => $bitis,
                            'spno'            => 'BAK-' . time() . '-' . ($item->id ?? rand(100, 999)),
                            'odeme_yontemi'   => $aciklamaOdeme,
                            'paytronay'       => 1,
                            'durum'           => 1,
                            'ip'              => $request->ip(),
                        ];

                        // Kolon adı varyasyonları — Schema'ya göre hangi varsa onu yaz
                        // (Bazı kurulumlarda baslangic_tarih, bazılarında baslangic_tarihi)
                        if (Schema::hasColumn('satilanlar', 'baslangic_tarihi')) {
                            $insertData['baslangic_tarihi'] = $baslangic;
                        }
                        if (Schema::hasColumn('satilanlar', 'bitis_tarihi')) {
                            $insertData['bitis_tarihi'] = $bitis;
                        }
                        if (Schema::hasColumn('satilanlar', 'paket_baslik')) {
                            $insertData['paket_baslik'] = $paketBaslik;
                        }
                        if (Schema::hasColumn('satilanlar', 'paket_adi')) {
                            $insertData['paket_adi'] = $paketBaslik;
                        }
                        if ($tipi === 1 && Schema::hasColumn('satilanlar', 'hosting_baslik')) {
                            $insertData['hosting_baslik'] = $paketBaslik;
                        }
                        if (Schema::hasColumn('satilanlar', 'domain')) {
                            $insertData['domain'] = $domainAdi ?? '';
                        }
                        if (Schema::hasColumn('satilanlar', 'hosting') && $tipi === 1) {
                            $insertData['hosting'] = $urunId;
                        }
                        // adi kolonu (bazı tablolarda var)
                        if (Schema::hasColumn('satilanlar', 'adi')) {
                            $insertData['adi'] = $paketBaslik;
                        }
                        // fiyat kolonu
                        if (Schema::hasColumn('satilanlar', 'fiyat')) {
                            $insertData['fiyat'] = $itemFiyat * $miktar;
                        }
                        // sure kolonu (yıl)
                        if (Schema::hasColumn('satilanlar', 'sure')) {
                            $insertData['sure'] = $sureYil;
                        }
                        // fatura_id ilişkilendirme
                        if (isset($fatura_id) && Schema::hasColumn('satilanlar', 'fatura_id')) {
                            $insertData['fatura_id'] = $fatura_id;
                        }

                        $satilanId = DB::table('satilanlar')->insertGetId($insertData);

                        // ========== DOMAIN için ekstra tablolar ==========
                        if ($tipi === 0 && !empty($domainAdi)) {
                            // 1) alan_adlarim — müşterinin "Alan Adlarım" sayfası okuyor
                            try {
                                if (Schema::hasTable('alan_adlarim')) {
                                    $alanData = [
                                        'uyeid'        => $userId,
                                        'domain'       => $domainAdi,
                                        'kayit_tarihi' => $baslangic,
                                        'bitis_tarihi' => $bitis,
                                        'durum'        => 1,
                                    ];
                                    if (Schema::hasColumn('alan_adlarim', 'siparis_id')) {
                                        $alanData['siparis_id'] = $satilanId;
                                    }
                                    if (Schema::hasColumn('alan_adlarim', 'order_id')) {
                                        $alanData['order_id'] = $satilanId;
                                    }
                                    if (Schema::hasColumn('alan_adlarim', 'created_at')) {
                                        $alanData['created_at'] = now();
                                        $alanData['updated_at'] = now();
                                    }
                                    DB::table('alan_adlarim')->insert($alanData);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('alan_adlarim insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
                            }

                            // 2) domain_satislar — admin "Domain Satışları" sayfası okuyor
                            try {
                                if (Schema::hasTable('domain_satislar')) {
                                    $satisData = [
                                        'uyeid'           => $userId,
                                        'domain'          => $domainAdi,
                                        'tutar'           => $itemFiyat * $miktar,
                                        'baslangic_tarih' => $baslangic,
                                        'bitis_tarih'     => $bitis,
                                        'durum'           => 1,
                                    ];
                                    if (Schema::hasColumn('domain_satislar', 'musteri_email') && !empty($uye->email)) {
                                        $satisData['musteri_email'] = $uye->email;
                                    }
                                    if (Schema::hasColumn('domain_satislar', 'saglayici')) {
                                        $satisData['saglayici'] = 'manuel';
                                    }
                                    if (Schema::hasColumn('domain_satislar', 'created_at')) {
                                        $satisData['created_at'] = now();
                                        $satisData['updated_at'] = now();
                                    }
                                    DB::table('domain_satislar')->insert($satisData);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('domain_satislar insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
                            }

                            // 3) domain_orders — DomainOrder Eloquent (ResellerClub akışı için)
                            try {
                                if (Schema::hasTable('domain_orders') && class_exists('App\Models\DomainOrder')) {
                                    \App\Models\DomainOrder::create([
                                        'user_id'     => $userId,
                                        'domain'      => $domainAdi,
                                        'price'       => $itemFiyat,
                                        'years'       => $sureYil,
                                        'status'      => \App\Models\DomainOrder::STATUS_PAID,
                                        'payment_method' => 'bakiye',
                                        'fatura_id'   => $fatura_id ?? null,
                                    ]);
                                }
                            } catch (\Throwable $e) {
                                Log::warning('domain_orders insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
                            }
                        }
                    }
                }

                // Kupon kullanım sayısını artır
                if ($kupon_kod) {
                    \App\Services\KuponService::incrementUsage($kupon_kod);
                    session()->forget('kupon_kod');
                }

                // Bayi komisyonunu kaydet
                if ($bayi_bilgi && isset($bayi_bilgi['bayi_id'])) {
                    \App\Services\BayiKodService::recordCommission(
                        $bayi_bilgi['bayi_id'],
                        $userId,
                        $fatura_id ?? null,
                        $toplam,
                        $bayi_bilgi['komisyon_orani'] ?? 10,
                        $bayi_indirim
                    );
                    \App\Services\BayiKodService::clearSessionBayi();
                }

                // Sepeti temizle
                if (Schema::hasColumn('sepet', 'user_id')) {
                    DB::table('sepet')->where('user_id', $userId)->delete();
                } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                    DB::table('sepet')->where('uyeid', $userId)->delete();
                }

                DB::commit();

                // E-posta bildirimi (hata önemsiz, sessizce geç)
                try {
                    $urunler = [];
                    foreach ($sepet_raw as $item) {
                        $urunler[] = [
                            'adi'   => $item->urun_adi ?? $item->name ?? 'Ürün',
                            'fiyat' => (float) ($item->fiyat ?? $item->tutar ?? 0),
                        ];
                    }
                    \App\Services\EmailNotificationService::sendPurchaseNotification(
                        $fatura_id ?? 0,
                        $userId,
                        $genel_toplam,
                        $urunler
                    );
                } catch (\Throwable $e) {
                    Log::warning('E-posta bildirimi gönderilemedi', ['error' => $e->getMessage()]);
                }

                $mesaj = $genel_toplam > 0
                    ? 'Ödemeniz tamamlandı. Bakiyenizden ' . number_format($genel_toplam, 2, ',', '.') . ' ₺ düşüldü.'
                    : 'Ücretsiz paketiniz başarıyla hesabınıza eklendi.';

                // Admine panel bildirimi — bakiye ile sipariş tamamlandı
                try {
                    $musteri = DB::table('uyeler')->where('id', $userId)->first();
                    $musteriAd = $musteri ? (trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? '')) ?: ($musteri->email ?? 'Müşteri')) : 'Müşteri';
                    admin_bildirim_gonder(
                        '💰 Yeni Sipariş (Bakiye): ₺' . number_format($genel_toplam, 2, ',', '.'),
                        $musteriAd . ' bakiye ile ödeme yaptı.' . (isset($fatura_id) ? ' Fatura #' . $fatura_id : ''),
                        'odeme',
                        'faturalar',
                        isset($fatura_id) ? (int) $fatura_id : null
                    );
                } catch (\Throwable $e) {
                    Log::warning('Bakiye ödeme admin bildirimi: ' . $e->getMessage());
                }

                return redirect()->route('web.paketlerim')->with('success', $mesaj);

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Bakiye ödeme hatası', [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'user_id' => $userId,
                ]);
                return redirect()->to(localized_route('sepet'))->with('error', 'Ödeme işlemi sırasında hata oluştu: ' . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            Log::error('Bakiye ödeme genel hatası', [
                'error' => $e->getMessage()
            ]);
            return redirect()->to(localized_route('sepet'))->with('error', 'Ödeme işlemi başlatılamadı.');
        }
    }
    
    /**
     * DN Bank coin ile ödeme.
     * Mevcut bakiye ödemesinden bağımsızdır; uyeler.dnbank_bakiye'den düşer ve
     * dnbank_hareketleri defterine işler. Borç (taksit) ayrıca admin tarafından yönetilir.
     */
    public function dnbankOdeme(Request $request)
    {
        try {
            $userId = Auth::guard('uye')->id();
            $uye    = Auth::guard('uye')->user();

            // Sepet
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

            // PROFİL/FATURA KONTROLÜ (madde 4) — coin ödemesinde de zorunlu
            $uyeProfil = DB::table('uyeler')->where('id', $userId)->first();
            if ($uyeProfil) {
                $eksikler = [];
                $bos = function ($v) { return $v === null || trim((string) $v) === ''; };
                if ((int) ($uyeProfil->utipi ?? 0) === 1) {
                    if ($bos($uyeProfil->fatura_unvan ?? null)) $eksikler[] = 'Fatura Ünvanı';
                    if ($bos($uyeProfil->fatura_tc ?? null))    $eksikler[] = 'Vergi No / TC';
                    if ($bos($uyeProfil->fatura_adres ?? null)) $eksikler[] = 'Fatura Adresi';
                } else {
                    if ($bos($uyeProfil->tc ?? null))    $eksikler[] = 'TC Kimlik No';
                    if ($bos($uyeProfil->ad ?? null))    $eksikler[] = 'Ad';
                    if ($bos($uyeProfil->soyad ?? null)) $eksikler[] = 'Soyad';
                    if ($bos($uyeProfil->adres ?? null)) $eksikler[] = 'Adres';
                }
                if (!empty($eksikler)) {
                    $hedef = localized_route('bilgilerim');
                    $ayrac = (strpos($hedef, '?') !== false) ? '&' : '?';
                    $hedef .= $ayrac . 'profil_eksik=1&eksik=' . urlencode(implode(', ', $eksikler));
                    return redirect()->to($hedef);
                }
            }

            // Toplam
            $toplam = 0;
            foreach ($sepet_raw as $item) {
                $fiyat  = $item->fiyat ?? $item->tutar ?? $item->price ?? $item->ucret ?? 0;
                $miktar = $item->miktar ?? 1;
                $toplam += (float) $fiyat * (int) $miktar;
            }

            // Kupon
            $kupon_kod = session('kupon_kod', '');
            $kupon_indirim = 0;
            if ($kupon_kod) {
                $kupon_sonuc = \App\Services\KuponService::validateAndApply($kupon_kod, $toplam);
                if ($kupon_sonuc['success']) {
                    $kupon_indirim = $kupon_sonuc['indirim'];
                } else {
                    session()->forget('kupon_kod');
                }
            }

            // Bayi kodu
            $bayi_bilgi = \App\Services\BayiKodService::getSessionBayi();
            $bayi_indirim = 0;
            if ($bayi_bilgi && isset($bayi_bilgi['bayi_kodu'])) {
                $bayi_sonuc = \App\Services\BayiKodService::validateAndApply($bayi_bilgi['bayi_kodu'], $toplam);
                if ($bayi_sonuc['success']) {
                    $bayi_indirim = $bayi_sonuc['indirim'];
                }
            }

            $genel_toplam = max(0, $toplam - $kupon_indirim - $bayi_indirim);

            if ($genel_toplam <= 0) {
                return redirect()->to(localized_route('sepet'))->with('error', 'Bu sepet için DN Bank ödemesi gerekmiyor; ücretsiz/normal ödeme adımını kullanın.');
            }

            // DN Bank coin kontrolü
            $coin = \App\Services\DnBankService::bakiye((int) $userId);
            if ($coin + 0.0001 < $genel_toplam) {
                return redirect()->to(localized_route('sepet'))->with('error', 'Yetersiz DN Bank bakiyesi! Mevcut coin: ' . number_format($coin, 2, ',', '.') . ' ₺');
            }

            DB::beginTransaction();
            try {
                // Fatura
                $fatura_data = [
                    'uyeid'         => $userId,
                    'tutar'         => $genel_toplam,
                    'toplam'        => $genel_toplam,
                    'durum'         => 1,
                    'fatura_no'     => 'FAT-' . time() . $userId,
                    'aciklama'      => 'Sepet Ödemesi (DN Bank)',
                    'tarih'         => now(),
                    'odenen_tarih'  => now(),
                    'tip'           => 'sepet_odeme',
                    'odeme_yontemi' => 'dnbank',
                ];
                if ($bayi_bilgi && isset($bayi_bilgi['bayi_kodu'])) {
                    $fatura_data['bayi_kodu'] = $bayi_bilgi['bayi_kodu'];
                }
                $fatura_id = DB::table('faturalar')->insertGetId($fatura_data);

                // Coin düş + defter kaydı
                \App\Services\DnBankService::bakiyeHarca(
                    (int) $userId,
                    $genel_toplam,
                    'Sepet Ödemesi • Fatura #' . $fatura_id,
                    (int) $fatura_id
                );

                // Sipariş kalemleri (satilanlar + domain tabloları)
                $aciklamaOdeme = 'DN Bank';
                if ($kupon_kod && $kupon_indirim > 0) {
                    $aciklamaOdeme .= ' - Kupon: ' . $kupon_kod . ' (-' . number_format($kupon_indirim, 2) . ' TL)';
                }
                $this->dnbankSiparisKalemleri($request, $userId, $uye, $sepet_raw, $fatura_id, $aciklamaOdeme);

                // Kupon kullanımı
                if ($kupon_kod) {
                    \App\Services\KuponService::incrementUsage($kupon_kod);
                    session()->forget('kupon_kod');
                }

                // Bayi komisyonu
                if ($bayi_bilgi && isset($bayi_bilgi['bayi_id'])) {
                    \App\Services\BayiKodService::recordCommission(
                        $bayi_bilgi['bayi_id'], $userId, $fatura_id, $toplam,
                        $bayi_bilgi['komisyon_orani'] ?? 10, $bayi_indirim
                    );
                    \App\Services\BayiKodService::clearSessionBayi();
                }

                // Sepeti temizle
                if (Schema::hasColumn('sepet', 'user_id')) {
                    DB::table('sepet')->where('user_id', $userId)->delete();
                } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                    DB::table('sepet')->where('uyeid', $userId)->delete();
                }

                DB::commit();

                // E-posta bildirimi (hata önemsiz)
                try {
                    $urunler = [];
                    foreach ($sepet_raw as $item) {
                        $urunler[] = [
                            'adi'   => $item->urun_adi ?? $item->name ?? 'Ürün',
                            'fiyat' => (float) ($item->fiyat ?? $item->tutar ?? 0),
                        ];
                    }
                    \App\Services\EmailNotificationService::sendPurchaseNotification($fatura_id, $userId, $genel_toplam, $urunler);
                } catch (\Throwable $e) {
                    Log::warning('DN Bank e-posta bildirimi gönderilemedi', ['error' => $e->getMessage()]);
                }

                // Admin bildirimi
                try {
                    $musteri = DB::table('uyeler')->where('id', $userId)->first();
                    $musteriAd = $musteri ? (trim(($musteri->ad ?? '') . ' ' . ($musteri->soyad ?? '')) ?: ($musteri->email ?? 'Müşteri')) : 'Müşteri';
                    admin_bildirim_gonder(
                        '🏦 Yeni Sipariş (DN Bank): ₺' . number_format($genel_toplam, 2, ',', '.'),
                        $musteriAd . ' DN Bank coin ile ödeme yaptı. Fatura #' . $fatura_id,
                        'odeme', 'faturalar', (int) $fatura_id
                    );
                } catch (\Throwable $e) {
                    Log::warning('DN Bank ödeme admin bildirimi: ' . $e->getMessage());
                }

                return redirect()->route('web.paketlerim')->with('success', 'Ödemeniz tamamlandı. DN Bank bakiyenizden ' . number_format($genel_toplam, 2, ',', '.') . ' ₺ düşüldü.');

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('DN Bank ödeme hatası', [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'user_id' => $userId,
                ]);
                return redirect()->to(localized_route('sepet'))->with('error', 'Ödeme işlemi sırasında hata oluştu: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('DN Bank ödeme genel hatası', ['error' => $e->getMessage()]);
            return redirect()->to(localized_route('sepet'))->with('error', 'Ödeme işlemi başlatılamadı.');
        }
    }

    /**
     * DN Bank ödemesinde sepet kalemlerini satilanlar (+ domain tabloları) olarak işle.
     * bakiyeOdeme'deki fulfillment mantığının DN Bank için ayrılmış kopyasıdır
     * (mevcut bakiye akışına dokunmamak için ayrı tutuldu).
     */
    private function dnbankSiparisKalemleri(Request $request, $userId, $uye, $sepet_raw, $fatura_id, $aciklamaOdeme)
    {
        if (!DB::getSchemaBuilder()->hasTable('satilanlar')) {
            return;
        }

        foreach ($sepet_raw as $item) {
            $urunId      = $item->urun_id ?? 0;
            $urunTipiRaw = $item->urun_tipi ?? $item->who ?? $item->name ?? '';
            $urunTipiRaw = is_string($urunTipiRaw) ? strtolower($urunTipiRaw) : (string) $urunTipiRaw;
            $urunAdi     = $item->urun_adi ?? $item->name ?? '';
            $itemDomain  = $item->domain ?? null;

            // tipi: 0=domain, 1=hosting, 2=web paket
            $tipi = null;
            if (in_array($urunTipiRaw, ['domain', '0'], true)) {
                $tipi = 0;
            } elseif (in_array($urunTipiRaw, ['hosting', 'hosting_yenileme', 'hosting-paket', 'web-hosting', '1'], true)) {
                $tipi = 1;
            } elseif (in_array($urunTipiRaw, ['web-paket', 'paket', 'yazilim', 'web_paket', '2'], true)) {
                $tipi = 2;
            }

            if ($tipi === null && !empty($itemDomain)) {
                $tipi = 0;
            }

            if ($tipi === null && !empty($urunAdi)) {
                if (preg_match('/\.(com|net|org|info|biz|tr|com\.tr|net\.tr|org\.tr|web|io|co|app|dev|tech|store|online|site)$/i', $urunAdi)) {
                    $tipi = 0;
                    $itemDomain = $urunAdi;
                }
            }

            if ($tipi === null && $urunId > 0) {
                try {
                    if (Schema::hasTable('hosting_paketler')) {
                        $hp = DB::table('hosting_paketler')->where('id', $urunId)->first();
                        if ($hp) { $tipi = 1; }
                    }
                    if ($tipi === null && Schema::hasTable('yazilimlar')) {
                        $yz = DB::table('yazilimlar')->where('id', $urunId)->first();
                        if ($yz) { $tipi = 2; }
                    }
                    if ($tipi === null && Schema::hasTable('domain_orders')) {
                        $do = DB::table('domain_orders')->where('id', $urunId)->first();
                        if ($do) {
                            $tipi = 0;
                            if (empty($itemDomain) && !empty($do->domain)) {
                                $itemDomain = $do->domain;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('DN Bank tipi tespit hatasi', ['urun_id' => $urunId, 'err' => $e->getMessage()]);
                }
            }

            if ($tipi === null) {
                $tipi = 2;
            }

            // Paket bilgisi
            $paketBaslik = $item->urun_adi ?? $item->name ?? null;
            $domainAdi   = $itemDomain ?? ($item->domain ?? null);

            if ($tipi === 2 && $urunId) {
                $yazilim = DB::table('yazilimlar')->where('id', $urunId)->first();
                if ($yazilim) {
                    $paketBaslik = $yazilim->adi ?? $paketBaslik ?? 'Web Paket';
                }
            } elseif ($tipi === 1 && $urunId) {
                $hostingPaket = DB::table('hosting_paketler')->where('id', $urunId)->first();
                if ($hostingPaket) {
                    $paketBaslik = $hostingPaket->adi ?? $paketBaslik ?? 'Hosting';
                }
            }

            if ($tipi === 0) {
                if (empty($domainAdi)) {
                    $domainAdi = $paketBaslik ?: ('Ürün #' . $urunId);
                }
                $paketBaslik = $domainAdi;
            }
            $paketBaslik = $paketBaslik ?: 'Ürün #' . $urunId;

            $itemFiyat = (float) ($item->fiyat ?? $item->tutar ?? 0);
            $miktar    = (int) ($item->miktar ?? 1);

            $sureYil   = max(1, (int) ($item->sure ?? $miktar));
            $baslangic = now();
            $bitis     = (clone $baslangic)->addYears($sureYil);

            // satilanlar ana kayıt
            $insertData = [
                'uyeid'           => $userId,
                'tutar'           => $itemFiyat * $miktar,
                'tipi'            => $tipi,
                'paket'           => $urunId ?: null,
                'tarih'           => now(),
                'baslangic_tarih' => $baslangic,
                'bitis_tarih'     => $bitis,
                'spno'            => 'DNB-' . time() . '-' . ($item->id ?? rand(100, 999)),
                'odeme_yontemi'   => $aciklamaOdeme,
                'paytronay'       => 1,
                'durum'           => 1,
                'ip'              => $request->ip(),
            ];

            if (Schema::hasColumn('satilanlar', 'baslangic_tarihi')) { $insertData['baslangic_tarihi'] = $baslangic; }
            if (Schema::hasColumn('satilanlar', 'bitis_tarihi'))     { $insertData['bitis_tarihi'] = $bitis; }
            if (Schema::hasColumn('satilanlar', 'paket_baslik'))     { $insertData['paket_baslik'] = $paketBaslik; }
            if (Schema::hasColumn('satilanlar', 'paket_adi'))        { $insertData['paket_adi'] = $paketBaslik; }
            if ($tipi === 1 && Schema::hasColumn('satilanlar', 'hosting_baslik')) { $insertData['hosting_baslik'] = $paketBaslik; }
            if (Schema::hasColumn('satilanlar', 'domain'))           { $insertData['domain'] = $domainAdi ?? ''; }
            if (Schema::hasColumn('satilanlar', 'hosting') && $tipi === 1) { $insertData['hosting'] = $urunId; }
            if (Schema::hasColumn('satilanlar', 'adi'))              { $insertData['adi'] = $paketBaslik; }
            if (Schema::hasColumn('satilanlar', 'fiyat'))            { $insertData['fiyat'] = $itemFiyat * $miktar; }
            if (Schema::hasColumn('satilanlar', 'sure'))             { $insertData['sure'] = $sureYil; }
            if (!empty($fatura_id) && Schema::hasColumn('satilanlar', 'fatura_id')) { $insertData['fatura_id'] = $fatura_id; }

            $satilanId = DB::table('satilanlar')->insertGetId($insertData);

            // Domain için ekstra tablolar
            if ($tipi === 0 && !empty($domainAdi)) {
                try {
                    if (Schema::hasTable('alan_adlarim')) {
                        $alanData = [
                            'uyeid'        => $userId,
                            'domain'       => $domainAdi,
                            'kayit_tarihi' => $baslangic,
                            'bitis_tarihi' => $bitis,
                            'durum'        => 1,
                        ];
                        if (Schema::hasColumn('alan_adlarim', 'siparis_id')) { $alanData['siparis_id'] = $satilanId; }
                        if (Schema::hasColumn('alan_adlarim', 'order_id'))   { $alanData['order_id'] = $satilanId; }
                        if (Schema::hasColumn('alan_adlarim', 'created_at')) {
                            $alanData['created_at'] = now();
                            $alanData['updated_at'] = now();
                        }
                        DB::table('alan_adlarim')->insert($alanData);
                    }
                } catch (\Throwable $e) {
                    Log::warning('DN Bank alan_adlarim insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
                }

                try {
                    if (Schema::hasTable('domain_satislar')) {
                        $satisData = [
                            'uyeid'           => $userId,
                            'domain'          => $domainAdi,
                            'tutar'           => $itemFiyat * $miktar,
                            'baslangic_tarih' => $baslangic,
                            'bitis_tarih'     => $bitis,
                            'durum'           => 1,
                        ];
                        if (Schema::hasColumn('domain_satislar', 'musteri_email') && !empty($uye->email)) {
                            $satisData['musteri_email'] = $uye->email;
                        }
                        if (Schema::hasColumn('domain_satislar', 'saglayici')) { $satisData['saglayici'] = 'manuel'; }
                        if (Schema::hasColumn('domain_satislar', 'created_at')) {
                            $satisData['created_at'] = now();
                            $satisData['updated_at'] = now();
                        }
                        DB::table('domain_satislar')->insert($satisData);
                    }
                } catch (\Throwable $e) {
                    Log::warning('DN Bank domain_satislar insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
                }

                try {
                    if (Schema::hasTable('domain_orders') && class_exists('App\Models\DomainOrder')) {
                        \App\Models\DomainOrder::create([
                            'user_id'        => $userId,
                            'domain'         => $domainAdi,
                            'price'          => $itemFiyat,
                            'years'          => $sureYil,
                            'status'         => \App\Models\DomainOrder::STATUS_PAID,
                            'payment_method' => 'dnbank',
                            'fatura_id'      => $fatura_id ?? null,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('DN Bank domain_orders insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
                }
            }
        }
    }

    /**
     * Kupon uygula
     */
    public function kuponUygula(Request $request)
    {
        $request->validate([
            'kupon_kod' => 'required|string|max:50'
        ]);
        
        $kupon_kod = strtoupper(trim($request->kupon_kod));
        
        // Sepet toplamını hesapla
        $userId = Auth::guard('uye')->id();
        $sepet_raw = null;
        
        if (Schema::hasColumn('sepet', 'user_id')) {
            $sepet_raw = DB::table('sepet')->where('user_id', $userId)->get();
        } elseif (Schema::hasColumn('sepet', 'uyeid')) {
            $sepet_raw = DB::table('sepet')->where('uyeid', $userId)->get();
        } else {
            return redirect()->to(localized_route('sepet'))->with('error', 'Sepetiniz boş.');
        }
        
        $toplam = 0;
        foreach ($sepet_raw as $item) {
            $fiyat = $item->fiyat ?? $item->tutar ?? $item->price ?? 0;
            $miktar = $item->miktar ?? 1;
            $toplam += (float) $fiyat * (int) $miktar;
        }
        
        // Kupon doğrula
        $kupon_sonuc = \App\Services\KuponService::validateAndApply($kupon_kod, $toplam);
        
        if ($kupon_sonuc['success']) {
            session(['kupon_kod' => $kupon_kod]);
            return redirect()->to(localized_route('sepet'))->with('success', $kupon_sonuc['message'] . ' ' . number_format($kupon_sonuc['indirim'], 2, ',', '.') . ' TL indirim uygulandı!');
        } else {
            $msg = !empty($kupon_sonuc['message']) ? $kupon_sonuc['message'] : 'Hatalı kod';
            $url = localized_route('sepet');
            $url .= (str_contains($url, '?') ? '&' : '?') . 'kupon_error=1';
            return redirect()->to($url)->with('error', $msg);
        }
    }
    
    /**
     * Kupon kaldır
     */
    public function kuponKaldir(Request $request)
    {
        session()->forget('kupon_kod');
        return redirect()->to(localized_route('sepet'))->with('success', 'Kupon kaldırıldı.');
    }
    
    /**
     * Bayi kodu uygula
     */
    public function bayiKoduUygula(Request $request)
    {
        $request->validate([
            'bayi_kodu' => 'required|string|max:50'
        ]);
        
        $bayiKodu = strtoupper(trim($request->bayi_kodu));
        
        // Sepet toplamını hesapla
        $userId = Auth::guard('uye')->id();
        $sepet_raw = null;
        
        if (Schema::hasColumn('sepet', 'user_id')) {
            $sepet_raw = DB::table('sepet')->where('user_id', $userId)->get();
        } elseif (Schema::hasColumn('sepet', 'uyeid')) {
            $sepet_raw = DB::table('sepet')->where('uyeid', $userId)->get();
        } else {
            return redirect()->to(localized_route('sepet'))->with('error', 'Sepetiniz boş.');
        }
        
        $toplam = 0;
        foreach ($sepet_raw as $item) {
            $fiyat = $item->fiyat ?? $item->tutar ?? $item->price ?? 0;
            $miktar = $item->miktar ?? 1;
            $toplam += (float) $fiyat * (int) $miktar;
        }
        
        // Bayi kodunu doğrula
        $sonuc = \App\Services\BayiKodService::validateAndApply($bayiKodu, $toplam);
        
        if ($sonuc['success']) {
            // Session'a bayi bilgisini kaydet
            \App\Services\BayiKodService::setSessionBayi([
                'bayi_kodu' => $bayiKodu,
                'bayi_id' => $sonuc['bayi_id'],
                'indirim' => $sonuc['indirim'],
                'indirim_orani' => $sonuc['indirim_orani'],
                'komisyon_orani' => $sonuc['komisyon_orani'],
            ]);
            
            return redirect()->to(localized_route('sepet'))->with('success', $sonuc['message'] . ' ' . number_format($sonuc['indirim'], 2, ',', '.') . ' TL indirim!');
        } else {
            $msg = !empty($sonuc['message']) ? $sonuc['message'] : 'Hatalı kod';
            $url = localized_route('sepet');
            $url .= (str_contains($url, '?') ? '&' : '?') . 'bayi_error=1';
            return redirect()->to($url)->with('error', $msg);
        }
    }
    
    /**
     * Bayi kodu kaldır
     */
    public function bayiKoduKaldir(Request $request)
    {
        \App\Services\BayiKodService::clearSessionBayi();
        return redirect()->to(localized_route('sepet'))->with('success', 'Bayi kodu kaldırıldı.');
    }
}