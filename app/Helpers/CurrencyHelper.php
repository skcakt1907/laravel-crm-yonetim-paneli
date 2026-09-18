<?php

namespace App\Helpers;

class CurrencyHelper
{
    private static $symbols = [
        'TL' => '₺',
        'TRY' => '₺',
        'USD' => '$',
        'EUR' => '€',
        'AED' => 'د.إ',
    ];
    
    /**
     * TCMB'den güncel kurları al
     */
    private static function getRates()
    {
        $kurlar = DovizKuruHelper::tcmbKurlariCek();
        
        // CurrencyHelper için formatla (TL bazlı)
        return [
            'TL' => 1.00,
            'TRY' => 1.00,
            'USD' => isset($kurlar['USD']) ? (1 / $kurlar['USD']) : 0.0236, // 1 TL = X USD
            'EUR' => isset($kurlar['EUR']) ? (1 / $kurlar['EUR']) : 0.0204, // 1 TL = X EUR
            'AED' => isset($kurlar['AED']) ? (1 / $kurlar['AED']) : 0.0872, // 1 TL = X AED
        ];
    }
    
    /**
     * Fiyatı belirtilen para birimine dönüştür (TL'den dövize)
     */
    public static function convert($amount, $currency = 'TL')
    {
        $currency = strtoupper($currency);
        
        // TL ise direkt döndür
        if ($currency === 'TL' || $currency === 'TRY') {
            return round((float)$amount, 2);
        }
        
        $rates = self::getRates();
        
        if (!isset($rates[$currency])) {
            return round((float)$amount, 2);
        }
        
        // TL'den dövize çevir: TL * (1 / Kur) = Döviz
        $converted = (float)$amount * $rates[$currency];
        
        return round($converted, 2);
    }
    
    /**
     * Dövizden TL'ye çevir
     */
    public static function convertToTL($amount, $currency = 'USD')
    {
        $currency = strtoupper($currency);
        
        // TL ise direkt döndür
        if ($currency === 'TL' || $currency === 'TRY') {
            return round((float)$amount, 2);
        }
        
        // DovizKuruHelper kullanarak dövizden TL'ye çevir
        return round(DovizKuruHelper::tlYeCevir($amount, $currency), 2);
    }
    
    /**
     * Fiyatı formatla ve para birimi sembolü ile göster
     */
    public static function format($amount, $currency = 'TL')
    {
        $currency = strtoupper($currency);
        
        // TRY'yi TL'ye normalize et
        if ($currency === 'TRY') {
            $currency = 'TL';
        }
        
        $converted = self::convert($amount, $currency);
        $symbol = self::$symbols[$currency] ?? $currency;
        
        // TL için Türk formatı, diğerleri için uluslararası format
        if ($currency === 'TL') {
            return number_format($converted, 2, ',', '.') . ' ' . $symbol;
        } else {
            return $symbol . ' ' . number_format($converted, 2, '.', ',');
        }
    }
    
    /**
     * Mevcut para birimini al
     */
    public static function getCurrency()
    {
        return session('currency', 'TL');
    }
    
    /**
     * Para birimini ayarla
     */
    public static function setCurrency($currency)
    {
        $currency = strtoupper($currency);
        $availableCurrencies = self::getAvailableCurrencies();
        
        if (in_array($currency, $availableCurrencies)) {
            // TL ve TRY'yi normalize et
            if ($currency === 'TRY') {
                $currency = 'TL';
            }
            session(['currency' => $currency]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Tüm desteklenen para birimlerini al
     */
    public static function getAvailableCurrencies()
    {
        return ['TL', 'TRY', 'USD', 'EUR', 'AED'];
    }
    
    /**
     * TCMB'den güncel kurları al (debug/test için)
     */
    public static function getCurrentRates()
    {
        return DovizKuruHelper::tcmbKurlariCek();
    }
    
    /**
     * Para birimi sembolünü al
     */
    public static function getSymbol($currency = null)
    {
        $currency = $currency ? strtoupper($currency) : self::getCurrency();
        return self::$symbols[$currency] ?? $currency;
    }
}







