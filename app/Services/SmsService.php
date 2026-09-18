<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * NetGSM API endpoint
     */
    protected string $apiUrl;

    /**
     * NetGSM kullanıcı kodu
     */
    protected string $usercode;

    /**
     * NetGSM şifre
     */
    protected string $password;

    /**
     * SMS başlığı (msgheader)
     */
    protected string $msgheader;

    /**
     * Request timeout (saniye)
     */
    protected int $timeout;

    /**
     * SMS servisi aktif mi?
     */
    protected bool $enabled;

    /**
     * NetGSM hata kodları ve açıklamaları
     */
    protected array $errorCodes = [
        '00' => 'Başarılı',
        '01' => 'Başarılı (Olası gecikme)',
        '20' => 'Mesaj metninde hata var',
        '30' => 'Geçersiz kullanıcı adı, şifre veya yetki yok',
        '40' => 'Mesaj başlığı (header) kayıtlı değil',
        '50' => 'Abone hesabında yeterli kredi yok',
        '51' => 'Kredi yetersiz',
        '60' => 'Gönderilemedi',
        '70' => 'Hatalı sorgulama. Gönderdiğiniz parametrelerden birisi hatalı veya zorunlu alanlardan birisi eksik',
        '80' => 'Gönderilemedi',
        '85' => 'Mükerrer gönderim',
        '100' => 'Sistem hatası',
        '101' => 'Sistem hatası',
        '102' => 'Sistem hatası',
        '103' => 'Sistem hatası',
        '104' => 'Sistem hatası',
        '105' => 'Sistem hatası',
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $config = config('services.netgsm');

        // Önce .env'den oku, yoksa veritabanından oku
        // GET endpoint kullanıyoruz (daha stabil)
        // ÖNCELİK: Veritabanı (admin panelinden yönetilir). Boşsa .env/config fallback.
        // Not: ?: kullanıyoruz ki DB'de boş string varsa da config'e düşsün.
        $this->apiUrl = rtrim(($this->getFromDatabase('sms_post_url', '') ?: ($config['api_url'] ?? 'https://api.netgsm.com.tr/sms/send/get')), '/');
        $this->usercode = trim($this->getFromDatabase('sms_kullanici_adi', '') ?: ($config['usercode'] ?? ''));
        $this->password = trim($this->getFromDatabase('sms_sifre', '') ?: ($config['password'] ?? ''));
        // Tırnakları temizle (env'den geliyorsa)
        $msgheader = $this->getFromDatabase('sms_baslik', '') ?: ($config['msgheader'] ?? '');
        $this->msgheader = trim($msgheader, '"\'');
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->enabled = (bool) ($config['enabled'] ?? true);
    }

    /**
     * Veritabanından değer oku (fallback)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getFromDatabase(string $key, $default = '')
    {
        try {
            $ayarlar = \DB::table('ayarlar')->first();
            if ($ayarlar && isset($ayarlar->$key)) {
                return $ayarlar->$key;
            }
        } catch (\Exception $e) {
            // Veritabanı hatası durumunda sessizce devam et
        }

        return $default;
    }

    /**
     * SMS gönder
     *
     * @param string $phone Telefon numarası (5XXXXXXXXX formatında, başında 0 olmadan)
     * @param string $message Gönderilecek mesaj
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function send(string $phone, string $message): array
    {
        // Servis aktif mi kontrol et
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'SMS servisi devre dışı.',
                'data' => null,
            ];
        }

        // Validasyon
        $validation = $this->validate($phone, $message);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'data' => null,
            ];
        }

        // Telefon numarasını temizle ve formatla
        $phone = $this->formatPhone($phone);

        try {
            // NetGSM API GET method kullanıyor (daha stabil, XML formatında sorun var)
            // URL'den son `/` karakterini kaldır
            $apiUrl = rtrim($this->apiUrl, '/');
            
            // NetGSM API parametreleri (GET method, query string)
            // ÖNEMLİ: Mesajı elle urlencode ETME! Http::get() zaten otomatik encode eder.
            // Elle urlencode + Laravel encode = ÇİFT KODLAMA = Türkçe karakter bozulması.
            // Türkçe karakter için 'dil' ve 'encoding' = TR gönderiyoruz (NetGSM send/get).
            $params = [
                'usercode' => $this->usercode,
                'password' => $this->password,
                'gsmno' => $phone,
                'message' => $message,
                'msgheader' => $this->msgheader,
                'dil' => 'TR',
                'encoding' => 'TR',
                'filter' => '0',
            ];
            
            // Gerçek sunucu IP'sini al
            $serverIp = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'unknown';
            $isLocalhost = in_array($serverIp, ['127.0.0.1', '::1', 'localhost']) || 
                          in_array(request()->ip(), ['127.0.0.1', '::1', 'localhost']);
            
            // Localhost'ta çalışıyorsa, dış IP'yi al (NetGSM IP kısıtlaması için)
            $externalIp = null;
            if ($isLocalhost) {
                try {
                    $externalIp = @file_get_contents('https://api.ipify.org', false, stream_context_create([
                        'http' => ['timeout' => 3]
                    ]));
                    if ($externalIp) {
                        $externalIp = trim($externalIp);
                    }
                } catch (\Exception $e) {
                    // Hata olursa sessizce devam et
                }
            }
            
            // Debug için log
            Log::info('NetGSM SMS gönderiliyor', [
                'url' => $apiUrl,
                'usercode' => $this->usercode,
                'password_length' => strlen($this->password),
                'password_preview' => substr($this->password, 0, 2) . '***' . substr($this->password, -2), // İlk 2 ve son 2 karakter
                'phone' => $phone,
                'msgheader' => $this->msgheader,
                'message_length' => strlen($message),
                'server_ip' => $serverIp,
                'external_ip' => $externalIp ?? 'not_fetched',
                'client_ip' => request()->ip() ?? 'unknown',
                'is_localhost' => $isLocalhost,
                'note' => $isLocalhost ? 'Localhost - NetGSM IP kısıtlaması varsa çalışmayabilir' : 'Production sunucu',
                'params' => array_merge($params, ['password' => '***']), // Şifreyi gizle
            ]);
            
            // SSL sertifika doğrulamasını devre dışı bırak (local development için)
            // User-Agent ekle (bazı API'ler bunu ister)
            $response = Http::timeout($this->timeout)
                ->withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Laravel-SMS-Service/1.0',
                ])
                ->get($apiUrl, $params);

            // Response'u işle
            $result = $this->handleResponse($response, $phone, $message);
            
            // handleResponse her zaman array döndürmeli, ama yine de kontrol et
            if (!is_array($result)) {
                Log::error('handleResponse beklenmeyen dönüş tipi', [
                    'result_type' => gettype($result),
                    'result_value' => $result,
                ]);
                return [
                    'success' => false,
                    'message' => 'SMS servisi beklenmeyen bir yanıt döndürdü.',
                    'data' => [
                        'error' => 'Invalid response type',
                        'phone' => $phone,
                    ],
                ];
            }
            
            return $result;

        } catch (\Exception $e) {
            Log::error('NetGSM SMS gönderim hatası', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS gönderilirken bir hata oluştu: ' . $e->getMessage() . ' (Detaylar için log dosyasını kontrol edin)',
                'data' => [
                    'error' => $e->getMessage(),
                    'phone' => $phone,
                ],
            ];
        } catch (\Throwable $e) {
            // PHP 7+ için Throwable yakalama
            Log::error('NetGSM SMS gönderim fatal hatası', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS gönderilirken kritik bir hata oluştu: ' . $e->getMessage(),
                'data' => [
                    'error' => $e->getMessage(),
                    'phone' => $phone,
                ],
            ];
        }
    }

    /**
     * Telefon numarasını formatla
     *
     * @param string $phone
     * @return string
     */
    protected function formatPhone(string $phone): string
    {
        // Sadece rakamları al
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Başında 0 varsa kaldır
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // 90 ile başlıyorsa kaldır (uluslararası format)
        if (str_starts_with($phone, '90')) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Validasyon
     *
     * @param string $phone
     * @param string $message
     * @return array
     */
    protected function validate(string $phone, string $message): array
    {
        // Kullanıcı kodu kontrolü
        if (empty($this->usercode)) {
            return [
                'valid' => false,
                'message' => 'NetGSM kullanıcı kodu (usercode) yapılandırılmamış.',
            ];
        }

        // Şifre kontrolü
        if (empty($this->password)) {
            return [
                'valid' => false,
                'message' => 'NetGSM şifre (password) yapılandırılmamış.',
            ];
        }

        // Başlık kontrolü
        if (empty($this->msgheader)) {
            return [
                'valid' => false,
                'message' => 'NetGSM mesaj başlığı (msgheader) yapılandırılmamış.',
            ];
        }

        // Telefon numarası kontrolü
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone) || strlen($phone) < 10) {
            return [
                'valid' => false,
                'message' => 'Geçersiz telefon numarası.',
            ];
        }

        // Mesaj kontrolü
        if (empty(trim($message))) {
            return [
                'valid' => false,
                'message' => 'Mesaj içeriği boş olamaz.',
            ];
        }

        return ['valid' => true];
    }

    /**
     * API response'unu işle
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param string $phone
     * @param string $message
     * @return array
     */
    protected function handleResponse($response, string $phone, string $message): array
    {
        $statusCode = $response->status();
        $body = trim($response->body());

        // HTTP hatası kontrolü
        if ($statusCode !== 200) {
            Log::error('NetGSM HTTP hatası', [
                'status_code' => $statusCode,
                'body' => $body,
                'phone' => $phone,
            ]);

            return [
                'success' => false,
                'message' => "HTTP hatası: {$statusCode}",
                'data' => [
                    'status_code' => $statusCode,
                    'response' => $body,
                ],
            ];
        }

        // NetGSM response kodunu kontrol et
        $errorCode = substr($body, 0, 2);
        $errorMessage = $this->errorCodes[$errorCode] ?? 'Bilinmeyen hata';

        // Başarılı kodlar: 00, 01
        if (in_array($errorCode, ['00', '01'])) {
            Log::info('NetGSM SMS başarıyla gönderildi', [
                'phone' => $phone,
                'code' => $errorCode,
                'response' => $body,
            ]);

            return [
                'success' => true,
                'message' => 'SMS başarıyla gönderildi.',
                'data' => [
                    'code' => $errorCode,
                    'response' => $body,
                    'phone' => $phone,
                ],
            ];
        }

        // Hata durumu
        Log::warning('NetGSM SMS gönderilemedi', [
            'phone' => $phone,
            'code' => $errorCode,
            'message' => $errorMessage,
            'response' => $body,
        ]);

        // Daha açıklayıcı hata mesajı
        $userFriendlyMessage = "SMS gönderilemedi! ";
        $userFriendlyMessage .= "Hata: {$errorMessage} ";
        $userFriendlyMessage .= "(Hata Kodu: {$errorCode})";
        
        // Özel hata kodları için ek açıklamalar
        if ($errorCode == '30') {
            $userFriendlyMessage .= " - Kullanıcı adı, şifre veya yetki kontrolü yapın.";
        } elseif ($errorCode == '40') {
            $userFriendlyMessage .= " - SMS başlığınızın NetGSM'de onaylı olduğundan emin olun.";
        } elseif ($errorCode == '50' || $errorCode == '51') {
            $userFriendlyMessage .= " - NetGSM hesabınızda yeterli kredi olduğundan emin olun.";
        }

        return [
            'success' => false,
            'message' => $userFriendlyMessage,
            'data' => [
                'code' => $errorCode,
                'response' => $body,
                'phone' => $phone,
                'error_message' => $errorMessage,
            ],
        ];
    }

    /**
     * XML body oluştur
     *
     * @param string $phone
     * @param string $message
     * @return string
     */
    protected function buildXmlBody(string $phone, string $message): string
    {
        // NetGSM XML formatı - boşluklar olmadan, tek satır formatında
        // CDATA kullanarak mesajı güvenli hale getir
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<mainbody>';
        $xml .= '<header>';
        $xml .= '<usercode>' . htmlspecialchars($this->usercode, ENT_XML1, 'UTF-8') . '</usercode>';
        $xml .= '<password>' . htmlspecialchars($this->password, ENT_XML1, 'UTF-8') . '</password>';
        $xml .= '<msgheader>' . htmlspecialchars($this->msgheader, ENT_XML1, 'UTF-8') . '</msgheader>';
        $xml .= '</header>';
        $xml .= '<body>';
        $xml .= '<msg><![CDATA[' . $message . ']]></msg>';
        $xml .= '<no>' . htmlspecialchars($phone, ENT_XML1, 'UTF-8') . '</no>';
        $xml .= '</body>';
        $xml .= '</mainbody>';
        
        return $xml;
    }

    /**
     * Doğrulama kodu SMS'i gönder
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    public function sendVerificationCode(string $phone, string $code): array
    {
        $message = "Doğrulama kodunuz: {$code}\n\nBu kodu kimseyle paylaşmayın.";
        return $this->send($phone, $message);
    }

    /**
     * Doğrulama kodu gönder (Türkçe metod adı - uyumluluk için)
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    public function dogrulamaKoduGonder(string $phone, string $code): array
    {
        return $this->sendVerificationCode($phone, $code);
    }
}