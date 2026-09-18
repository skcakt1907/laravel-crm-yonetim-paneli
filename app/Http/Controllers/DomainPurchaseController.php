<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ResellerClubService;
use App\Models\DomainOrder;
use Illuminate\Support\Facades\Log;

class DomainPurchaseController extends Controller
{
  protected $resellerClub;
  
  public function __construct(ResellerClubService $resellerClub)
  {
    $this->resellerClub = $resellerClub;
    $this->middleware('auth:uye');
  }
  
  /**
   * Domain satın alma işlemi (ödeme sonrası)
   */
  public function satinAl(Request $request)
  {
    $request->validate([
      'domain' => 'required|string',
      'years' => 'required|integer|min:1|max:10',
      'siparis_id' => 'nullable|integer', // DomainOrder ID veya Fatura ID
      'order_id' => 'nullable|integer', // DomainOrder ID
    ]);
    
    $user = Auth::guard('uye')->user();
    $domain = strtolower(trim($request->domain));
    $years = $request->years;
    $order_id = $request->order_id ?? $request->siparis_id;
    
    // DomainOrder bul
    $domain_order = null;
    if ($order_id) {
      $domain_order = DomainOrder::where('id', $order_id)
        ->where('user_id', $user->id)
        ->first();
    }
    
    if (!$domain_order) {
      // DomainOrder yoksa oluştur
      $domain_order = DomainOrder::create([
        'user_id' => $user->id,
        'domain' => $domain,
        'price' => $request->price ?? 0,
        'years' => $years,
        'status' => DomainOrder::STATUS_PAID,
      ]);
    }
    
    // ResellerClub aktif mi kontrol et
    if (!config('services.resellerclub.enabled')) {
      Log::error('ResellerClub is not enabled');
      return response()->json([
        'success' => false,
        'message' => 'Domain satın alma servisi şu anda aktif değil.',
      ], 400);
    }
    
    try {
      // Önce domain müsait mi kontrol et
      $tld = $this->extractTld($domain);
      $check = $this->resellerClub->checkAvailability($domain, [$tld]);
      
      if (!$check['success'] || !($check['available'] ?? false)) {
        return response()->json([
          'success' => false,
          'message' => 'Domain artık müsait değil veya kontrol edilemedi.',
        ], 400);
      }
      
      // Müşteri ID'sini al veya oluştur
      $customer_id = $this->getOrCreateCustomer($user);
      
      if (!$customer_id) {
        return response()->json([
          'success' => false,
          'message' => 'Müşteri bilgileri oluşturulamadı.',
        ], 500);
      }
      
      // İletişim bilgilerini al veya oluştur
      $contact_ids = $this->getOrCreateContacts($user, $customer_id);
      
      if (empty($contact_ids)) {
        return response()->json([
          'success' => false,
          'message' => 'İletişim bilgileri oluşturulamadı.',
        ], 500);
      }
      
      // Nameserver'ları al (varsayılan veya kullanıcıdan)
      $nameservers = $this->getNameservers($request);
      
      // Domain kayıt işlemi
      $result = $this->resellerClub->registerDomain(
        $domain,
        $years,
        $customer_id,
        $contact_ids,
        $nameservers
      );
      
      if ($result['success']) {
        // Başarılı - DomainOrder'ı güncelle
        $domain_order->update([
          'status' => DomainOrder::STATUS_ACTIVE,
          'reseller_order_id' => $result['order_id'] ?? null,
          'registered_at' => now(),
          'expires_at' => now()->addYears($years),
        ]);
        
        // Eski satilanlar tablosunu da güncelle (uyumluluk için)
        if ($request->has('siparis_id') && $request->siparis_id) {
          DB::table('satilanlar')
            ->where('id', $request->siparis_id)
            ->update([
              'domain_durum' => 'kayitli',
              'domain_order_id' => $result['order_id'] ?? null,
              'domain_kayit_tarihi' => now(),
              'updated_at' => now(),
            ]);
        }
        
        // Domain kaydını veritabanına ekle
        $this->saveDomainRecord($user->id, $domain, $years, $result['order_id'] ?? null, $domain_order->id);
        
        return response()->json([
          'success' => true,
          'message' => 'Domain başarıyla kaydedildi!',
          'order_id' => $result['order_id'],
          'domain_order_id' => $domain_order->id,
        ]);
      } else {
        // Hata - DomainOrder'ı güncelle
        $domain_order->update([
          'status' => DomainOrder::STATUS_FAILED,
          'error_message' => $result['message'] ?? 'Bilinmeyen hata',
        ]);
        
        // Eski satilanlar tablosunu da güncelle
        if ($request->has('siparis_id') && $request->siparis_id) {
          DB::table('satilanlar')
            ->where('id', $request->siparis_id)
            ->update([
              'domain_durum' => 'hata',
              'domain_hata' => $result['message'] ?? 'Bilinmeyen hata',
              'updated_at' => now(),
            ]);
        }
        
        return response()->json([
          'success' => false,
          'message' => $result['message'] ?? 'Domain kayıt işlemi başarısız oldu.',
        ], 500);
      }
      
    } catch (\Exception $e) {
      Log::error('Domain Purchase Error: ' . $e->getMessage(), [
        'domain' => $domain,
        'user_id' => $user->id,
        'trace' => $e->getTraceAsString(),
      ]);
      
      return response()->json([
        'success' => false,
        'message' => 'Domain satın alma işlemi sırasında bir hata oluştu.',
      ], 500);
    }
  }
  
  /**
   * TLD çıkar
   */
  private function extractTld($domain)
  {
    $parts = explode('.', $domain);
    if (count($parts) > 2) {
      // .com.tr gibi çift uzantılar
      return $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
    }
    return $parts[count($parts) - 1];
  }
  
  /**
   * Müşteri ID'sini al veya oluştur
   */
  private function getOrCreateCustomer($user)
  {
    // Önce veritabanında var mı kontrol et
    $customer = DB::table('resellerclub_customers')
      ->where('uye_id', $user->id)
      ->first();
    
    if ($customer && $customer->customer_id) {
      return $customer->customer_id;
    }
    
    // Yeni müşteri oluştur
    $username = $user->email;
    $passwd = Str::random(12); // Güvenli şifre oluştur
    
    $result = $this->resellerClub->createCustomer(
      $username,
      $passwd,
      ($user->ad ?? '') . ' ' . ($user->soyad ?? ''),
      $user->firma ?? '',
      $user->email,
      $user->adres ?? '',
      $user->il ?? 'Istanbul',
      $user->ilce ?? '',
      $user->ulke ?? 'TR',
      $user->pkodu ?? '34000',
      $this->getPhoneCode($user->telefon ?? ''),
      $this->getPhoneNumber($user->telefon ?? '')
    );
    
    if ($result['success'] && isset($result['customer_id'])) {
      // Veritabanına kaydet
      DB::table('resellerclub_customers')->insert([
        'uye_id' => $user->id,
        'customer_id' => $result['customer_id'],
        'username' => $username,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      
      return $result['customer_id'];
    }
    
    return null;
  }
  
  /**
   * İletişim bilgilerini al veya oluştur
   */
  private function getOrCreateContacts($user, $customer_id)
  {
    // Önce veritabanında var mı kontrol et
    $contact = DB::table('resellerclub_contacts')
      ->where('uye_id', $user->id)
      ->where('customer_id', $customer_id)
      ->first();
    
    if ($contact && $contact->contact_id) {
      return [
        'registrant' => $contact->contact_id,
        'admin' => $contact->contact_id,
        'tech' => $contact->contact_id,
        'billing' => $contact->contact_id,
      ];
    }
    
    // Yeni iletişim bilgisi oluştur
    $result = $this->resellerClub->createContact(
      $customer_id,
      ($user->ad ?? '') . ' ' . ($user->soyad ?? ''),
      $user->firma ?? '',
      $user->email,
      $user->adres ?? '',
      $user->il ?? 'Istanbul',
      $user->ilce ?? '',
      $user->ulke ?? 'TR',
      $user->pkodu ?? '34000',
      $this->getPhoneCode($user->telefon ?? ''),
      $this->getPhoneNumber($user->telefon ?? ''),
      'Contact'
    );
    
    if ($result['success'] && isset($result['contact_id'])) {
      // Veritabanına kaydet
      DB::table('resellerclub_contacts')->insert([
        'uye_id' => $user->id,
        'customer_id' => $customer_id,
        'contact_id' => $result['contact_id'],
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      
      return [
        'registrant' => $result['contact_id'],
        'admin' => $result['contact_id'],
        'tech' => $result['contact_id'],
        'billing' => $result['contact_id'],
      ];
    }
    
    return [];
  }
  
  /**
   * Nameserver'ları al
   */
  private function getNameservers($request)
  {
    // Kullanıcı nameserver belirtmişse onu kullan
    if ($request->has('nameservers') && !empty($request->nameservers)) {
      return is_array($request->nameservers) 
        ? $request->nameservers 
        : explode(',', $request->nameservers);
    }
    
    // Varsayılan nameserver'ları config'den al
    $default_ns = config('services.resellerclub.default_nameservers', []);
    
    if (!empty($default_ns)) {
      return is_array($default_ns) ? $default_ns : explode(',', $default_ns);
    }
    
    // Hiçbiri yoksa boş döndür (ResellerClub varsayılanlarını kullanır)
    return [];
  }
  
  /**
   * Telefon kodu çıkar
   */
  private function getPhoneCode($phone)
  {
    // +90 555 123 45 67 formatından 90 çıkar
    if (preg_match('/\+?(\d+)/', $phone, $matches)) {
      $code = $matches[1];
      if (strlen($code) >= 2) {
        return substr($code, 0, 2);
      }
    }
    return '90'; // Varsayılan Türkiye
  }
  
  /**
   * Telefon numarası çıkar
   */
  private function getPhoneNumber($phone)
  {
    // Sadece rakamları al
    return preg_replace('/\D/', '', $phone);
  }
  
  /**
   * Domain kaydını veritabanına kaydet
   */
  private function saveDomainRecord($user_id, $domain, $years, $order_id, $siparis_id)
  {
    try {
      // alan_adlarim tablosuna ekle (varsa)
      if (DB::getSchemaBuilder()->hasTable('alan_adlarim')) {
        DB::table('alan_adlarim')->insert([
          'uyeid' => $user_id,
          'domain' => $domain,
          'kayit_tarihi' => now(),
          'bitis_tarihi' => now()->addYears($years),
          'durum' => 1,
          'siparis_id' => $siparis_id,
          'order_id' => $order_id,
          'created_at' => now(),
          'updated_at' => now(),
        ]);
      }
    } catch (\Exception $e) {
      Log::warning('Domain record save error: ' . $e->getMessage());
      // Hata olsa bile devam et
    }
  }
}

