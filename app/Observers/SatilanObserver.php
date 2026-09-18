<?php

namespace App\Observers;

use App\Models\Satilan;
use App\Services\FaturaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SatilanObserver
{
    protected $faturaService;

    public function __construct(FaturaService $faturaService)
    {
        $this->faturaService = $faturaService;
    }

    /**
     * Handle the Satilan "updated" event.
     */
    public function updated(Satilan $satilan): void
    {
        // Eğer durum 0'dan 1'e değiştiyse (onaylandıysa) fatura kes
        if ($satilan->isDirty('durum') && $satilan->durum == 1 && $satilan->getOriginal('durum') == 0) {
            try {
                // Fatura zaten kesilmiş mi kontrol et
                $mevcutFatura = DB::table('faturalar')
                    ->where('hizmet', $satilan->id)
                    ->where('uyeid', $satilan->uyeid)
                    ->first();

                if (!$mevcutFatura) {
                    // Fatura başlığını oluştur
                    $baslik = $this->faturaBaslikOlustur($satilan);
                    
                    // Tutarı al (tutar veya fiyat alanından)
                    $tutar = (float)($satilan->tutar ?? $satilan->fiyat ?? 0);
                    
                    // Ödeme yöntemini al
                    $odemeYontemi = $satilan->odeme_yontemi ?? 'Satış Onayı';
                    
                    // Otomatik fatura kes
                    $this->faturaService->otomatikFaturaKes(
                        $satilan->id,
                        $satilan->uyeid,
                        $tutar,
                        $baslik,
                        $satilan->aciklama ?? '',
                        $odemeYontemi
                    );
                }
            } catch (\Exception $e) {
                Log::error('Satış onaylandığında fatura kesme hatası', [
                    'satilan_id' => $satilan->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Fatura başlığını oluştur
     */
    private function faturaBaslikOlustur($satilan)
    {
        $baslik = '';
        
        // Hosting satışı
        if (isset($satilan->hosting) && $satilan->hosting > 0) {
            $baslik = $satilan->hosting_baslik ?? 'Hosting Hizmeti';
            if ($satilan->domain) {
                $baslik .= ' (' . $satilan->domain . ')';
            }
        }
        // Web paket satışı
        elseif (isset($satilan->paket) && $satilan->paket > 0) {
            $baslik = $satilan->paket_baslik ?? 'Web Paketi';
            if ($satilan->domain) {
                $baslik .= ' (' . $satilan->domain . ')';
            }
        }
        // Domain satışı
        elseif ($satilan->domain) {
            $baslik = $satilan->domain;
            if (isset($satilan->zmnt) && $satilan->zmnt > 0) {
                $baslik .= ' (' . $satilan->zmnt . ' Yıllık)';
            }
        }
        // Genel
        else {
            $baslik = $satilan->baslik ?? 'Satış';
        }
        
        return $baslik;
    }
}
