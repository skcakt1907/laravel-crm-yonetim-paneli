<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FaturaService
{
    /**
     * Otomatik fatura kes
     */
    public function otomatikFaturaKes($satilanId, $uyeId, $tutar, $baslik, $aciklama = '', $odemeYontemi = null)
    {
        try {
            // Fatura ayarlarını kontrol et
            $ayarlar = DB::table('ayarlar')->first();
            if (!$ayarlar || !($ayarlar->fatura_otomatik_kes ?? true)) {
                Log::info('Otomatik fatura kesme kapalı', ['satilan_id' => $satilanId]);
                return null;
            }

            // Fatura numarası oluştur
            $faturaNo = $this->faturaNoOlustur();

            // KDV hesapla
            $kdvOrani = $ayarlar->kdv ?? 0;
            $kdv = ($tutar * $kdvOrani) / 100;
            $toplam = $tutar + $kdv;

            // Fatura oluştur
            $faturaId = DB::table('faturalar')->insertGetId([
                'fatura_no' => $faturaNo,
                'uyeid' => $uyeId,
                'baslik' => $baslik,
                'aciklama' => $aciklama,
                'tutar' => $tutar,
                'kdv' => $kdv,
                'toplam' => $toplam,
                'durum' => 1, // Onaylanmış olarak oluştur
                'tarih' => now(),
                'bitis_tarih' => now(),
                'odenen_tarih' => now(),
                'odeme_yontemi' => $odemeYontemi ?? 'Satış Onayı',
                'hizmet' => $satilanId,
                'spno' => 'SAT-' . $satilanId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Otomatik fatura kesildi', [
                'fatura_id' => $faturaId,
                'fatura_no' => $faturaNo,
                'satilan_id' => $satilanId,
                'uye_id' => $uyeId,
                'tutar' => $tutar,
            ]);

            return $faturaId;
        } catch (\Exception $e) {
            Log::error('Fatura kesme hatası', [
                'satilan_id' => $satilanId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fatura numarası oluştur (YIL-SIRA formatında)
     */
    private function faturaNoOlustur()
    {
        $yil = Carbon::now()->format('Y');
        
        // Bu yılın son fatura numarasını bul
        $sonFatura = DB::table('faturalar')
            ->where('fatura_no', 'LIKE', $yil . '-%')
            ->orderBy('fatura_no', 'desc')
            ->first();

        if ($sonFatura && $sonFatura->fatura_no) {
            // Son numaradan sıra numarasını çıkar
            $parts = explode('-', $sonFatura->fatura_no);
            $sira = isset($parts[1]) ? (int)$parts[1] : 0;
            $sira++;
        } else {
            $sira = 1;
        }

        // 6 haneli sıra numarası (001, 002, ...)
        $faturaNo = $yil . '-' . str_pad($sira, 6, '0', STR_PAD_LEFT);

        // Benzersizlik kontrolü
        while (DB::table('faturalar')->where('fatura_no', $faturaNo)->exists()) {
            $sira++;
            $faturaNo = $yil . '-' . str_pad($sira, 6, '0', STR_PAD_LEFT);
        }

        return $faturaNo;
    }

    /**
     * Fatura ayarlarını getir
     */
    public function getFaturaAyarlari()
    {
        return DB::table('ayarlar')->first();
    }
}
