<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class BayiKodService
{
    /**
     * Bayi kodunu doğrula ve indirim bilgisini döndür
     * 
     * @param string $bayiKodu
     * @param float $sepetToplam
     * @return array
     */
    public static function validateAndApply(string $bayiKodu, float $sepetToplam): array
    {
        try {
            // Bayi ayarlarını al
            $ayarlar = self::getAyarlar();
            
            // Bayi sistemi aktif mi?
            if (!$ayarlar->bayi_sistemi_aktif) {
                return [
                    'success' => false,
                    'message' => 'Bayi kodu sistemi şu anda aktif değil.',
                    'indirim' => 0,
                    'bayi' => null
                ];
            }
            
            // Bayi kodunu bul
            $bayi = DB::table('bayiler')
                ->where('bayi_kodu', strtoupper(trim($bayiKodu)))
                ->where('durum', 1) // Aktif
                ->where('onay_durumu', 1) // Onaylanmış
                ->first();
            
            if (!$bayi) {
                return [
                    'success' => false,
                    'message' => 'Geçersiz bayi kodu. Lütfen kontrol edin.',
                    'indirim' => 0,
                    'bayi' => null
                ];
            }
            
            // Minimum sepet tutarı kontrolü
            $minTutar = $bayi->min_sepet_tutari ?? $ayarlar->min_sepet_tutari ?? 0;
            if ($sepetToplam < $minTutar) {
                return [
                    'success' => false,
                    'message' => 'Bu bayi kodunu kullanmak için minimum sepet tutarı: ' . number_format($minTutar, 2, ',', '.') . ' ₺',
                    'indirim' => 0,
                    'bayi' => null
                ];
            }
            
            // İndirim oranını al (bayiye özel veya varsayılan)
            $indirimOrani = $bayi->musteri_indirim_orani ?? $ayarlar->varsayilan_musteri_indirim_orani ?? 5;
            
            // İndirim tutarını hesapla
            $indirimTutari = ($sepetToplam * $indirimOrani) / 100;
            
            // Maksimum indirim kontrolü
            $maxIndirim = $ayarlar->max_musteri_indirim ?? 500;
            if ($indirimTutari > $maxIndirim) {
                $indirimTutari = $maxIndirim;
            }
            
            // Bayi bilgilerini al (isim göstermek için)
            $uyeBilgi = DB::table('uyeler')->where('id', $bayi->uye_id)->first();
            $bayiAdi = $uyeBilgi ? (($uyeBilgi->ad ?? '') . ' ' . ($uyeBilgi->soyad ?? '')) : 'Bayi';
            
            return [
                'success' => true,
                'message' => '%' . number_format($indirimOrani, 0) . ' indirim uygulandı! (Bayi: ' . trim($bayiAdi) . ')',
                'indirim' => round($indirimTutari, 2),
                'indirim_orani' => $indirimOrani,
                'bayi' => $bayi,
                'bayi_id' => $bayi->id,
                'komisyon_orani' => $bayi->komisyon_orani ?? $ayarlar->varsayilan_komisyon_orani ?? 10
            ];
            
        } catch (\Exception $e) {
            Log::error('Bayi kodu doğrulama hatası', [
                'bayi_kodu' => $bayiKodu,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Bayi kodu doğrulanırken bir hata oluştu.',
                'indirim' => 0,
                'bayi' => null
            ];
        }
    }
    
    /**
     * Bayi komisyonunu kaydet (ödeme başarılı olduktan sonra çağrılır)
     * 
     * @param int $bayiId
     * @param int|null $uyeId
     * @param int|null $faturaId
     * @param float $satisTutari
     * @param float $komisyonOrani
     * @param float $musteriIndirimTutari
     * @return bool
     */
    public static function recordCommission(
        int $bayiId, 
        ?int $uyeId, 
        ?int $faturaId, 
        float $satisTutari, 
        float $komisyonOrani, 
        float $musteriIndirimTutari = 0
    ): bool {
        try {
            // Komisyon tutarını hesapla
            $komisyonTutari = ($satisTutari * $komisyonOrani) / 100;
            
            // Bayi satış kaydı oluştur
            $data = [
                'bayi_id' => $bayiId,
                'satis_tutari' => $satisTutari,
                'komisyon_orani' => $komisyonOrani,
                'komisyon_tutari' => round($komisyonTutari, 2),
                'odendi' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Opsiyonel alanlar
            if ($uyeId) {
                $data['uye_id'] = $uyeId;
            }
            if ($faturaId) {
                $data['fatura_id'] = $faturaId;
            }
            if (Schema::hasColumn('bayi_satislar', 'musteri_indirim_tutari')) {
                $data['musteri_indirim_tutari'] = $musteriIndirimTutari;
            }
            
            DB::table('bayi_satislar')->insert($data);
            
            // Bayinin bakiyesini güncelle
            DB::table('bayiler')
                ->where('id', $bayiId)
                ->increment('toplam_kazanc', $komisyonTutari);
            
            DB::table('bayiler')
                ->where('id', $bayiId)
                ->increment('cekilebilir_bakiye', $komisyonTutari);
            
            // Bayi'ye bildirim gönder
            self::sendNotification($bayiId, $satisTutari, $komisyonTutari);
            
            Log::info('Bayi komisyon kaydı oluşturuldu', [
                'bayi_id' => $bayiId,
                'satis_tutari' => $satisTutari,
                'komisyon_tutari' => $komisyonTutari
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Bayi komisyon kayıt hatası', [
                'bayi_id' => $bayiId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Bayiye satış bildirimi gönder
     */
    private static function sendNotification(int $bayiId, float $satisTutari, float $komisyonTutari): void
    {
        try {
            if (Schema::hasTable('bayi_bildirimler')) {
                DB::table('bayi_bildirimler')->insert([
                    'bayi_id' => $bayiId,
                    'baslik' => 'Yeni Satış Komisyonu!',
                    'mesaj' => 'Bayi kodunuz kullanılarak ' . number_format($satisTutari, 2, ',', '.') . ' ₺ tutarında satış yapıldı. Komisyon kazancınız: ' . number_format($komisyonTutari, 2, ',', '.') . ' ₺',
                    'tip' => 'komisyon',
                    'okundu' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Bayi bildirim gönderilemedi', [
                'bayi_id' => $bayiId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Bayi ayarlarını getir
     * 
     * @return object
     */
    public static function getAyarlar(): object
    {
        try {
            if (Schema::hasTable('bayi_ayarlari')) {
                $ayarlar = DB::table('bayi_ayarlari')->first();
                if ($ayarlar) {
                    return $ayarlar;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Bayi ayarları alınamadı', ['error' => $e->getMessage()]);
        }
        
        // Varsayılan ayarlar
        return (object) [
            'varsayilan_komisyon_orani' => 10,
            'varsayilan_musteri_indirim_orani' => 5,
            'min_sepet_tutari' => 100,
            'max_musteri_indirim' => 500,
            'bayi_sistemi_aktif' => true,
            'yeni_bayi_otomatik_onay' => false,
        ];
    }
    
    /**
     * Session'dan bayi bilgisini al
     */
    public static function getSessionBayi(): ?array
    {
        return session('bayi_kod_bilgi');
    }
    
    /**
     * Session'a bayi bilgisini kaydet
     */
    public static function setSessionBayi(array $bilgi): void
    {
        session(['bayi_kod_bilgi' => $bilgi]);
    }
    
    /**
     * Session'dan bayi bilgisini sil
     */
    public static function clearSessionBayi(): void
    {
        session()->forget('bayi_kod_bilgi');
    }
}
