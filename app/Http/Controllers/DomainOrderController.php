<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DomainOrder;
use App\Services\ResellerClubService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DomainOrderController extends Controller
{
  protected $resellerClub;
  
  public function __construct(ResellerClubService $resellerClub)
  {
    $this->resellerClub = $resellerClub;
  }
  
  /**
   * Domain sorgulama
   * GET /domain/check?domain=example.com
   */
  public function check(Request $request)
  {
    $request->validate([
      'domain' => 'required|string|max:255',
    ]);
    
    $domain = strtolower(trim($request->domain));
    
    // Domain format kontrolü
    if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $domain)) {
      return response()->json([
        'success' => false,
        'message' => 'Geçersiz domain formatı',
        'available' => false,
      ], 400);
    }
    
    // ResellerClub aktif mi kontrol et
    if (!config('services.resellerclub.enabled')) {
      // ResellerClub kapalıysa, veritabanından fiyat al
      $price = $this->getDomainPriceFromDB($domain);
      
      return response()->json([
        'success' => true,
        'available' => true, // Varsayılan olarak müsait göster
        'domain' => $domain,
        'price' => $price,
        'message' => 'ResellerClub API aktif değil, fiyat veritabanından alındı',
      ]);
    }
    
    try {
      $result = $this->resellerClub->checkAvailability($domain);
      
      if ($result['success']) {
        return response()->json([
          'success' => true,
          'available' => $result['available'] ?? false,
          'domain' => $result['domain'] ?? $domain,
          'price' => $result['price'] ?? $this->getDomainPriceFromDB($domain),
        ]);
      }
      
      return response()->json([
        'success' => false,
        'message' => $result['message'] ?? 'Domain kontrol edilemedi',
        'available' => false,
      ], 500);
      
    } catch (\Exception $e) {
      Log::error('Domain Check Error: ' . $e->getMessage());
      
      return response()->json([
        'success' => false,
        'message' => 'Domain kontrol edilirken hata oluştu',
        'available' => false,
      ], 500);
    }
  }
  
  /**
   * Domain sepete ekle
   */
  public function sepeteEkle(Request $request)
  {
    $request->validate([
      'domain' => 'required|string|max:255',
      'years' => 'required|integer|min:1|max:10',
      'price' => 'required|numeric|min:0',
    ]);
    
    if (!Auth::guard('uye')->check()) {
      return response()->json([
        'success' => false,
        'message' => 'Sepete eklemek için giriş yapmalısınız',
      ], 401);
    }
    
    $user_id = Auth::guard('uye')->id();
    $domain = strtolower(trim($request->domain));
    $years = $request->years;
    $price = $request->price;
    
    try {
      // Önce domain müsait mi kontrol et
      $check = $this->check(new Request(['domain' => $domain]));
      $check_data = json_decode($check->getContent(), true);
      
      if (!$check_data['available']) {
        return response()->json([
          'success' => false,
          'message' => 'Bu domain müsait değil',
        ], 400);
      }
      
      // Sepette zaten var mı kontrol et
      $existing = DomainOrder::where('user_id', $user_id)
        ->where('domain', $domain)
        ->whereIn('status', ['pending', 'paid'])
        ->first();
      
      if ($existing) {
        return response()->json([
          'success' => false,
          'message' => 'Bu domain zaten sepetinizde',
        ], 400);
      }
      
      // DomainOrder oluştur
      $order = DomainOrder::create([
        'user_id' => $user_id,
        'domain' => $domain,
        'price' => $price,
        'years' => $years,
        'status' => DomainOrder::STATUS_PENDING,
      ]);
      
      // ========== Eski sepet sistemine de ekle (Schema-aware, 29 Mayıs 2026 fix) ==========
      // Sepet tablosu kolonları: id, urun_id, urun_tipi, user_id, who, name, fiyat, miktar, tutar
      $sepet_data = [];

      // User ID
      if (Schema::hasColumn('sepet', 'user_id')) {
        $sepet_data['user_id'] = $user_id;
      } elseif (Schema::hasColumn('sepet', 'uyeid')) {
        $sepet_data['uyeid'] = $user_id;
      }

      // urun_id (Domain'in hash'i — büyük sayı ama sepet için yeterli)
      if (Schema::hasColumn('sepet', 'urun_id')) {
        $sepet_data['urun_id'] = abs(crc32($domain));
      }

      // ÜRÜN TİPİ — KRİTİK: domain olarak işaretle!
      if (Schema::hasColumn('sepet', 'urun_tipi')) {
        $sepet_data['urun_tipi'] = 'domain';
      } elseif (Schema::hasColumn('sepet', 'tipi')) {
        $sepet_data['tipi'] = 0;
      }

      // who kolonu (sepet sahibi)
      if (Schema::hasColumn('sepet', 'who')) {
        $sepet_data['who'] = $user_id;
      }

      // Domain ismi — birden çok kolon olabilir (uyumluluk için hepsini dene)
      if (Schema::hasColumn('sepet', 'name')) {
        $sepet_data['name'] = $domain;
      }
      if (Schema::hasColumn('sepet', 'urun_adi')) {
        $sepet_data['urun_adi'] = $domain;
      }
      if (Schema::hasColumn('sepet', 'adi')) {
        $sepet_data['adi'] = $domain;
      }
      if (Schema::hasColumn('sepet', 'domain')) {
        $sepet_data['domain'] = $domain;
      }

      // Fiyat
      if (Schema::hasColumn('sepet', 'fiyat')) {
        $sepet_data['fiyat'] = $price;
      }
      if (Schema::hasColumn('sepet', 'tutar')) {
        $sepet_data['tutar'] = $price;
      }
      if (Schema::hasColumn('sepet', 'price')) {
        $sepet_data['price'] = $price;
      }

      // Miktar
      if (Schema::hasColumn('sepet', 'miktar')) {
        $sepet_data['miktar'] = 1;
      } elseif (Schema::hasColumn('sepet', 'sure')) {
        $sepet_data['sure'] = $years;
      }

      // Tarih
      if (Schema::hasColumn('sepet', 'tarih')) {
        $sepet_data['tarih'] = now();
      }
      if (Schema::hasColumn('sepet', 'created_at')) {
        $sepet_data['created_at'] = now();
      }
      if (Schema::hasColumn('sepet', 'updated_at')) {
        $sepet_data['updated_at'] = now();
      }

      if (count($sepet_data) > 0) {
        DB::table('sepet')->insert($sepet_data);
      }
      
      return response()->json([
        'success' => true,
        'message' => 'Domain sepete eklendi',
        'order_id' => $order->id,
      ]);
      
    } catch (\Exception $e) {
      Log::error('Domain Sepete Ekle Error: ' . $e->getMessage());
      
      return response()->json([
        'success' => false,
        'message' => 'Sepete eklenirken hata oluştu',
      ], 500);
    }
  }
  
  /**
   * Ödeme başarılı callback
   */
  public function paymentSuccess(Request $request)
  {
    $request->validate([
      'order_id' => 'required|integer',
      'payment_method' => 'required|string',
      'payment_id' => 'nullable|string',
      'fatura_id' => 'nullable|integer',
    ]);
    
    $order = DomainOrder::findOrFail($request->order_id);
    
    // Sadece pending durumundaki siparişler işlenir
    if (!$order->isPending()) {
      return response()->json([
        'success' => false,
        'message' => 'Bu sipariş zaten işlenmiş',
      ], 400);
    }
    
    try {
      // Ödeme bilgilerini güncelle
      $order->update([
        'status' => DomainOrder::STATUS_PAID,
        'payment_method' => $request->payment_method,
        'payment_id' => $request->payment_id,
        'fatura_id' => $request->fatura_id,
      ]);
      
      // ResellerClub aktif değilse sadece durumu güncelle
      if (!config('services.resellerclub.enabled')) {
        return response()->json([
          'success' => true,
          'message' => 'Ödeme alındı, domain kaydı için ResellerClub API aktif edilmeli',
          'order_id' => $order->id,
        ]);
      }
      
      // Domain kayıt işlemini başlat
      // Queue kullanmak yerine direkt çalıştır (daha hızlı)
      try {
        $purchase_controller = new DomainPurchaseController($this->resellerClub);
        $purchase_request = new Request([
          'domain' => $order->domain,
          'years' => $order->years,
          'order_id' => $order->id,
          'price' => $order->price,
        ]);
        
        $result = $purchase_controller->satinAl($purchase_request);
        $result_data = json_decode($result->getContent(), true);
        
        if (!($result_data['success'] ?? false)) {
          // Hata varsa job'a gönder (retry için)
          dispatch(new \App\Jobs\RegisterDomainJob($order->id));
        }
      } catch (\Exception $e) {
        Log::error('Direct domain registration failed, using queue: ' . $e->getMessage());
        dispatch(new \App\Jobs\RegisterDomainJob($order->id));
      }
      
      return response()->json([
        'success' => true,
        'message' => 'Ödeme alındı, domain kayıt işlemi başlatıldı',
        'order_id' => $order->id,
      ]);
      
    } catch (\Exception $e) {
      Log::error('Payment Success Error: ' . $e->getMessage());
      
      $order->update([
        'status' => DomainOrder::STATUS_FAILED,
        'error_message' => $e->getMessage(),
      ]);
      
      return response()->json([
        'success' => false,
        'message' => 'İşlem sırasında hata oluştu',
      ], 500);
    }
  }
  
  /**
   * Domain durum kontrolü
   */
  public function checkStatus($order_id)
  {
    $order = DomainOrder::findOrFail($order_id);
    
    // Sadece kendi siparişini görebilir
    if ($order->user_id !== Auth::guard('uye')->id()) {
      abort(403);
    }
    
    // ResellerClub order ID varsa durumu kontrol et
    if ($order->reseller_order_id && config('services.resellerclub.enabled')) {
      try {
        $details = $this->resellerClub->getDomainDetails($order->reseller_order_id);
        
        if ($details['success']) {
          // Durumu güncelle
          if ($details['status'] === 'Active') {
            $order->update([
              'status' => DomainOrder::STATUS_ACTIVE,
              'registered_at' => $details['creation_date'] ? date('Y-m-d H:i:s', strtotime($details['creation_date'])) : null,
              'expires_at' => $details['expiry_date'] ? date('Y-m-d H:i:s', strtotime($details['expiry_date'])) : null,
            ]);
          }
          
          return response()->json([
            'success' => true,
            'order' => $order->fresh(),
            'details' => $details,
          ]);
        }
      } catch (\Exception $e) {
        Log::error('Check Status Error: ' . $e->getMessage());
      }
    }
    
    return response()->json([
      'success' => true,
      'order' => $order,
    ]);
  }
  
  /**
   * Veritabanından domain fiyatı al
   */
  private function getDomainPriceFromDB($domain)
  {
    $tld = $this->extractTld($domain);
    
    // domain_fiyatlar tablosundan al
    if (DB::getSchemaBuilder()->hasTable('domain_fiyatlar')) {
      $price = DB::table('domain_fiyatlar')
        ->where('uzanti', $tld)
        ->value('kayit_fiyat');
      
      if ($price) {
        return (float) $price;
      }
    }
    
    // alanadi tablosundan al
    $alanadi = DB::table('alanadi')->where('id', 1)->first();
    if ($alanadi) {
      $uzantilar = json_decode($alanadi->uzanti ?? '[]', true);
      $kayitlar = json_decode($alanadi->kayit ?? '[]', true);
      
      foreach ($uzantilar as $index => $uzanti) {
        if (trim($uzanti, '.') === $tld) {
          return isset($kayitlar[$index]) ? (float) $kayitlar[$index] : 0;
        }
      }
    }
    
    return 0;
  }
  
  /**
   * TLD çıkar
   */
  private function extractTld($domain)
  {
    $parts = explode('.', $domain);
    if (count($parts) > 2) {
      return $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
    }
    return $parts[count($parts) - 1];
  }
}