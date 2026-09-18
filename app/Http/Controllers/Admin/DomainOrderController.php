<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DomainOrder;
use App\Services\ResellerClubService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DomainOrderController extends Controller
{
  protected $resellerClub;
  
  public function __construct(ResellerClubService $resellerClub)
  {
    $this->resellerClub = $resellerClub;
  }
  
  /**
   * Domain siparişleri listesi
   */
  public function index(Request $request)
  {
    $query = DomainOrder::with('user');
    
    // Filtreleme
    if ($request->has('status') && $request->status) {
      $query->where('status', $request->status);
    }
    
    if ($request->has('search') && $request->search) {
      $query->where(function($q) use ($request) {
        $q->where('domain', 'LIKE', '%' . $request->search . '%')
          ->orWhere('reseller_order_id', 'LIKE', '%' . $request->search . '%');
      });
    }
    
    $orders = $query->orderBy('created_at', 'desc')->paginate(20);
    
    $stats = [
      'pending' => DomainOrder::pending()->count(),
      'paid' => DomainOrder::paid()->count(),
      'active' => DomainOrder::active()->count(),
      'failed' => DomainOrder::failed()->count(),
    ];
    
    return view('admin.domain-orders.index', compact('orders', 'stats'));
  }
  
  /**
   * Domain sipariş detayı
   */
  public function show($id)
  {
    $order = DomainOrder::with('user', 'fatura')->findOrFail($id);
    
    // ResellerClub'dan güncel bilgileri çek
    $details = null;
    if ($order->reseller_order_id && config('services.resellerclub.enabled')) {
      try {
        $details = $this->resellerClub->getDomainDetails($order->reseller_order_id);
      } catch (\Exception $e) {
        Log::error('Domain details fetch error: ' . $e->getMessage());
      }
    }
    
    return view('admin.domain-orders.show', compact('order', 'details'));
  }
  
  /**
   * Domain durumunu manuel kontrol et
   */
  public function checkStatus($id)
  {
    $order = DomainOrder::findOrFail($id);
    
    if (!$order->reseller_order_id) {
      return redirect()->back()->with('error', 'ResellerClub order ID bulunamadı');
    }
    
    try {
      $details = $this->resellerClub->getDomainDetails($order->reseller_order_id);
      
      if ($details['success']) {
        $update_data = [];
        $aktivOldu = false;
        
        if ($details['status'] === 'Active') {
          // Daha önce aktif değilse bildirim göndereceğiz
          if ($order->status !== DomainOrder::STATUS_ACTIVE) {
            $aktivOldu = true;
          }
          $update_data['status'] = DomainOrder::STATUS_ACTIVE;
        }
        
        if ($details['creation_date']) {
          $update_data['registered_at'] = date('Y-m-d H:i:s', strtotime($details['creation_date']));
        }
        
        if ($details['expiry_date']) {
          $update_data['expires_at'] = date('Y-m-d H:i:s', strtotime($details['expiry_date']));
        }
        
        $order->update($update_data);

        // 📧 Yeni aktif oldu → müşteriye mail
        if ($aktivOldu && $order->user_id) {
          try {
            \App\Services\CustomerNotifier::domainAlindi($order->user_id, $order->id);
          } catch (\Throwable $e) {
            Log::warning('Domain aktif maili gönderilemedi', ['err' => $e->getMessage()]);
          }
        }
        
        return redirect()->back()->with('success', 'Domain durumu güncellendi');
      } else {
        return redirect()->back()->with('error', $details['message'] ?? 'Domain durumu alınamadı');
      }
      
    } catch (\Exception $e) {
      Log::error('Check Status Error: ' . $e->getMessage());
      return redirect()->back()->with('error', 'Durum kontrol edilirken hata oluştu');
    }
  }
  
  /**
   * Domain kayıt işlemini tekrar dene
   */
  public function retry($id)
  {
    $order = DomainOrder::findOrFail($id);
    
    if (!$order->isPaid() && !$order->isFailed()) {
      return redirect()->back()->with('error', 'Sadece ödenmiş veya başarısız siparişler tekrar denenebilir');
    }
    
    try {
      // Job'u tekrar çalıştır
      dispatch(new \App\Jobs\RegisterDomainJob($order->id));
      
      return redirect()->back()->with('success', 'Domain kayıt işlemi tekrar başlatıldı');
      
    } catch (\Exception $e) {
      Log::error('Retry Domain Registration Error: ' . $e->getMessage());
      return redirect()->back()->with('error', 'İşlem başlatılırken hata oluştu');
    }
  }
  
  /**
   * Domain yenileme (isteğe bağlı)
   */
  public function renew($id)
  {
    $order = DomainOrder::findOrFail($id);
    
    if (!$order->isActive()) {
      return redirect()->back()->with('error', 'Sadece aktif domainler yenilenebilir');
    }
    
    if (!config('services.resellerclub.enabled')) {
      return redirect()->back()->with('error', 'ResellerClub API aktif değil');
    }
    
    if (!$order->reseller_order_id) {
      return redirect()->back()->with('error', 'ResellerClub order ID bulunamadı');
    }
    
    try {
      $years = $request->input('years', 1);
      
      $result = $this->resellerClub->renewDomain($order->reseller_order_id, $years);
      
      if ($result['success']) {
        // Domain order'ı güncelle
        $order->update([
          'expires_at' => $result['expiry_date'] ? date('Y-m-d H:i:s', strtotime($result['expiry_date'])) : null,
        ]);
        
        Log::info('Domain renewed successfully', [
          'order_id' => $order->id,
          'domain' => $order->domain,
          'years' => $years,
        ]);
        
        return redirect()->back()->with('success', 'Domain başarıyla yenilendi');
      } else {
        Log::error('Domain renewal failed', [
          'order_id' => $order->id,
          'error' => $result['message'],
        ]);
        
        return redirect()->back()->with('error', $result['message'] ?? 'Domain yenileme başarısız');
      }
      
    } catch (\Exception $e) {
      Log::error('Domain Renew Error: ' . $e->getMessage());
      return redirect()->back()->with('error', 'Yenileme sırasında hata oluştu: ' . $e->getMessage());
    }
  }
}