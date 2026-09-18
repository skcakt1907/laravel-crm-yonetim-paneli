<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * VERİ ARAÇLARI — SSH'sız bakım ekranı.
 *
 * Sunucuda SSH erişimi yok; `php artisan ...` komutları elle çalıştırılamıyor.
 * Bu ekran, seçili bakım komutlarını panelden çalıştırıp çıktısını gösterir.
 *
 * GÜVENLİK:
 *  - Yalnızca aşağıdaki BEYAZ LİSTEDEKİ komutlar çalışır; serbest komut YOK.
 *  - Parametreler de sabit; kullanıcıdan komut satırı alınmaz.
 *  - Varsayılan KURU ÇALIŞMA; yazma için ayrıca onay kutusu işaretlenmeli.
 *  - Her çalıştırma log'a düşer (kim, hangi komut, kuru mu).
 *  - Ekran admin.auth + rol middleware'i altında.
 */
class VeriAraclariController extends Controller
{
    /**
     * Çalıştırılabilir komutlar.
     * 'yazar' => true olanlar veriyi değiştirir, ek onay ister.
     */
    private const ARACLAR = [
        'musteri-birlestir' => [
            'komut'    => 'musteri:birlestir',
            'ad'       => 'Mükerrer Müşterileri Birleştir',
            'aciklama' => 'Aynı üyeye bağlı birden fazla CRM kartını tek karta indirir. '
                        . 'Kaybeden kayıt SİLİNMEZ, işaretlenir; verisi kazanan karta taşınır.',
            'yazar'    => true,
            'ikon'     => 'git-merge',
        ],
        'uye-crm-karti' => [
            'komut'    => 'uye:crm-karti-ac',
            'ad'       => 'Üye ↔ CRM Bağlarını Onar',
            'aciklama' => 'Kopuk bağları kurar ve CRM kartı olmayan üyeler için kart açar. '
                        . 'Personel ve test hesapları atlanır.',
            'yazar'    => true,
            'ikon'     => 'link',
        ],
        'sahte-temizle' => [
            'komut'    => 'musteri:sahte-temizle',
            'ad'       => 'Sahte/Test Kayıtları Pasife Çek',
            'aciklama' => 'Demo verisi ve test kayıtlarını listeden gizler. Silmez, pasife çeker; '
                        . 'verisi olan hiçbir kayda dokunmaz.',
            'yazar'    => true,
            'ikon'     => 'eraser',
        ],
        'proforma' => [
            'komut'    => 'proforma:uret',
            'ad'       => 'Proforma Fatura Üret',
            'aciklama' => 'Bitişine 5–30 gün kalan hizmetler için proforma keser, '
                        . 'Bekleyen Faturalar ekranına düşer.',
            'yazar'    => true,
            'kuruBayrak' => '--kuru-calisma',
            'ikon'     => 'file-plus',
        ],
        'gunluk-rapor' => [
            'komut'    => 'finans:gunluk-rapor',
            'ad'       => 'Günlük Finans Raporunu Gönder',
            'aciklama' => 'Bugünün gelir-gider raporunu muhasebeye e-posta ile gönderir. '
                        . 'KURU ÇALIŞMASI YOKTUR — çalıştırırsan mail gider.',
            'yazar'    => true,
            'kurusuz'  => true,
            'ikon'     => 'mail',
        ],
    ];

    public function index()
    {
        return view('admin.bakim.veri-araclari', ['araclar' => self::ARACLAR]);
    }

    public function calistir(Request $request)
    {
        $anahtar = (string) $request->input('arac');
        $arac    = self::ARACLAR[$anahtar] ?? null;

        if (!$arac) {
            return back()->with('error', 'Bilinmeyen araç.');
        }

        // Yazma için açık onay şart
        $uygula = $request->boolean('uygula');
        if ($uygula && $request->input('onay') !== 'ANLADIM') {
            return back()->with('error', 'Yazma modu için onay kutusuna ANLADIM yazmalısın.');
        }

        // Parametreler beyaz listeden — kullanıcıdan komut satırı ALINMAZ
        $parametre = [];
        if (!empty($arac['kurusuz'])) {
            // Kuru çalışması olmayan komut: sadece uygula seçiliyse çalışsın
            if (!$uygula) {
                return back()->with('error', $arac['ad'] . ' için kuru çalışma yok; yazma onayı vermelisin.');
            }
        } elseif ($uygula) {
            $parametre = ['--uygula' => true];
        } elseif (!empty($arac['kuruBayrak'])) {
            $parametre = [$arac['kuruBayrak'] => true];
        }

        Log::info('Veri aracı çalıştırıldı', [
            'arac'     => $anahtar,
            'komut'    => $arac['komut'],
            'uygula'   => $uygula,
            'yonetici' => session('admin_id'),
        ]);

        try {
            @set_time_limit(300);
            Artisan::call($arac['komut'], $parametre);
            $cikti = Artisan::output();
        } catch (\Throwable $e) {
            Log::error('Veri aracı hatası', ['arac' => $anahtar, 'hata' => $e->getMessage()]);
            return back()->with('error', 'Çalıştırılamadı: ' . $e->getMessage());
        }

        return back()
            ->with('cikti', trim($cikti))
            ->with('ciktiBaslik', $arac['ad'] . ($uygula ? ' — UYGULANDI' : ' — kuru çalışma'))
            ->with('success', $uygula ? 'Komut çalıştı ve değişiklikler yazıldı.' : 'Kuru çalışma tamamlandı, hiçbir şey değişmedi.');
    }
}
