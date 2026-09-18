<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KuponService
{
    /**
     * Kupon doğrula ve indirim hesapla
     */
    public static function validateAndApply($kod, $toplam_tutar)
    {
        $kod = strtoupper(trim($kod));
        
        // Önce ana kuponlar tablosunda ara
        $kupon = DB::table('kuponlar')
            ->where('kod', $kod)
            ->first();
        
        // Ana tabloda bulunamadıysa bayi promosyon kodlarında ara
        if (!$kupon && Schema::hasTable('bayi_promosyon_kodlar')) {
            return self::validateBayiPromoKod($kod, $toplam_tutar);
        }
        
        if (!$kupon) {
            return [
                'success' => false,
                'message' => __('messages.invalid_coupon_code'),
                'indirim' => 0
            ];
        }
        
        // Durum kontrolü
        if (!isset($kupon->durum) || $kupon->durum != 1) {
            return [
                'success' => false,
                'message' => 'Bu kupon aktif değil!',
                'indirim' => 0
            ];
        }
        
        // Tarih kontrolü
        $now = now();
        
        if (isset($kupon->baslangic_tarih) && $kupon->baslangic_tarih) {
            if ($now->lt($kupon->baslangic_tarih)) {
                return [
                    'success' => false,
                    'message' => 'Bu kupon henüz geçerli değil!',
                    'indirim' => 0
                ];
            }
        }
        
        if (isset($kupon->bitis_tarih) && $kupon->bitis_tarih) {
            if ($now->gt($kupon->bitis_tarih)) {
                return [
                    'success' => false,
                    'message' => 'Bu kuponun süresi dolmuş!',
                    'indirim' => 0
                ];
            }
        }
        
        // Kullanım limiti kontrolü
        if (isset($kupon->kullanim_limiti) && $kupon->kullanim_limiti) {
            $kullanim_sayisi = $kupon->kullanim_sayisi ?? 0;
            if ($kullanim_sayisi >= $kupon->kullanim_limiti) {
                return [
                    'success' => false,
                    'message' => 'Bu kuponun kullanım limiti dolmuş!',
                    'indirim' => 0
                ];
            }
        }
        
        // İndirim hesapla
        $indirim = 0;
        $tip = $kupon->tip ?? 'yuzde';
        $indirim_miktari = (float) ($kupon->indirim ?? 0);
        
        if ($tip == 'tutar') {
            // Sabit tutar indirimi
            $indirim = min($indirim_miktari, $toplam_tutar);
        } else {
            // Yüzde indirimi
            $indirim = ($toplam_tutar * $indirim_miktari) / 100;
        }
        
        return [
            'success' => true,
            'message' => 'Kupon başarıyla uygulandı!',
            'indirim' => round($indirim, 2),
            'kupon' => $kupon
        ];
    }
    
    /**
     * Kupon kullanım sayısını artır
     */
    public static function incrementUsage($kod)
    {
        $kod = strtoupper($kod);
        
        // Önce ana tabloda dene
        $updated = DB::table('kuponlar')
            ->where('kod', $kod)
            ->increment('kullanim_sayisi', 1);
        
        // Ana tabloda yoksa bayi promo kodlarında dene
        if (!$updated && Schema::hasTable('bayi_promosyon_kodlar')) {
            DB::table('bayi_promosyon_kodlar')
                ->where('kod', $kod)
                ->increment('kullanim_sayisi', 1);
        }
    }
    
    /**
     * Bayi promosyon kodunu doğrula ve indirim hesapla
     */
    private static function validateBayiPromoKod($kod, $toplam_tutar)
    {
        $promo = DB::table('bayi_promosyon_kodlar')
            ->where('kod', $kod)
            ->first();
        
        if (!$promo) {
            return [
                'success' => false,
                'message' => __('messages.invalid_coupon_code'),
                'indirim' => 0
            ];
        }
        
        // Aktif mi kontrolü
        if (!isset($promo->durum) || $promo->durum != 1) {
            return [
                'success' => false,
                'message' => __('messages.coupon_not_active'),
                'indirim' => 0
            ];
        }
        
        // Admin onayı kontrolü
        if (!isset($promo->onay_durumu) || $promo->onay_durumu != 1) {
            return [
                'success' => false,
                'message' => __('messages.coupon_pending_approval'),
                'indirim' => 0
            ];
        }
        
        // Bitiş tarihi kontrolü
        if (isset($promo->bitis_tarihi) && $promo->bitis_tarihi) {
            if (now()->gt($promo->bitis_tarihi)) {
                return [
                    'success' => false,
                    'message' => __('messages.coupon_expired'),
                    'indirim' => 0
                ];
            }
        }
        
        // Kullanım limiti kontrolü
        if (isset($promo->kullanim_limiti) && $promo->kullanim_limiti) {
            $kullanim_sayisi = $promo->kullanim_sayisi ?? 0;
            if ($kullanim_sayisi >= $promo->kullanim_limiti) {
                return [
                    'success' => false,
                    'message' => __('messages.coupon_usage_limit_reached'),
                    'indirim' => 0
                ];
            }
        }
        
        // İndirim hesapla
        $indirim = 0;
        $tip = $promo->indirim_tipi ?? 'yuzde';
        $indirim_miktari = (float) ($promo->indirim_miktari ?? 0);
        
        if ($tip == 'tutar') {
            // Sabit tutar indirimi
            $indirim = min($indirim_miktari, $toplam_tutar);
        } else {
            // Yüzde indirimi
            $indirim = ($toplam_tutar * $indirim_miktari) / 100;
        }
        
        // Bayi bilgisini al
        $bayi = DB::table('bayiler')->where('id', $promo->bayi_id)->first();
        $bayi_adi = $bayi ? ($bayi->firma_adi ?? $bayi->bayi_kodu ?? 'Bayi') : 'Bayi';
        
        return [
            'success' => true,
            'message' => __('messages.coupon_applied_successfully'),
            'indirim' => round($indirim, 2),
            'kupon' => $promo,
            'bayi_id' => $promo->bayi_id,
            'bayi_adi' => $bayi_adi
        ];
    }
}


