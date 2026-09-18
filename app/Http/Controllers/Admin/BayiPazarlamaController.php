<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BayiPazarlamaController extends Controller
{
    /**
     * Promosyon Kodları
     */
    public function promosyonKodlar()
    {
        $bayiId = session('admin_id');
        
        // Promosyon kodları (tablo yoksa boş döndür)
        $kodlar = collect([]);
        
        try {
            if (Schema::hasTable('bayi_promosyon_kodlar')) {
                $kodlar = DB::table('bayi_promosyon_kodlar')
                    ->where('bayi_id', $bayiId)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {
            // Tablo yoksa boş bırak
        }
        
        return view('admin.bayi.pazarlama.promosyon-kodlar', compact('kodlar'));
    }
    
    /**
     * Promosyon Kodu Oluştur
     */
    public function promosyonOlustur(Request $request)
    {
        $request->validate([
            'kod' => 'required|unique:bayi_promosyon_kodlar,kod',
            'indirim_tipi' => 'required|in:yuzde,tutar',
            'indirim_miktari' => 'required|numeric|min:0',
            'kullanim_limiti' => 'nullable|integer|min:1',
            'bitis_tarihi' => 'nullable|date',
        ]);
        
        $bayiId = session('admin_id');
        $kod = strtoupper($request->kod);
        
        // Bayi bilgisini al
        $bayi = DB::table('bayiler')->where('yonetici_id', $bayiId)->first();
        $bayiAdi = $bayi ? ($bayi->firma_adi ?? $bayi->bayi_kodu ?? 'Bayi #' . $bayiId) : 'Bayi #' . $bayiId;
        
        // Promo kodu oluştur (onay_durumu = 0: Beklemede)
        $promoId = DB::table('bayi_promosyon_kodlar')->insertGetId([
            'bayi_id' => $bayiId,
            'kod' => $kod,
            'indirim_tipi' => $request->indirim_tipi,
            'indirim_miktari' => $request->indirim_miktari,
            'kullanim_limiti' => $request->kullanim_limiti,
            'bitis_tarihi' => $request->bitis_tarihi,
            'durum' => 1,
            'onay_durumu' => 0, // Beklemede
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Admin'e bildirim gönder
        if (Schema::hasTable('admin_bildirimler')) {
            DB::table('admin_bildirimler')->insert([
                'tip' => 'promo_kod_onay',
                'baslik' => __('messages.new_promo_code_request'),
                'mesaj' => $bayiAdi . ' ' . __('messages.reseller_created_promo') . ': ' . $kod,
                'ilgili_id' => $promoId,
                'ilgili_tablo' => 'bayi_promosyon_kodlar',
                'okundu' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        return redirect()->route('admin.bayi.promosyon.kodlar')->with('success', __('messages.promo_code_created_pending'));
    }
    
    /**
     * Promosyon Kodu Sil
     */
    public function promosyonSil($id)
    {
        DB::table('bayi_promosyon_kodlar')
            ->where('id', $id)
            ->where('bayi_id', session('admin_id'))
            ->delete();
        
        return redirect()->route('admin.bayi.promosyon.kodlar')->with('success', 'Promosyon kodu silindi!');
    }
    
    /**
     * Kampanyalar
     */
    public function kampanyalar()
    {
        $bayiId = session('admin_id');
        
        // Tablo kontrolü
        if (!Schema::hasTable('kampanyalar')) {
            $kampanyalar = collect([]);
            return view('admin.bayi.pazarlama.kampanyalar', compact('kampanyalar'));
        }
        
        // Aktif kampanyalar
        $kampanyalar = DB::table('kampanyalar')
            ->where('durum', 1)
            ->where(function($query) use ($bayiId) {
                $query->whereNull('bayi_id')
                      ->orWhere('bayi_id', $bayiId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('admin.bayi.pazarlama.kampanyalar', compact('kampanyalar'));
    }
    
    /**
     * Email/SMS Şablonları
     */
    public function emailSablonlar()
    {
        $bayiId = session('admin_id');
        
        // Hazır şablonlar
        $sablonlar = [
            [
                'id' => 1,
                'baslik' => 'Hoşgeldiniz Mesajı',
                'icerik' => 'Merhaba {musteri_adi}, sistemimize hoş geldiniz!',
            ],
            [
                'id' => 2,
                'baslik' => 'Ödeme Hatırlatma',
                'icerik' => 'Sayın {musteri_adi}, {fatura_no} numaralı faturanızın ödeme tarihi yaklaşıyor.',
            ],
            [
                'id' => 3,
                'baslik' => 'Yenileme Hatırlatma',
                'icerik' => 'Merhaba {musteri_adi}, {hizmet_adi} hizmetinizin yenileme tarihi: {yenileme_tarihi}',
            ],
        ];
        
        // Müşteri listesi
        $musteriler = DB::table('bayi_satislar')
            ->join('faturalar', 'bayi_satislar.fatura_id', '=', 'faturalar.id')
            ->join('uyeler', 'faturalar.uyeid', '=', 'uyeler.id')
            ->where('bayi_satislar.bayi_id', $bayiId)
            ->select('uyeler.id', 'uyeler.ad', 'uyeler.email')
            ->distinct()
            ->get();
        
        return view('admin.bayi.pazarlama.email-sablonlar', compact('sablonlar', 'musteriler'));
    }
    
    /**
     * Email Gönder
     */
    public function emailGonder(Request $request)
    {
        $request->validate([
            'musteri_id' => 'required|array',
            'konu' => 'required|string|max:255',
            'mesaj' => 'required|string',
        ]);
        
        $musteriler = DB::table('uyeler')
            ->whereIn('id', $request->musteri_id)
            ->where('durum', 1)
            ->get();
        
        $gonderilen = 0;
        $hata = 0;
        
        foreach ($musteriler as $musteri) {
            try {
                // Mesaj içindeki placeholder'ları değiştir
                $mesaj = $request->mesaj;
                $mesaj = str_replace('{musteri_adi}', $musteri->ad ?? 'Müşteri', $mesaj);
                $mesaj = str_replace('{musteri_email}', $musteri->email ?? '', $mesaj);
                $mesaj = str_replace('{musteri_telefon}', $musteri->telefon ?? '', $mesaj);
                
                // Email gönder
                Mail::raw($mesaj, function ($message) use ($musteri, $request) {
                    $message->to($musteri->email)
                            ->subject($request->konu);
                });
                
                $gonderilen++;
            } catch (\Exception $e) {
                \Log::error('Email gönderme hatası: ' . $e->getMessage(), [
                    'musteri_id' => $musteri->id,
                    'email' => $musteri->email,
                ]);
                $hata++;
            }
        }
        
        $mesaj = "{$gonderilen} email başarıyla gönderildi.";
        if ($hata > 0) {
            $mesaj .= " {$hata} email gönderilemedi.";
        }
        
        return back()->with($hata > 0 ? 'warning' : 'success', $mesaj);
    }
}

