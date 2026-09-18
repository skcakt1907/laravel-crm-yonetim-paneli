<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HeaderBackgroundHelper
{
    /**
     * Sayfa header arkaplan stilini döndürür
     * 
     * @param string $sayfaAdi Sayfa adı (anasayfa, paketler, hosting, blog, iletisim, hizmet, domain, sayfa, referanslar, firsatlar)
     * @return string CSS style string
     */
    public static function getHeaderBackgroundStyle($sayfaAdi)
    {
        try {
            $ayarlar = DB::table('ayarlar')->first();
            if (!$ayarlar) {
                return self::getDefaultStyle($sayfaAdi);
            }
            
            $turu = $ayarlar->{"{$sayfaAdi}_arkaplan_turu"} ?? 'resim';
            
            if ($turu == 'resim') {
                return self::getImageStyle($sayfaAdi);
            } elseif ($turu == 'renk') {
                $renk = $ayarlar->{"{$sayfaAdi}_arkaplan_renk"} ?? self::getDefaultColor($sayfaAdi);
                return "background-color: {$renk};";
            } elseif ($turu == 'gradient') {
                $baslangic = $ayarlar->{"{$sayfaAdi}_gradient_baslangic"} ?? self::getDefaultGradientStart($sayfaAdi);
                $bitis = $ayarlar->{"{$sayfaAdi}_gradient_bitis"} ?? self::getDefaultGradientEnd($sayfaAdi);
                return "background: linear-gradient(135deg, {$baslangic} 0%, {$bitis} 100%);";
            }
            
            return self::getDefaultStyle($sayfaAdi);
        } catch (\Exception $e) {
            return self::getDefaultStyle($sayfaAdi);
        }
    }
    
    /**
     * Resim stilini döndürür
     */
    protected static function getImageStyle($sayfaAdi)
    {
        try {
            $arkaplan = DB::table('arka_plan')->where('id', 1)->first();
            $resimKolonu = self::getImageColumnName($sayfaAdi);
            $resim = $arkaplan && isset($arkaplan->{$resimKolonu}) ? $arkaplan->{$resimKolonu} : 'bg.jpg';
            $klasor = self::getImageFolder($sayfaAdi);
            
            // Dosya var mı kontrol et
            $dosyaYolu = public_path("tema/uploads/arkaplan/{$klasor}/{$resim}");
            if (!file_exists($dosyaYolu)) {
                // Eğer bg.jpg de yoksa, varsayılan gradient döndür
                $defaultBg = public_path("tema/uploads/arkaplan/{$klasor}/bg.jpg");
                if (!file_exists($defaultBg)) {
                    return self::getDefaultGradientStyle($sayfaAdi);
                }
                $resim = 'bg.jpg';
            }
            
            return "background-image: url(" . asset("tema/uploads/arkaplan/{$klasor}/{$resim}") . "); background-size: cover; background-position: center; background-repeat: no-repeat;";
        } catch (\Exception $e) {
            return self::getDefaultGradientStyle($sayfaAdi);
        }
    }
    
    /**
     * Varsayılan gradient stil
     */
    protected static function getDefaultGradientStyle($sayfaAdi)
    {
        $baslangic = self::getDefaultGradientStart($sayfaAdi);
        $bitis = self::getDefaultGradientEnd($sayfaAdi);
        return "background: linear-gradient(135deg, {$baslangic} 0%, {$bitis} 100%);";
    }
    
    /**
     * Sayfa adına göre resim kolonu adını döndürür
     */
    protected static function getImageColumnName($sayfaAdi)
    {
        $map = [
            'anasayfa' => 'anasayfa',
            'paketler' => 'paketler',
            'hosting' => 'hosting',
            'blog' => 'blog',
            'iletisim' => 'iletisim',
            'hizmet' => 'hizmetler',
            'domain' => 'alanadi',
            'sayfa' => 'sayfalar',
            'referanslar' => 'referanslar',
            'firsatlar' => 'firsatlar',
        ];
        
        return $map[$sayfaAdi] ?? 'paketler';
    }
    
    /**
     * Sayfa adına göre resim klasörünü döndürür
     */
    protected static function getImageFolder($sayfaAdi)
    {
        $map = [
            'anasayfa' => 'anasayfa',
            'paketler' => 'paketler',
            'hosting' => 'hosting',
            'blog' => 'blog',
            'iletisim' => 'iletisim',
            'hizmet' => 'hizmetler',
            'domain' => 'alanadi',
            'sayfa' => 'sayfalar',
            'referanslar' => 'referanslar',
            'firsatlar' => 'firsatlar',
        ];
        
        return $map[$sayfaAdi] ?? 'paketler';
    }
    
    /**
     * Varsayılan stil
     */
    protected static function getDefaultStyle($sayfaAdi)
    {
        try {
            $klasor = self::getImageFolder($sayfaAdi);
            $dosyaYolu = public_path("tema/uploads/arkaplan/{$klasor}/bg.jpg");
            
            // Eğer varsayılan resim dosyası varsa, onu kullan
            if (file_exists($dosyaYolu)) {
                return "background-image: url(" . asset("tema/uploads/arkaplan/{$klasor}/bg.jpg") . "); background-size: cover; background-position: center; background-repeat: no-repeat;";
            }
            
            // Yoksa gradient kullan
            return self::getDefaultGradientStyle($sayfaAdi);
        } catch (\Exception $e) {
            return self::getDefaultGradientStyle($sayfaAdi);
        }
    }
    
    /**
     * Varsayılan renk
     */
    protected static function getDefaultColor($sayfaAdi)
    {
        $colors = [
            'anasayfa' => '#141e30',
            'paketler' => '#141e30',
            'hosting' => '#141e30',
            'blog' => '#141e30',
            'iletisim' => '#141e30',
            'hizmet' => '#141e30',
            'domain' => '#141e30',
            'sayfa' => '#141e30',
            'referanslar' => '#141e30',
            'firsatlar' => '#141e30',
        ];
        
        return $colors[$sayfaAdi] ?? '#141e30';
    }
    
    /**
     * Varsayılan gradient başlangıç
     */
    protected static function getDefaultGradientStart($sayfaAdi)
    {
        return '#141e30';
    }
    
    /**
     * Varsayılan gradient bitiş
     */
    protected static function getDefaultGradientEnd($sayfaAdi)
    {
        return '#243b55';
    }
}
