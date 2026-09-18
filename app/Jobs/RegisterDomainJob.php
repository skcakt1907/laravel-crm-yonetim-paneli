<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\DomainOrder;
use App\Services\ResellerClubService;
use App\Http\Controllers\DomainPurchaseController;
use Illuminate\Support\Facades\Log;

class RegisterDomainJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
  
  protected $order_id;
  
  public function __construct($order_id)
  {
    $this->order_id = $order_id;
  }
  
  public function handle()
  {
    $order = DomainOrder::find($this->order_id);
    
    if (!$order || !$order->isPaid()) {
      Log::warning('RegisterDomainJob: Order not found or not paid', ['order_id' => $this->order_id]);
      return;
    }
    
    try {
      $user = $order->user;
      
      if (!$user) {
        throw new \Exception('User not found');
      }
      
      // DomainPurchaseController'ı kullan
      $controller = new DomainPurchaseController(app(ResellerClubService::class));
      
      // Request oluştur
      $request = new \Illuminate\Http\Request([
        'domain' => $order->domain,
        'years' => $order->years,
        'siparis_id' => $order->fatura_id ?? $order->id,
      ]);
      
      // Domain kayıt işlemi
      $result = $controller->satinAl($request);
      $result_data = json_decode($result->getContent(), true);
      
      if ($result_data['success'] ?? false) {
        // Başarılı - order'ı güncelle
        $order->update([
          'status' => DomainOrder::STATUS_ACTIVE,
          'reseller_order_id' => $result_data['order_id'] ?? null,
          'registered_at' => now(),
          'expires_at' => now()->addYears($order->years),
        ]);
        
        Log::info('Domain registered successfully', [
          'order_id' => $order->id,
          'domain' => $order->domain,
          'reseller_order_id' => $result_data['order_id'],
        ]);
      } else {
        // Hata
        $order->update([
          'status' => DomainOrder::STATUS_FAILED,
          'error_message' => $result_data['message'] ?? 'Domain kayıt başarısız',
        ]);
        
        Log::error('Domain registration failed', [
          'order_id' => $order->id,
          'domain' => $order->domain,
          'error' => $result_data['message'] ?? 'Unknown error',
        ]);
      }
      
    } catch (\Exception $e) {
      Log::error('RegisterDomainJob Error: ' . $e->getMessage(), [
        'order_id' => $this->order_id,
        'trace' => $e->getTraceAsString(),
      ]);
      
      $order->update([
        'status' => DomainOrder::STATUS_FAILED,
        'error_message' => $e->getMessage(),
      ]);
    }
  }
}

