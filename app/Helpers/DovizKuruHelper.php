<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DovizKuruHelper
{
    /**
     * TCMB'den güncel kurları çek
     * NOT: TCMB kurları saat 15:30'dan sonra güncellenir
     * 15:30'dan önce dünün kurları, 15:30'dan sonra bugünün kurları kullanılır
     */
    public static function tcmbKurlariCek()
    {
        // Saat kontrolü: 15:30'dan önce dünün, sonra bugünün kurları
        $currentHour = (int)date('H');
        $currentMinute = (int)date('i');
        $currentTime = $currentHour * 60 + $currentMinute; // Dakika cinsinden
        $updateTime = 15 * 60 + 30; // 15:30 = 930 dakika
        
        // 15:30'dan önceyse dünün kurlarını kullan
        $targetDate = ($currentTime < $updateTime) 
            ? date('Y-m-d', strtotime('-1 day')) 
            : date('Y-m-d');
        
        $cacheKey = 'tcmb_kurlar_' . $targetDate;
        
        return Cache::remember($cacheKey, 86400, function () use ($targetDate) {
            try {
                // TCMB'den kurları çek
                $response = Http::timeout(10)->get('https://www.tcmb.gov.tr/kurlar/today.xml');
                
                if ($response->successful()) {
                    $xml = simplexml_load_string($response->body());
                    
                    // XML'deki tarihi kontrol et
                    $xmlDate = (string)$xml['Tarih'] ?? null;
                    $xmlDateFormatted = null;
                    
                    if ($xmlDate) {
                        // TCMB tarih formatı: "26.11.2025" -> "2025-11-26"
                        $dateParts = explode('.', $xmlDate);
                        if (count($dateParts) == 3) {
                            $xmlDateFormatted = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                        }
                    }
                    
                    $kurlar = [
                        'TL' => 1.00,
                        'TRY' => 1.00,
                    ];
                    
                    // XML'den kurları çek
                    foreach ($xml->Currency as $currency) {
                        $code = (string)$currency['CurrencyCode'];
                        
                        // USD, EUR, AED kurlarını al (ALIŞ fiyatı - müşteri fiyatlarını alış kuruna göre hesaplıyoruz)
                        if (in_array($code, ['USD', 'EUR', 'AED', 'GBP'])) {
                            // ForexBuying = Alış fiyatı (kur hesabında temel aldığımız değer)
                            $rate = 0.0;
                            
                            if (isset($currency->ForexBuying) && (float)$currency->ForexBuying > 0) {
                                $rate = (float)$currency->ForexBuying;
                            } elseif (isset($currency->ForexSelling) && (float)$currency->ForexSelling > 0) {
                                // Yedek olarak satış kurunu kullan (TCMB bazı günler alış kurunu boş bırakabiliyor)
                                $rate = (float)$currency->ForexSelling;
                            }
                            
                            if ($rate > 0) {
                                $kurlar[$code] = $rate;
                            }
                        }
                    }
                    
                    // En az USD, EUR ve AED olmalı ve değerleri 0'dan büyük olmalı
                    if (isset($kurlar['USD']) && $kurlar['USD'] > 0 &&
                        isset($kurlar['EUR']) && $kurlar['EUR'] > 0 &&
                        isset($kurlar['AED']) && $kurlar['AED'] > 0) {
                        
                        // Tarih bilgisini de kaydet
                        $kurlar['_tarih'] = $xmlDateFormatted ?? $targetDate;
                        $kurlar['_kaynak'] = 'TCMB';
                        $kurlar['_kullanilan_tarih'] = $targetDate;
                        
                        return $kurlar;
                    }
                }
                
                // Kurlar bulunamadıysa log
                \Log::warning('TCMB: Kurlar bulunamadı, tarih: ' . $targetDate);
                
            } catch (\Exception $e) {
                \Log::error('TCMB Kur Hatası: ' . $e->getMessage());
            }
            
            // Varsayılan kurlar (hata durumunda veya TCMB'ye erişilemediğinde)
            // Son güncelleme: 26.11.2025 (SATIŞ fiyatları)
            return [
                'TL' => 1.00,
                'TRY' => 1.00,
                'USD' => 42.4341,  // TCMB güncel satış kur
                'EUR' => 49.1239,  // TCMB güncel satış kur
                'AED' => 11.6189,  // TCMB güncel satış kur
                'GBP' => 56.50,    // TCMB güncel satış kur (yedek)
                '_tarih' => $targetDate,
                '_kaynak' => 'Varsayılan',
                '_kullanilan_tarih' => $targetDate,
            ];
        });
    }
    
    /**
     * TL'den başka para birimine çevir
     */
    public static function tldenCevir($tutar, $paraBirimi)
    {
        // Tutarı float'a çevir
        $tutar = (float)$tutar;
        
        // TRY'yi normalize et
        if ($paraBirimi == 'TRY') {
            $paraBirimi = 'TL';
        }
        
        // TL ise direkt döndür
        if ($paraBirimi == 'TL') {
            return $tutar;
        }
        
        $kurlar = self::tcmbKurlariCek();
        
        if (!isset($kurlar[$paraBirimi])) {
            return $tutar;
        }
        
        // TL / Kur = Döviz
        return $tutar / $kurlar[$paraBirimi];
    }
    
    /**
     * Başka para biriminden TL'ye çevir
     */
    public static function tlYeCevir($tutar, $paraBirimi)
    {
        // Tutarı float'a çevir
        $tutar = (float)$tutar;
        
        // TRY'yi normalize et
        if ($paraBirimi == 'TRY') {
            $paraBirimi = 'TL';
        }
        
        // TL ise direkt döndür
        if ($paraBirimi == 'TL') {
            return $tutar;
        }
        
        $kurlar = self::tcmbKurlariCek();
        
        if (!isset($kurlar[$paraBirimi])) {
            return $tutar;
        }
        
        // Döviz * Kur = TL
        return $tutar * $kurlar[$paraBirimi];
    }
    
    /**
     * Formatlanmış fiyat göster (sadece rakam, sembol yok)
     */
    public static function fiyatGoster($tutar, $paraBirimi = 'TL')
    {
        // Tutarı float'a çevir
        $tutar = (float)$tutar;
        
        // TRY'yi TL'ye normalize et
        if ($paraBirimi == 'TRY') {
            $paraBirimi = 'TL';
        }
        
        if ($paraBirimi == 'TL') {
            return number_format($tutar, 2, ',', '.');
        } else {
            $dovizTutar = self::tldenCevir($tutar, $paraBirimi);
            return number_format($dovizTutar, 2, '.', ',');
        }
    }
    
    /**
     * TCMB kurlarını test et / debug
     */
    public static function kurlariGetir()
    {
        return self::tcmbKurlariCek();
    }
}