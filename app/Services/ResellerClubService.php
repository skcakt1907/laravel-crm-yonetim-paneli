<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResellerClubService
{
  private $base_url = 'https://httpapi.com/api';
  private $user_id;
  private $api_key;
  
  public function __construct()
  {
    $this->user_id = config('services.resellerclub.user_id');
    $this->api_key = config('services.resellerclub.api_key');
  }
  
  /**
   * Domain müsaitlik kontrolü
   */
  public function checkAvailability($domain_name, $tlds = [])
  {
    try {
      $params = [
        'auth-userid' => $this->user_id,
        'api-key' => $this->api_key,
        'domain-name' => $domain_name,
      ];
      
      if (!empty($tlds)) {
        $params['tlds'] = is_array($tlds) ? implode(',', $tlds) : $tlds;
      }
      
      $response = Http::timeout(30)->get($this->base_url . '/domains/available.json', $params);
      
      if ($response->successful()) {
        $data = $response->json();
        
        // ResellerClub API formatı: {"example.com": {"status": "available", "classkey": "domcno"}}
        $is_available = false;
        $price = null;
        
        if (isset($data[$domain_name])) {
          $domain_data = $data[$domain_name];
          $is_available = isset($domain_data['status']) && $domain_data['status'] === 'available';
          
          // Fiyat bilgisini al
          if ($is_available) {
            $tld = $this->extractTld($domain_name);
            $price_result = $this->getDomainPrice($tld, 'register');
            if ($price_result['success']) {
              $price = $price_result['price'];
            }
          }
        }
        
        return [
          'success' => true,
          'available' => $is_available,
          'domain' => $domain_name,
          'price' => $price,
          'data' => $data,
        ];
      }
      
      return [
        'success' => false,
        'message' => 'API hatası: ' . $response->body(),
        'available' => false,
      ];
      
    } catch (\Exception $e) {
      Log::error('ResellerClub API Error: ' . $e->getMessage());
      return [
        'success' => false,
        'message' => $e->getMessage(),
        'available' => false,
      ];
    }
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
  
  /**
   * Domain kayıt
   */
  public function registerDomain($domain_name, $years = 1, $customer_id, $contact_ids, $nameservers = [])
  {
    try {
      $params = [
        'auth-userid' => $this->user_id,
        'api-key' => $this->api_key,
        'domain-name' => $domain_name,
        'years' => $years,
        'customer-id' => $customer_id,
        'reg-contact-id' => $contact_ids['registrant'] ?? $contact_ids,
        'admin-contact-id' => $contact_ids['admin'] ?? $contact_ids,
        'tech-contact-id' => $contact_ids['tech'] ?? $contact_ids,
        'billing-contact-id' => $contact_ids['billing'] ?? $contact_ids,
      ];
      
      if (!empty($nameservers)) {
        $params['ns'] = is_array($nameservers) ? $nameservers : explode(',', $nameservers);
      }
      
      $response = Http::asForm()->post($this->base_url . '/domains/register.json', $params);
      
      if ($response->successful()) {
        $data = $response->json();
        
        if (isset($data['status']) && $data['status'] === 'Success') {
          return [
            'success' => true,
            'order_id' => $data['entityid'] ?? null,
            'data' => $data,
          ];
        }
        
        return [
          'success' => false,
          'message' => $data['message'] ?? 'Domain kayıt başarısız',
          'data' => $data,
        ];
      }
      
      return [
        'success' => false,
        'message' => 'API hatası: ' . $response->body(),
      ];
      
    } catch (\Exception $e) {
      Log::error('ResellerClub Register Error: ' . $e->getMessage());
      return [
        'success' => false,
        'message' => $e->getMessage(),
      ];
    }
  }
  
  /**
   * Müşteri oluştur
   */
  public function createCustomer($username, $passwd, $name, $company, $email, $address, $city, $state, $country, $zipcode, $phone_cc, $phone)
  {
    try {
      $response = Http::asForm()->post($this->base_url . '/customers/signup.json', [
        'auth-userid' => $this->user_id,
        'api-key' => $this->api_key,
        'username' => $username,
        'passwd' => $passwd,
        'name' => $name,
        'company' => $company,
        'email' => $email,
        'address-line-1' => $address,
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'zipcode' => $zipcode,
        'phone-cc' => $phone_cc,
        'phone' => $phone,
      ]);
      
      if ($response->successful()) {
        $data = $response->json();
        
        if (isset($data['status']) && $data['status'] === 'Success') {
          return [
            'success' => true,
            'customer_id' => $data['customerid'] ?? null,
            'data' => $data,
          ];
        }
        
        return [
          'success' => false,
          'message' => $data['message'] ?? 'Müşteri oluşturma başarısız',
          'data' => $data,
        ];
      }
      
      return [
        'success' => false,
        'message' => 'API hatası: ' . $response->body(),
      ];
      
    } catch (\Exception $e) {
      Log::error('ResellerClub Create Customer Error: ' . $e->getMessage());
      return [
        'success' => false,
        'message' => $e->getMessage(),
      ];
    }
  }
  
  /**
   * İletişim bilgisi oluştur
   */
  public function createContact($customer_id, $name, $company, $email, $address, $city, $state, $country, $zipcode, $phone_cc, $phone, $type = 'Contact')
  {
    try {
      $response = Http::asForm()->post($this->base_url . '/contacts/add.json', [
        'auth-userid' => $this->user_id,
        'api-key' => $this->api_key,
        'customer-id' => $customer_id,
        'name' => $name,
        'company' => $company,
        'email' => $email,
        'address-line-1' => $address,
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'zipcode' => $zipcode,
        'phone-cc' => $phone_cc,
        'phone' => $phone,
        'type' => $type, // Contact, CaContact, CoContact
      ]);
      
      if ($response->successful()) {
        $data = $response->json();
        
        if (isset($data['status']) && $data['status'] === 'Success') {
          return [
            'success' => true,
            'contact_id' => $data['contactid'] ?? null,
            'data' => $data,
          ];
        }
        
        return [
          'success' => false,
          'message' => $data['message'] ?? 'İletişim bilgisi oluşturma başarısız',
          'data' => $data,
        ];
      }
      
      return [
        'success' => false,
        'message' => 'API hatası: ' . $response->body(),
      ];
      
    } catch (\Exception $e) {
      Log::error('ResellerClub Create Contact Error: ' . $e->getMessage());
      return [
        'success' => false,
        'message' => $e->getMessage(),
      ];
    }
  }
  
  /**
   * Domain fiyat bilgisi
   */
  public function getDomainPrice($tld, $action = 'register')
  {
    try {
      $response = Http::get($this->base_url . '/products/customer-price.json', [
        'auth-userid' => $this->user_id,
        'api-key' => $this->api_key,
        'product-type' => 'domain',
        'action' => $action, // register, renew, transfer
        'product-key' => $tld,
      ]);
      
      if ($response->successful()) {
        $data = $response->json();
        return [
          'success' => true,
          'price' => $data['customerprice'] ?? null,
          'data' => $data,
        ];
      }
      
      return [
        'success' => false,
        'message' => 'API hatası: ' . $response->body(),
      ];
      
    } catch (\Exception $e) {
      Log::error('ResellerClub Price Error: ' . $e->getMessage());
      return [
        'success' => false,
        'message' => $e->getMessage(),
      ];
    }
  }
  
  /**
   * Domain detayları (order ID ile)
   */
  public function getDomainDetails($order_id)
  {
    try {
      $response = Http::get($this->base_url . '/domains/details.json', [
        'auth-userid' => $this->user_id,
        'api-key' => $this->api_key,
        'order-id' => $order_id,
        'options' => 'All',
      ]);
      
      if ($response->successful()) {
        $data = $response->json();
        
        if (isset($data['status']) && $data['status'] === 'Success') {
          return [
            'success' => true,
            'domain' => $data['domainname'] ?? null,
            'status' => $data['currentstatus'] ?? null,
            'creation_date' => $data['creationtime'] ?? null,
            'expiry_date' => $data['endtime'] ?? null,
            'nameservers' => $data['ns'] ?? [],
            'data' => $data,
          ];
        }
        
        return [
          'success' => false,
          'message' => $data['message'] ?? 'Domain detayları alınamadı',
          'data' => $data,
        ];
      }
      
      return [
        'success' => false,
        'message' => 'API hatası: ' . $response->body(),
      ];
      
    } catch (\Exception $e) {
      Log::error('ResellerClub Domain Details Error: ' . $e->getMessage());
      return [
        'success' => false,
        'message' => $e->getMessage(),
      ];
    }
  }
  
  /**
   * Domain yenileme
   */
  public function renewDomain($order_id, $years = 1)
  {
    try {
      $response = Http::asForm()->post($this->base_url . '/domains/renew.json', [
        'auth-userid' => $this->user_id,
        'api-key' => $this->api_key,
        'order-id' => $order_id,
        'years' => $years,
        'exp-date' => date('Y-m-d'), // Bugünün tarihi
        'invoice-option' => 'NoInvoice', // Fatura seçeneği
      ]);
      
      if ($response->successful()) {
        $data = $response->json();
        
        if (isset($data['status']) && $data['status'] === 'Success') {
          return [
            'success' => true,
            'order_id' => $data['entityid'] ?? $order_id,
            'expiry_date' => $data['endtime'] ?? null,
            'data' => $data,
          ];
        }
        
        return [
          'success' => false,
          'message' => $data['message'] ?? 'Domain yenileme başarısız',
          'data' => $data,
        ];
      }
      
      return [
        'success' => false,
        'message' => 'API hatası: ' . $response->body(),
      ];
      
    } catch (\Exception $e) {
      Log::error('ResellerClub Renew Error: ' . $e->getMessage());
      return [
        'success' => false,
        'message' => $e->getMessage(),
      ];
    }
  }
  
  /**
   * Domain kayıt (customerData ile)
   */
  public function registerDomainWithData($domain_name, $years, $customer_data)
  {
    // Önce müşteri oluştur
    $customer_result = $this->createCustomer(
      $customer_data['username'],
      $customer_data['password'],
      $customer_data['name'],
      $customer_data['company'] ?? '',
      $customer_data['email'],
      $customer_data['address'],
      $customer_data['city'],
      $customer_data['state'] ?? '',
      $customer_data['country'],
      $customer_data['zipcode'],
      $customer_data['phone_cc'],
      $customer_data['phone']
    );
    
    if (!$customer_result['success']) {
      return $customer_result;
    }
    
    $customer_id = $customer_result['customer_id'];
    
    // İletişim bilgisi oluştur
    $contact_result = $this->createContact(
      $customer_id,
      $customer_data['name'],
      $customer_data['company'] ?? '',
      $customer_data['email'],
      $customer_data['address'],
      $customer_data['city'],
      $customer_data['state'] ?? '',
      $customer_data['country'],
      $customer_data['zipcode'],
      $customer_data['phone_cc'],
      $customer_data['phone']
    );
    
    if (!$contact_result['success']) {
      return $contact_result;
    }
    
    $contact_id = $contact_result['contact_id'];
    
    // Domain kaydet
    return $this->registerDomain(
      $domain_name,
      $years,
      $customer_id,
      [
        'registrant' => $contact_id,
        'admin' => $contact_id,
        'tech' => $contact_id,
        'billing' => $contact_id,
      ],
      $customer_data['nameservers'] ?? []
    );
  }
}

