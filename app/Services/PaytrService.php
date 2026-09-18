<?php

namespace App\Services;

use App\Models\Fatura;
use App\Models\Ayar;
use Illuminate\Support\Facades\Auth;

class PaytrService
{
    protected $merchant_id;
    protected $merchant_key;
    protected $merchant_salt;
    protected $test_mode;

    public function __construct()
    {
        // Yeni şema: ayarlar tablosu. Fallback: eski paytr tablosu.
        $a = \DB::table('ayarlar')->first();
        $this->merchant_id   = $a->paytr_merchant_id   ?? null;
        $this->merchant_key  = $a->paytr_merchant_key  ?? null;
        $this->merchant_salt = $a->paytr_merchant_salt ?? null;
        $this->test_mode     = isset($a->paytr_test_mode) ? (int) $a->paytr_test_mode : null;

        if (!$this->merchant_id || !$this->merchant_key || !$this->merchant_salt) {
            try {
                $paytr = \DB::table('paytr')->first();
                if ($paytr) {
                    $this->merchant_id   = $this->merchant_id   ?: ($paytr->magaza_no      ?? '');
                    $this->merchant_key  = $this->merchant_key  ?: ($paytr->magaza_parola  ?? '');
                    $this->merchant_salt = $this->merchant_salt ?: ($paytr->magaza_anahtar ?? '');
                    if ($this->test_mode === null) {
                        $this->test_mode = $paytr->test_modu ?? 1;
                    }
                }
            } catch (\Throwable $e) {}
        }

        $this->merchant_id   = (string) ($this->merchant_id   ?? '');
        $this->merchant_key  = (string) ($this->merchant_key  ?? '');
        $this->merchant_salt = (string) ($this->merchant_salt ?? '');
        $this->test_mode     = (int) ($this->test_mode ?? 1);
    }

    /**
     * PayTR ödeme isteği oluştur
     */
    public function createPayment($fatura_id, $return_url, $cancel_url)
    {
        $fatura = Fatura::findOrFail($fatura_id);
        $uye = $fatura->uye;

        // Sepet bilgileri
        $user_basket = base64_encode(json_encode([
            [
                'Fatura #' . $fatura->fatura_no,
                $fatura->toplam,
                1
            ]
        ]));

        // Kullanıcı IP
        $user_ip = request()->ip();

        // E-posta
        $email = $uye->email;

        // Ödeme tutarı (kuruş cinsinden)
        $payment_amount = $fatura->toplam * 100;

        // Merchant OID (sipariş numarası)
        $merchant_oid = 'FAT' . $fatura->id . time();

        // Kullanıcı adı
        $user_name = $uye->ad . ' ' . $uye->soyad;

        // Adres bilgileri
        $user_address = $uye->adres ?? 'Adres Bilgisi Yok';
        $user_phone = $uye->telefon ?? '0000000000';

        // PayTR parametreleri
        $no_installment = 0; // Taksit yapılmasını istemiyorsanız 1 yapın
        $max_installment = 0; // Maksimum taksit sayısı (0 = sınırsız)
        $currency = 'TL';
        $timeout_limit = 30;
        $debug_on = $this->test_mode ? 1 : 0;
        $test_mode = $this->test_mode ? 1 : 0;

        // Hash oluşturma (yedek klasöründeki doğru implementasyon)
        $hash_str = $this->merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket . 
                    $no_installment . $max_installment . $currency . $test_mode;
        $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $this->merchant_salt, $this->merchant_key, true));

        // PayTR API'ye gönderilecek veriler
        $post_data = [
            'merchant_id' => $this->merchant_id,
            'user_ip' => $user_ip,
            'merchant_oid' => $merchant_oid,
            'email' => $email,
            'payment_amount' => $payment_amount,
            'paytr_token' => $paytr_token,
            'user_basket' => $user_basket,
            'debug_on' => $debug_on,
            'no_installment' => $no_installment,
            'max_installment' => $max_installment,
            'user_name' => $user_name,
            'user_address' => $user_address,
            'user_phone' => $user_phone,
            'merchant_ok_url' => $return_url,
            'merchant_fail_url' => $cancel_url,
            'timeout_limit' => $timeout_limit,
            'currency' => $currency,
            'test_mode' => $test_mode,
        ];

        // cURL ile PayTR API'ye istek
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $isLocal = app()->environment('local') || in_array(request()->ip(), ['127.0.0.1','::1']);

        // CA bundle ara — composer'ın paketlediği bundle dahil
        $caPath = null;
        $candidates = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            base_path('vendor/composer/ca-bundle/res/cacert.pem'),
            base_path('vendor/symfony/http-client/cacert.pem'),
            'C:/wamp64/apps/phpmyadmin5.2.1/vendor/composer/ca-bundle/res/cacert.pem',
            'C:/wamp64/bin/php/php8.3.6/extras/ssl/cacert.pem',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
        ];
        foreach ($candidates as $p) {
            if ($p && @is_file($p)) { $caPath = $p; break; }
        }

        if ($caPath) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $caPath);
        } elseif ($isLocal) {
            // Local geliştirme: CA bundle yoksa SSL doğrulamasını kapat
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        } else {
            // Canlı: doğrulama aktif (sistem CA'sını kullansın)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($result, true);

        if ($result['status'] == 'success') {
            return [
                'status' => 'success',
                'token' => $result['token'],
                'iframe_url' => 'https://www.paytr.com/odeme/guvenli/' . $result['token']
            ];
        } else {
            throw new \Exception($result['reason'] ?? 'PayTR hatası');
        }
    }

    /**
     * PayTR callback doğrulama
     */
    public function verifyCallback($post_data)
    {
        $merchant_oid = $post_data['merchant_oid'];
        $status = $post_data['status'];
        $total_amount = $post_data['total_amount'];
        $hash = $post_data['hash'];

        // Hash kontrolü
        $hash_str = $merchant_oid . $this->merchant_salt . $status . $total_amount;
        $token = base64_encode(hash_hmac('sha256', $hash_str, $this->merchant_key, true));

        if ($hash != $token) {
            throw new \Exception('PAYTR notification failed: bad hash');
        }

        return [
            'merchant_oid' => $merchant_oid,
            'status' => $status,
            'total_amount' => $total_amount,
        ];
    }
}







