<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Models\Yonetici;
use App\Notifications\BayiYeniSatisEmail;
use App\Notifications\BayiOdemeOnayEmail;

class BildirimHelper
{
    /**
     * Yeni Satış Bildirimi Gönder
     */
    public static function yeniSatisBildirimi($bayiId, $satis)
    {
        // Bayi bildirim ayarlarını kontrol et
        $ayarlar = DB::table('bayi_bildirim_ayarlari')
            ->where('bayi_id', $bayiId)
            ->first();
        
        // Email bildirimi açıksa gönder
        if (($ayarlar->yeni_satis_email ?? 1) == 1) {
            try {
                $yonetici = Yonetici::find($bayiId);
                
                if ($yonetici && $yonetici->email) {
                    $yonetici->notify(new BayiYeniSatisEmail($satis));
                }
            } catch (\Exception $e) {
                // Hata logla (opsiyonel)
                \Log::error('Bayi email bildirimi gönderilemedi: ' . $e->getMessage());
            }
        }
        
        // Bayi bildirimler tablosuna kaydet
        DB::table('bayi_bildirimler')->insert([
            'bayi_id' => $bayiId,
            'baslik' => 'Yeni Satış!',
            'mesaj' => 'Yeni bir satış gerçekleştirdiniz. Tutar: ₺' . number_format($satis->satis_tutari, 2) . ', Komisyon: ₺' . number_format($satis->komisyon_tutari, 2),
            'tip' => 'satis',
            'okundu' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Ödeme Onay Bildirimi Gönder
     */
    public static function odemeOnayBildirimi($bayiId, $odeme)
    {
        // Bayi bildirim ayarlarını kontrol et
        $ayarlar = DB::table('bayi_bildirim_ayarlari')
            ->where('bayi_id', $bayiId)
            ->first();
        
        // Email bildirimi açıksa gönder
        if (($ayarlar->odeme_onay_email ?? 1) == 1) {
            try {
                $yonetici = Yonetici::find($bayiId);
                
                if ($yonetici && $yonetici->email) {
                    $yonetici->notify(new BayiOdemeOnayEmail($odeme));
                }
            } catch (\Exception $e) {
                \Log::error('Bayi ödeme email bildirimi gönderilemedi: ' . $e->getMessage());
            }
        }
        
        // Bayi bildirimler tablosuna kaydet
        DB::table('bayi_bildirimler')->insert([
            'bayi_id' => $bayiId,
            'baslik' => 'Ödeme Onaylandı!',
            'mesaj' => 'Ödeme talebiniz onaylandı! Tutar: ₺' . number_format($odeme->tutar, 2) . '. Ödeme 1-3 iş günü içinde hesabınıza yatırılacaktır.',
            'tip' => 'odeme',
            'okundu' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Sistem Duyurusu Gönder
     */
    public static function sistemDuyurusu($bayiId, $baslik, $mesaj)
    {
        $ayarlar = DB::table('bayi_bildirim_ayarlari')
            ->where('bayi_id', $bayiId)
            ->first();
        
        // Email bildirimi açıksa gönder
        if (($ayarlar->sistem_duyuru_email ?? 1) == 1) {
            try {
                $yonetici = Yonetici::find($bayiId);
                
                if ($yonetici && $yonetici->email) {
                    $yonetici->notify(new \Illuminate\Notifications\Messages\MailMessage([
                        'subject' => $baslik,
                        'line' => $mesaj,
                    ]));
                }
            } catch (\Exception $e) {
                \Log::error('Sistem duyurusu email gönderilemedi: ' . $e->getMessage());
            }
        }
        
        // Bayi bildirimler tablosuna kaydet
        DB::table('bayi_bildirimler')->insert([
            'bayi_id' => $bayiId,
            'baslik' => $baslik,
            'mesaj' => $mesaj,
            'tip' => 'sistem',
            'okundu' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Tüm Bayilere Duyuru Gönder
     */
    public static function tumBayilereDuyuru($baslik, $mesaj)
    {
        $bayiler = DB::table('yoneticiler')
            ->where('rol', 3) // Bayi rolü
            ->where('durum', 1)
            ->get();
        
        foreach ($bayiler as $bayi) {
            self::sistemDuyurusu($bayi->id, $baslik, $mesaj);
        }
    }
}

