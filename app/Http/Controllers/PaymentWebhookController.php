<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DomainOrder;
use App\Models\Fatura;
use App\Services\ResellerClubService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
  protected $resellerClub;
  
  public function __construct(ResellerClubService $resellerClub)
  {
    $this->resellerClub = $resellerClub;
  }
  
  /**
   * PayTR webhook/callback
   */
  public function paytrCallback(Request $request)
  {
    try {
      // ════════════════════════════════════════════════════════════
      // TEŞHİS LOGU: Callback'in sunucuya ULAŞTIĞINI kanıtlar.
      // Bu satır log'da görünmüyorsa, PayTR isteği sunucuya hiç gelmemiştir
      // (Bildirim URL boş/yanlış) veya CSRF/route katmanı engelliyordur.
      // ════════════════════════════════════════════════════════════
      Log::info('PayTR callback ULAŞTI', [
        'merchant_oid' => $request->merchant_oid,
        'status'       => $request->status,
        'total_amount' => $request->total_amount,
        'ip'           => $request->ip(),
      ]);

      // PayTR anahtarlarını al — SepetController ile AYNI sıra:
      // önce yeni şema (ayarlar), yoksa eski (paytr). Aksi halde hash uyuşmaz.
      $merchant_key  = null;
      $merchant_salt = null;

      $ayar = DB::table('ayarlar')->first();
      if ($ayar) {
        $merchant_key  = $ayar->paytr_merchant_key  ?? null;
        $merchant_salt = $ayar->paytr_merchant_salt ?? null;
      }

      if (!$merchant_key || !$merchant_salt) {
        $paytr = DB::table('paytr')->first();
        if ($paytr) {
          $merchant_key  = $merchant_key  ?: ($paytr->magaza_parola  ?? '');
          $merchant_salt = $merchant_salt ?: ($paytr->magaza_anahtar ?? '');
        }
      }

      if (!$merchant_key || !$merchant_salt) {
        Log::error('PayTR callback: PayTR ayarları bulunamadı (ayarlar/paytr)');
        return response()->json(['status' => 'error', 'message' => 'PayTR settings not found'], 500);
      }
      
      $hash = base64_encode(
        hash_hmac('sha256', 
          $request->merchant_oid . $merchant_salt . $request->status . $request->total_amount,
          $merchant_key,
          true
        )
      );
      
      if ($hash !== $request->hash) {
        Log::warning('PayTR callback hash mismatch', ['request' => $request->all()]);
        return response('PAYTR notification failed: bad hash', 400);
      }
      
      // merchant_oid formatı kontrolü
      // ÖNEMLİ: PayTR, aynı merchant_oid ile birden fazla deneme olduğunda
      // oid'in SONUNA kendi benzersizleştirme eki (timestamp) ekleyebilir.
      // Örn: gönderdiğimiz "FAT2562" → callback'te "FAT25621780489712" gelir.
      // Bu yüzden tam eşleşme değil, PREFIX eşleşmesi ile gerçek kaydı buluyoruz.
      $merchant_oid = $request->merchant_oid;

      // Gerçek fatura_id'yi çöz: FAT/# temizle, ardından faturalar tablosunda
      // var olan EN UZUN prefix'i bul (PayTR ekini soyar).
      $oid_clean = str_replace(['FAT', '#', 'DOMAIN_'], '', $merchant_oid);
      $fatura_id = $this->resolveFaturaId($merchant_oid, $oid_clean);

      // satilanlar kaydını bul: önce tam eşleşme, olmazsa prefix eşleşme,
      // olmazsa çözülen fatura_id üzerinden.
      $siparis = null;
      $satilanKayit = null;
      if (DB::getSchemaBuilder()->hasTable('satilanlar')) {
        // 1) tam eşleşme (#FATxxxx)
        $tryExact = '#' . $merchant_oid;
        $satilanKayit = DB::table('satilanlar')->where('spno', $tryExact)->first();
        // 2) PayTR eki eklenmişse: bizim spno'muz gelen oid'in başlangıcıdır.
        //    spno '#FAT2562', gelen 'FAT25621780489712' → baştan eşleştir.
        //    Karşılaştırma FAT dahil ham oid ile yapılır (REPLACE sadece # kaldırır).
        if (!$satilanKayit) {
          $satilanKayit = DB::table('satilanlar')
            ->whereRaw("? LIKE CONCAT(REPLACE(spno, '#', ''), '%')", [$merchant_oid])
            ->where('spno', 'like', '#FAT%')
            ->orderByDesc('id')
            ->first();
        }
        // 3) fatura_id üzerinden (fatura_id kolonu varsa)
        if (!$satilanKayit && $fatura_id && DB::getSchemaBuilder()->hasColumn('satilanlar', 'fatura_id')) {
          $satilanKayit = DB::table('satilanlar')->where('fatura_id', $fatura_id)->orderByDesc('id')->first();
        }
      }
      // Eşleşen kaydın gerçek spno'sunu kullan (sonraki update'ler buna gider)
      $siparis = $satilanKayit->spno ?? ('#' . $merchant_oid);
      if (!$fatura_id && $satilanKayit && isset($satilanKayit->fatura_id)) {
        $fatura_id = $satilanKayit->fatura_id;
      }
      
      // Eğer ödeme zaten onaylanmışsa "OK" döndür (idempotent)
      if (DB::getSchemaBuilder()->hasTable('satilanlar')) {
        $durum = DB::table('satilanlar')->where('spno', $siparis)->first();
        if ($durum && isset($durum->paytronay) && $durum->paytronay == "1") {
          if (DB::getSchemaBuilder()->hasTable('faturalar') && $fatura_id) {
            $faturasor = DB::table('faturalar')->where('id', $fatura_id)->first();
            if ($faturasor && isset($faturasor->durum) && (string)$faturasor->durum === "1") {
              return response('OK', 200);
            }
          }
        }
      }
      
      if (strpos($merchant_oid, 'DOMAIN_') === 0) {
        // Domain siparişi
        $order_id = str_replace('DOMAIN_', '', $merchant_oid);
        $order = DomainOrder::find($order_id);
        
        if (!$order) {
          Log::error('PayTR callback: DomainOrder not found', ['order_id' => $order_id]);
          return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }
        
        if ($request->status === 'success') {
          // Ödeme başarılı
          $order->update([
            'status' => DomainOrder::STATUS_PAID,
            'payment_method' => 'paytr',
            'payment_id' => $request->payment_id ?? null,
          ]);
          
          // Domain kayıt işlemini başlat
          $this->processDomainRegistration($order);
          
          return response('OK', 200);
        } else {
          // Ödeme başarısız
          $order->update([
            'status' => DomainOrder::STATUS_FAILED,
            'error_message' => $request->failed_reason_code . ': ' . $request->failed_reason_msg,
          ]);
          
          return response('OK', 200);
        }
      } else {
        // Normal fatura ödemesi
        $satilan = $satilanKayit;
        
        $fatura = null;
        if ($fatura_id && class_exists('App\Models\Fatura')) {
          $fatura = Fatura::find($fatura_id);
        } elseif ($fatura_id && DB::getSchemaBuilder()->hasTable('faturalar')) {
          $fatura = DB::table('faturalar')->where('id', $fatura_id)->first();
        }
        
        if ($request->status === 'success') {
          // Tutar doğrulaması - beklenen tutarla gelen tutar eşleşmeli
          if ($fatura && isset($fatura->toplam)) {
            $beklenenTutar = intval(floatval($fatura->toplam) * 100);
            $gelenTutar = intval($request->total_amount ?? 0);
            if ($gelenTutar > 0 && $beklenenTutar > 0 && abs($beklenenTutar - $gelenTutar) > 1) {
              Log::warning('PayTR tutar uyumsuzlugu', [
                'beklenen' => $beklenenTutar,
                'gelen' => $gelenTutar,
                'merchant_oid' => $merchant_oid,
              ]);
              return response('OK', 200);
            }
          }

          // satilanlar tablosunu ONAYLA: paytronay + durum + ödeme tarihi
          if ($satilan && DB::getSchemaBuilder()->hasTable('satilanlar')) {
            $satilanUpdate = ['paytronay' => 1];
            if (DB::getSchemaBuilder()->hasColumn('satilanlar', 'durum')) {
              $satilanUpdate['durum'] = 1;
            }
            if (DB::getSchemaBuilder()->hasColumn('satilanlar', 'odenen_tarih')) {
              $satilanUpdate['odenen_tarih'] = now();
            }
            DB::table('satilanlar')
              ->where('spno', $siparis)
              ->update($satilanUpdate);
          }
          
          // Fatura varsa güncelle
          if ($fatura) {
            if (is_object($fatura) && method_exists($fatura, 'update')) {
              // Eloquent model
              $fatura->update([
                'durum' => 1,
                'odenen_tarih' => now(),
                'odeme_yontemi' => 'Kredi Kartı (Online Ödeme)',
              ]);
            } elseif (DB::getSchemaBuilder()->hasTable('faturalar')) {
              // Query builder — id ile güncelle (spno kolonu olmayabilir, id her zaman güvenilir)
              $faturaUpdate = [
                'durum' => 1,
                'odeme_yontemi' => 'Kredi Kartı (Online Ödeme)',
              ];
              if (DB::getSchemaBuilder()->hasColumn('faturalar', 'odenen_tarih')) {
                $faturaUpdate['odenen_tarih'] = now();
              }
              if (is_numeric($fatura_id) && $fatura_id > 0) {
                DB::table('faturalar')->where('id', $fatura_id)->update($faturaUpdate);
              } else {
                DB::table('faturalar')->where('spno', $siparis)->update($faturaUpdate);
              }
            }
            
            // Bayi komisyonunu kaydet (ödeme başarılı olduğunda)
            $this->processBayiCommission($fatura);

            // Müşteriye ödeme onay maili (lime+logo, CustomerNotifier)
            try {
              $uyeId = is_object($fatura) ? ($fatura->uyeid ?? $fatura->uye_id ?? null) : null;
              if (!$uyeId && DB::getSchemaBuilder()->hasTable('faturalar')) {
                $fr = (is_numeric($fatura_id) && $fatura_id > 0)
                  ? DB::table('faturalar')->where('id', $fatura_id)->first()
                  : DB::table('faturalar')->where('spno', $siparis)->first();
                $uyeId = $fr->uyeid ?? null;
              }
              $fId = (is_object($fatura) ? ($fatura->id ?? null) : null) ?: (is_numeric($fatura_id) ? (int) $fatura_id : null);
              if ($uyeId && class_exists(\App\Services\CustomerNotifier::class)) {
                \App\Services\CustomerNotifier::odemeOnaylandi($uyeId, $fId);
              }
            } catch (\Throwable $e) {
              Log::warning('PayTR ödeme onay maili gönderilemedi', ['err' => $e->getMessage()]);
            }
          }
          
          // Domain siparişleri varsa işle
          if (class_exists('App\Models\DomainOrder')) {
            $domain_orders = \App\Models\DomainOrder::where('fatura_id', $fatura_id)
              ->where('status', \App\Models\DomainOrder::STATUS_PENDING)
              ->get();
            
            foreach ($domain_orders as $order) {
              $order->update([
                'status' => \App\Models\DomainOrder::STATUS_PAID,
                'payment_method' => 'paytr',
              ]);
              $this->processDomainRegistration($order);
            }
          }

          // Ödeme onaylandı → satışa bağlı domain kayıtlarını oluştur + sepeti temizle
          $this->siparisTamamlamaIslemleri($siparis, $fatura_id);
          
          return response('OK', 200);
        } else {
          // Ödeme başarısız
          if ($fatura) {
            if (is_object($fatura) && method_exists($fatura, 'update')) {
              // NOT: durum SAYISAL bir alan (0=iptal/ödenmedi, 1=ödendi).
              // Önceden 'iptal' (metin) yazılıyordu; query builder yolu ile
              // tutarlı olması için 0 kullanılır.
              $fatura->update(['durum' => 0]);
            } elseif (DB::getSchemaBuilder()->hasTable('faturalar')) {
              DB::table('faturalar')
                ->where('spno', $siparis)
                ->update(['durum' => '0']);
            }
          }
          
          return response('OK', 200);
        }
      }
      
    } catch (\Exception $e) {
      Log::error('PayTR callback error: ' . $e->getMessage(), [
        'request' => $request->all(),
        'trace' => $e->getTraceAsString(),
      ]);
      
      return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
  }
  
  /**
   * iyzico webhook/callback
   */
  public function iyzicoCallback(Request $request)
  {
    try {
      $token = $request->token;
      
      if (!$token) {
        return response()->json(['status' => 'error', 'message' => 'Token missing'], 400);
      }
      
      // iyzico servisinden ödeme durumunu kontrol et
      $iyzico = app(\App\Services\IyzicoService::class);
      $payment_result = $iyzico->verifyCallback($token);
      
      if ($payment_result['status'] !== 'success') {
        return response()->json(['status' => 'error', 'message' => 'Payment check failed'], 400);
      }
      
      $payment_data = $payment_result;
      $conversation_id = $payment_data['conversation_id'] ?? $payment_data['conversationId'] ?? '';
      
      // conversationId formatı: DOMAIN_ORDER_ID veya FATURA_ID
      if (strpos($conversation_id, 'DOMAIN_') === 0) {
        // Domain siparişi
        $order_id = str_replace('DOMAIN_', '', $conversation_id);
        $order = DomainOrder::find($order_id);
        
        if (!$order) {
          Log::error('iyzico callback: DomainOrder not found', ['order_id' => $order_id]);
          return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }
        
        if ($payment_result['status'] === 'success') {
          $order->update([
            'status' => DomainOrder::STATUS_PAID,
            'payment_method' => 'iyzico',
            'payment_id' => $payment_data['payment_id'] ?? null,
          ]);
          
          $this->processDomainRegistration($order);
          
          return response()->json(['status' => 'success']);
        } else {
          $order->update([
            'status' => DomainOrder::STATUS_FAILED,
            'error_message' => $payment_data['error'] ?? 'Payment failed',
          ]);
          
          return response()->json(['status' => 'failed']);
        }
      } else {
        // Normal fatura ödemesi
        $fatura_id = str_replace('FAT', '', $conversation_id);
        $fatura = Fatura::find($fatura_id);
        
        if (!$fatura) {
          Log::error('iyzico callback: Fatura not found', ['fatura_id' => $fatura_id]);
          return response()->json(['status' => 'error', 'message' => 'Fatura not found'], 404);
        }
        
        if ($payment_result['status'] === 'success') {
          // DIKKAT: 'durum' SAYISAL bir kolon (int: 0=bekleyen, 1=odendi,
          // 2=iptal). Buraya 'odendi' METNI yaziliyordu; MySQL bunu 0'a
          // ceviriyor, yani odenen fatura BEKLEYEN olarak kaydediliyordu.
          // Ayrica 'odenen_tarih' hic yazilmadigi icin odeme gunluk
          // hareketlere/raporlara dusmuyordu.
          $fatura->update([
            'durum' => 1,
            'odenen_tarih' => now()->toDateString(),
            'odeme_tarihi' => now(),
            'odeme_yontemi' => 'iyzico',
          ]);
          
          // Bayi komisyonunu kaydet (ödeme başarılı olduğunda)
          $this->processBayiCommission($fatura);

          // Müşteriye ödeme onay maili (lime+logo, CustomerNotifier)
          try {
            $uyeId = $fatura->uyeid ?? $fatura->uye_id ?? null;
            if ($uyeId && class_exists(\App\Services\CustomerNotifier::class)) {
              \App\Services\CustomerNotifier::odemeOnaylandi($uyeId, $fatura->id ?? null);
            }
          } catch (\Throwable $e) {
            Log::warning('iyzico ödeme onay maili gönderilemedi', ['err' => $e->getMessage()]);
          }

          // Domain siparişleri varsa işle
          $domain_orders = DomainOrder::where('fatura_id', $fatura_id)
            ->where('status', DomainOrder::STATUS_PENDING)
            ->get();
          
          foreach ($domain_orders as $order) {
            $order->update([
              'status' => DomainOrder::STATUS_PAID,
              'payment_method' => 'iyzico',
            ]);
            $this->processDomainRegistration($order);
          }
          
          return response()->json(['status' => 'success']);
        } else {
          // durum SAYISAL alan (0=iptal, 1=ödendi) — 'iptal' metin yerine 0.
          $fatura->update([
            'durum' => 0,
          ]);
          
          return response()->json(['status' => 'failed']);
        }
      }
      
    } catch (\Exception $e) {
      Log::error('iyzico callback error: ' . $e->getMessage(), [
        'request' => $request->all(),
        'trace' => $e->getTraceAsString(),
      ]);
      
      return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
  }
  
  /**
   * merchant_oid'den gerçek fatura_id'yi çözer.
   * PayTR, oid sonuna timestamp eki ekleyebildiği için (FAT2562 → FAT25621780489712),
   * temizlenmiş rakam dizisinin azalan uzunluktaki prefix'lerini deneyip
   * faturalar tablosunda GERÇEKTEN var olan ilk id'yi döndürür.
   *
   * @param string $merchant_oid Ham oid (FAT2562... / DOMAIN_... olabilir)
   * @param string $oid_clean    FAT/#/DOMAIN_ temizlenmiş hali (ör. "25621780489712")
   * @return int|null
   */
  private function resolveFaturaId($merchant_oid, $oid_clean)
  {
    if ($oid_clean === '' || !ctype_digit($oid_clean)) {
      return null;
    }
    if (!DB::getSchemaBuilder()->hasTable('faturalar')) {
      return is_numeric($oid_clean) ? (int) $oid_clean : null;
    }

    // 1) Tam değer bir fatura mı? (PayTR ek yapmadıysa)
    $tam = DB::table('faturalar')->where('id', (int) $oid_clean)->first();
    if ($tam) {
      return (int) $oid_clean;
    }

    // 2) PayTR ek yapmışsa: en uzun geçerli prefix'i bul.
    //    Örn "25621780489712" → 2562 (faturalar'da var olan).
    //    Uzunluğu kısaltarak dene; gerçek fatura id genelde ilk birkaç hanedir.
    $len = strlen($oid_clean);
    for ($i = $len - 1; $i >= 1; $i--) {
      $prefix = substr($oid_clean, 0, $i);
      // Baştaki sıfırları atlamak için (id sıfırla başlamaz)
      if ($prefix[0] === '0') {
        continue;
      }
      $row = DB::table('faturalar')->where('id', (int) $prefix)->first();
      if ($row) {
        return (int) $prefix;
      }
    }

    return null;
  }

  /**
   * Ödeme onaylandıktan SONRA çalışır:
   * - Bu siparişe (spno) ait domain tipli satışlar için alan_adlarim / domain_satislar kayıtlarını oluşturur (yoksa)
   * - İlgili üyenin sepetini temizler
   * - Müşteri-üye senkronunu tetikler (varsa)
   */
  private function siparisTamamlamaIslemleri($siparis, $fatura_id)
  {
    try {
      if (!DB::getSchemaBuilder()->hasTable('satilanlar')) {
        return;
      }

      $satislar = DB::table('satilanlar')->where('spno', $siparis)->get();
      if ($satislar->isEmpty()) {
        return;
      }

      $uyeId = $satislar->first()->uyeid ?? null;
      $uye = $uyeId ? DB::table('uyeler')->where('id', $uyeId)->first() : null;

      foreach ($satislar as $s) {
        // tipi 0 = domain
        $tipi = isset($s->tipi) ? (int) $s->tipi : null;
        $domainAdi = $s->domain ?? null;
        if ($tipi !== 0 || empty($domainAdi)) {
          continue;
        }

        $baslangic = $s->baslangic_tarih ?? ($s->baslangic_tarihi ?? now());
        $bitis     = $s->bitis_tarih ?? ($s->bitis_tarihi ?? \Carbon\Carbon::parse($baslangic)->addYear());

        // 1) alan_adlarim (yoksa ekle)
        try {
          if (DB::getSchemaBuilder()->hasTable('alan_adlarim')) {
            $varMi = DB::table('alan_adlarim')
              ->where('uyeid', $uyeId)
              ->where('domain', $domainAdi)
              ->exists();
            if (!$varMi) {
              $alanData = [
                'uyeid'        => $uyeId,
                'domain'       => $domainAdi,
                'kayit_tarihi' => $baslangic,
                'bitis_tarihi' => $bitis,
                'durum'        => 1,
              ];
              if (DB::getSchemaBuilder()->hasColumn('alan_adlarim', 'siparis_id')) {
                $alanData['siparis_id'] = $s->id;
              }
              if (DB::getSchemaBuilder()->hasColumn('alan_adlarim', 'order_id')) {
                $alanData['order_id'] = $s->id;
              }
              if (DB::getSchemaBuilder()->hasColumn('alan_adlarim', 'created_at')) {
                $alanData['created_at'] = now();
                $alanData['updated_at'] = now();
              }
              DB::table('alan_adlarim')->insert($alanData);
            }
          }
        } catch (\Throwable $e) {
          Log::warning('Callback alan_adlarim insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
        }

        // 2) domain_satislar (yoksa ekle)
        try {
          if (DB::getSchemaBuilder()->hasTable('domain_satislar')) {
            $varMi = DB::table('domain_satislar')
              ->where('uyeid', $uyeId)
              ->where('domain', $domainAdi)
              ->exists();
            if (!$varMi) {
              $satisData = [
                'uyeid'           => $uyeId,
                'domain'          => $domainAdi,
                'tutar'           => $s->tutar ?? 0,
                'baslangic_tarih' => $baslangic,
                'bitis_tarih'     => $bitis,
                'durum'           => 1,
              ];
              if (DB::getSchemaBuilder()->hasColumn('domain_satislar', 'musteri_email') && $uye && !empty($uye->email)) {
                $satisData['musteri_email'] = $uye->email;
              }
              if (DB::getSchemaBuilder()->hasColumn('domain_satislar', 'saglayici')) {
                $satisData['saglayici'] = 'manuel';
              }
              if (DB::getSchemaBuilder()->hasColumn('domain_satislar', 'created_at')) {
                $satisData['created_at'] = now();
                $satisData['updated_at'] = now();
              }
              DB::table('domain_satislar')->insert($satisData);
            }
          }
        } catch (\Throwable $e) {
          Log::warning('Callback domain_satislar insert hata', ['err' => $e->getMessage(), 'domain' => $domainAdi]);
        }
      }

      // Sepeti temizle (ödeme onaylandı)
      if ($uyeId) {
        try {
          if (DB::getSchemaBuilder()->hasColumn('sepet', 'user_id')) {
            DB::table('sepet')->where('user_id', $uyeId)->delete();
          } elseif (DB::getSchemaBuilder()->hasColumn('sepet', 'uyeid')) {
            DB::table('sepet')->where('uyeid', $uyeId)->delete();
          }
        } catch (\Throwable $e) {
          Log::warning('Callback sepet temizleme hata', ['err' => $e->getMessage()]);
        }
      }

    } catch (\Throwable $e) {
      Log::error('siparisTamamlamaIslemleri hata', ['err' => $e->getMessage(), 'spno' => $siparis]);
    }
  }

  /**
   * Domain kayıt işlemini başlat
   */
  private function processDomainRegistration(DomainOrder $order)
  {
    if (!config('services.resellerclub.enabled')) {
      Log::info('ResellerClub disabled, skipping domain registration', ['order_id' => $order->id]);
      return;
    }
    
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
      Log::error('Domain registration process error: ' . $e->getMessage());
      dispatch(new \App\Jobs\RegisterDomainJob($order->id));
    }
  }
  
  /**
   * Bayi komisyonunu işle (ödeme başarılı olduğunda)
   */
  private function processBayiCommission($fatura)
  {
    try {
      // Fatura'dan bayi_kodu'nu al
      $bayiKodu = null;
      $faturaId = null;
      $uyeId = null;
      $satisTutari = 0;
      
      if (is_object($fatura) && method_exists($fatura, 'getAttribute')) {
        // Eloquent model
        $bayiKodu = $fatura->bayi_kodu ?? null;
        $faturaId = $fatura->id ?? null;
        $uyeId = $fatura->uyeid ?? $fatura->uye_id ?? null;
        $satisTutari = (float)($fatura->toplam ?? $fatura->tutar ?? 0);
      } elseif (DB::getSchemaBuilder()->hasTable('faturalar')) {
        // Query builder - fatura'yı tekrar çek
        $faturaData = DB::table('faturalar')
          ->where('id', is_object($fatura) ? ($fatura->id ?? null) : $fatura)
          ->first();
        
        if ($faturaData) {
          $bayiKodu = $faturaData->bayi_kodu ?? null;
          $faturaId = $faturaData->id ?? null;
          $uyeId = $faturaData->uyeid ?? null;
          $satisTutari = (float)($faturaData->toplam ?? $faturaData->tutar ?? 0);
        }
      }
      
      // Bayi kodu yoksa işlem yapma
      if (!$bayiKodu || $satisTutari <= 0) {
        return;
      }
      
      // Bayi'yi bul
      $bayi = DB::table('bayiler')
        ->where('bayi_kodu', strtoupper(trim($bayiKodu)))
        ->where('durum', 1)
        ->where('onay_durumu', 1)
        ->first();
      
      if (!$bayi) {
        Log::warning('Bayi bulunamadı', ['bayi_kodu' => $bayiKodu]);
        return;
      }
      
      // Daha önce bu fatura için komisyon kaydedilmiş mi kontrol et
      $mevcutKayit = DB::table('bayi_satislar')
        ->where('fatura_id', $faturaId)
        ->where('bayi_id', $bayi->id)
        ->first();
      
      if ($mevcutKayit) {
        Log::info('Bu fatura için komisyon zaten kaydedilmiş', ['fatura_id' => $faturaId]);
        return;
      }
      
      // Komisyon oranını al
      $komisyonOrani = (float)($bayi->komisyon_orani ?? 10);
      
      // Bayi komisyonunu kaydet
      \App\Services\BayiKodService::recordCommission(
        $bayi->id,
        $uyeId,
        $faturaId,
        $satisTutari,
        $komisyonOrani,
        0 // Müşteri indirim tutarı (webhook'ta bilinmiyor, 0 olarak kaydedilir)
      );
      
      Log::info('Bayi komisyonu kaydedildi', [
        'bayi_id' => $bayi->id,
        'fatura_id' => $faturaId,
        'satis_tutari' => $satisTutari,
        'komisyon_orani' => $komisyonOrani
      ]);
      
    } catch (\Exception $e) {
      Log::error('Bayi komisyon işleme hatası', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
    }
  }
}