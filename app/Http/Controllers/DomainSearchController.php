<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ResellerClubService;
use App\Helpers\DovizKuruHelper;

class DomainSearchController extends Controller
{
    protected $resellerClub;
    
    public function __construct(ResellerClubService $resellerClub)
    {
        $this->resellerClub = $resellerClub;
    }
    
    private $whoisServers = [
        "biz" => "whois.neulevel.biz",
        "com" => "whois.internic.net",
        "net" => "whois.internic.net",
        "org" => "whois.pir.org",
        "info" => "whois.nic.info",
        "ist" => "whois.internic.net",
        "istanbul" => "shop.whois.com",
        "website" => "whois.nic.website",
        "com.tr" => "whois.nic.tr",
        "net.tr" => "whois.nic.tr",
        "gen.tr" => "whois.nic.tr",
        "web.tr" => "whois.nic.tr",
        "org.tr" => "whois.nic.tr",
        "tv" => "whois.nic.tv",
        "cc" => "whois.nic.cc",
        "de" => "whois.nic.de",
        "uk" => "whois.nic.uk",
    ];
    
    public function sorgula(Request $request)
    {
        try {
            $alanadi = strtolower(trim($request->input('alanadi')));
            $uzanti = $request->input('uzanti');
            
            if (empty($alanadi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lütfen bir alan adı girin.'
                ]);
            }
            
            // Yasaklı karakterleri kontrol et
            if (!preg_match('/^[a-z0-9-]+$/', $alanadi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alan adında sadece harf, rakam ve tire (-) kullanabilirsiniz.'
                ]);
            }
            
            $results = [];
            
            // Tek uzantı mı yoksa çoklu mu?
            $uzantilar = is_array($uzanti) ? $uzanti : [$uzanti];
            
            foreach ($uzantilar as $ext) {
                $fullDomain = $alanadi . '.' . $ext;
                
                // ResellerClub API aktifse onu kullan, değilse whois kullan
                $musait = false;
                $fiyat = $this->getDomainPrice($ext);
                
                if (config('services.resellerclub.enabled')) {
                    // ResellerClub API ile kontrol et
                    $result = $this->resellerClub->checkAvailability($fullDomain);
                    if ($result['success']) {
                        $musait = $result['available'] ?? false;
                        // API'den fiyat geldiyse onu kullan
                        if (isset($result['price']) && $result['price'] > 0) {
                            $fiyat['kayit'] = $result['price'];
                        }
                    } else {
                        // API hatası varsa whois'e düş
                        $musait = $this->whoisSorgula($fullDomain, $ext);
                    }
                } else {
                    // ResellerClub kapalıysa whois kullan
                    $musait = $this->whoisSorgula($fullDomain, $ext);
                }
                
                $currency = request('currency') ?? session('currency', 'TRY');
                $kayitTry = $fiyat['kayit'] ?? 0.0;
                $yenilemeTry = $fiyat['yenileme'] ?? 0.0;

                // Para birimine göre HAM sayısal değer (formatlamayı frontend yapar).
                // ÖNEMLİ: fiyatGoster() Türkçe formatlı string döndürür ("1.200,00"),
                // onu (float) ile sarmak "1.2" hatası yaratıyordu. Bu yüzden sayısal çeviriyi
                // doğrudan tldenCevir ile yapıyoruz.
                $kayitDeger = ($currency === 'TRY' || $currency === 'TL')
                    ? $kayitTry
                    : \App\Helpers\DovizKuruHelper::tldenCevir($kayitTry, $currency);
                $yenilemeDeger = ($currency === 'TRY' || $currency === 'TL')
                    ? $yenilemeTry
                    : \App\Helpers\DovizKuruHelper::tldenCevir($yenilemeTry, $currency);

                $results[] = [
                    'domain' => $fullDomain,
                    'uzanti' => $ext,
                    'musait' => $musait,
                    'kayit_fiyat' => round((float) $kayitDeger, 2),
                    'yenileme_fiyat' => round((float) $yenilemeDeger, 2),
                    'currency' => $currency,
                    // Dolu domainlerde WHOIS detayı (port 43, dış API yok). Alınamazsa null.
                    'whois' => $musait ? null : $this->whoisDetay($fullDomain, $ext),
                ];
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function sepeteEkle(Request $request)
    {
        $domain = $request->input('domain');
        $fiyat = (float) $request->input('fiyat', 0.0);
        
        // Eğer fiyat 0 ise, domain fiyatını otomatik çek
        if ($fiyat <= 0 && $domain) {
            $extension = '.' . explode('.', $domain)[count(explode('.', $domain)) - 1] ?? '';
            $price_data = $this->getDomainPrice($extension);
            $fiyat = $price_data['kayit'] ?? 0.0;
        }
        
        // AJAX isteği kontrolü
        $is_ajax = $request->ajax() || 
                   $request->wantsJson() || 
                   $request->header('X-Requested-With') === 'XMLHttpRequest' ||
                   $request->header('Accept') === 'application/json';
        
        // Kullanıcı girişi kontrolü
        if (!auth()->guard('uye')->check()) {
            if ($is_ajax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sepete ürün eklemek için giriş yapmalısınız.',
                    'redirect' => route('giris')
                ], 401);
            }
            return redirect()->route('giris')->with('error', 'Sepete ürün eklemek için giriş yapmalısınız.');
        }
        
        $userId = auth()->guard('uye')->id();
        
        try {
            // Sepette bu domain var mı kontrol et - mevcut kolonları kullan
            $mevcutSepet = null;
            $domain_kolon_adi = null;
            
            // Hangi domain kolonu var kontrol et
            if (Schema::hasColumn('sepet', 'urun_adi')) {
                $domain_kolon_adi = 'urun_adi';
            } elseif (Schema::hasColumn('sepet', 'adi')) {
                $domain_kolon_adi = 'adi';
            } elseif (Schema::hasColumn('sepet', 'domain')) {
                $domain_kolon_adi = 'domain';
            }
            
            // Domain kolonu varsa kontrol et
            if ($domain_kolon_adi) {
                // Önce user_id ile dene
                if (Schema::hasColumn('sepet', 'user_id')) {
                    $mevcutSepet = DB::table('sepet')
                        ->where('user_id', $userId)
                        ->where($domain_kolon_adi, $domain)
                        ->first();
                }
                
                // user_id yoksa uyeid ile dene (eski format)
                if (!$mevcutSepet && Schema::hasColumn('sepet', 'uyeid')) {
                    $mevcutSepet = DB::table('sepet')
                        ->where('uyeid', $userId)
                        ->where($domain_kolon_adi, $domain)
                        ->first();
                }
                
                if ($mevcutSepet) {
                    if ($is_ajax) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Bu domain zaten sepetinizde.'
                        ], 400);
                    }
                    return redirect()->to(localized_route('sepet'))->with('error', 'Bu domain zaten sepetinizde.');
                }
            }
            
            // Sepete ekle - mevcut tablo yapısına göre
            $insertData = [];
            
            // User ID kolonu
            if (Schema::hasColumn('sepet', 'user_id')) {
                $insertData['user_id'] = $userId;
            } elseif (Schema::hasColumn('sepet', 'uyeid')) {
                $insertData['uyeid'] = $userId;
            }
            
            // urun_id kolonu (zorunlu olabilir)
            if (Schema::hasColumn('sepet', 'urun_id')) {
                // Domain için urun_id = domain'in hash değeri (unique olması için)
                $insertData['urun_id'] = abs(crc32($domain)); // Domain'in hash'i
            }
            
            // who kolonu (zorunlu olabilir - muhtemelen "who" veya "kim" gibi bir alan)
            if (Schema::hasColumn('sepet', 'who')) {
                $insertData['who'] = $userId; // Varsayılan olarak user_id
            }
            
            // Domain adı kolonu - yukarıda bulunan kolon adını kullan
            if ($domain_kolon_adi) {
                $insertData[$domain_kolon_adi] = $domain;
            }
            
            // name kolonu da varsa domain adını oraya kaydet
            if (Schema::hasColumn('sepet', 'name')) {
                $insertData['name'] = $domain;
            }
            
            // urun_adi kolonu varsa domain adını oraya kaydet
            if (Schema::hasColumn('sepet', 'urun_adi')) {
                $insertData['urun_adi'] = $domain;
            }
            
            // adi kolonu varsa domain adını oraya kaydet
            if (Schema::hasColumn('sepet', 'adi')) {
                $insertData['adi'] = $domain;
            }
            
            // domain kolonu varsa domain adını oraya kaydet
            if (Schema::hasColumn('sepet', 'domain')) {
                $insertData['domain'] = $domain;
            }
            
            // Ürün tipi kolonu
            if (Schema::hasColumn('sepet', 'urun_tipi')) {
                $insertData['urun_tipi'] = 'domain';
            } elseif (Schema::hasColumn('sepet', 'tipi')) {
                $insertData['tipi'] = 0; // 0: domain
            }
            
            // Fiyat kolonu - hem fiyat hem tutar kolonlarına yaz (varsalar)
            if (Schema::hasColumn('sepet', 'fiyat')) {
                $insertData['fiyat'] = $fiyat;
            }
            if (Schema::hasColumn('sepet', 'tutar')) {
                $insertData['tutar'] = $fiyat;
            }
            if (Schema::hasColumn('sepet', 'price')) {
                $insertData['price'] = $fiyat;
            }
            
            // Miktar/Süre kolonu
            if (Schema::hasColumn('sepet', 'miktar')) {
                $insertData['miktar'] = 1;
            } elseif (Schema::hasColumn('sepet', 'sure')) {
                $insertData['sure'] = 1; // 1 yıl
            } elseif (Schema::hasColumn('sepet', 'quantity')) {
                $insertData['quantity'] = 1;
            }
            
            // Açıklama kolonu
            if (Schema::hasColumn('sepet', 'aciklama')) {
                $insertData['aciklama'] = 'Domain Kayıt (1 yıl)';
            } elseif (Schema::hasColumn('sepet', 'description')) {
                $insertData['description'] = 'Domain Kayıt (1 yıl)';
            }
            
            // Tarih kolonları
            if (Schema::hasColumn('sepet', 'created_at')) {
                $insertData['created_at'] = now();
            }
            if (Schema::hasColumn('sepet', 'updated_at')) {
                $insertData['updated_at'] = now();
            }
            if (Schema::hasColumn('sepet', 'tarih')) {
                $insertData['tarih'] = date('Y-m-d H:i:s');
            }
            
            // En az bir kolon varsa ekle
            if (count($insertData) > 0) {
                DB::table('sepet')->insert($insertData);
            } else {
                throw new \Exception('Sepet tablosunda uygun kolon bulunamadı');
            }
            
            if ($is_ajax) {
                return response()->json([
                    'success' => true,
                    'message' => $domain . ' sepetinize eklendi!'
                ]);
            }
            
            return redirect()->to(localized_route('sepet'))->with('success', $domain . ' sepetinize eklendi!');
            
        } catch (\Exception $e) {
            \Log::error('Sepete ekleme hatası: ' . $e->getMessage(), [
                'domain' => $domain,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($is_ajax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sepete eklenirken hata: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Sepete eklenirken hata: ' . $e->getMessage());
        }
    }
    
    /**
     * Ham WHOIS metnini port 43 üzerinden çeker (dış API YOK).
     * Uzantı tanımlı değilse IANA'dan (yine port 43) whois sunucusu öğrenilir.
     */
    private function whoisHam(string $domain, string $uzanti): ?string
    {
        $server = $this->whoisServers[$uzanti] ?? $this->whoisSunucuBul($uzanti);
        if (!$server) {
            return null;
        }
        try {
            $fp = @fsockopen($server, 43, $errno, $errstr, 8);
            if (!$fp) {
                return null; // port 43 kapalı olabilir (paylaşımlı hosting)
            }
            stream_set_timeout($fp, 8);
            fputs($fp, $domain . "\r\n");
            $out = '';
            while (!feof($fp)) {
                $out .= fgets($fp, 512);
            }
            fclose($fp);
            $out = trim($out);
            return $out !== '' ? $out : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Bilinmeyen uzantı için IANA'dan yetkili whois sunucusunu bulur (port 43). */
    private function whoisSunucuBul(string $uzanti): ?string
    {
        try {
            $fp = @fsockopen('whois.iana.org', 43, $errno, $errstr, 6);
            if (!$fp) {
                return null;
            }
            stream_set_timeout($fp, 6);
            fputs($fp, $uzanti . "\r\n");
            $out = '';
            while (!feof($fp)) {
                $out .= fgets($fp, 256);
            }
            fclose($fp);
            if (preg_match('/^whois:\s*(\S+)/mi', $out, $m)) {
                return trim($m[1]);
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    /**
     * WHOIS detayı: kayıt eden firma, tarihler, name server'lar, durum.
     * Kayıtlı (dolu) domainler için gösterilir. Alınamazsa null döner.
     */
    private function whoisDetay(string $domain, string $uzanti): ?array
    {
        $ham = $this->whoisHam($domain, $uzanti);
        if (!$ham) {
            return null;
        }

        $al = function (array $anahtarlar) use ($ham) {
            foreach ($anahtarlar as $k) {
                if (preg_match('/^[ \t]*' . preg_quote($k, '/') . '[ \t]*:[ \t]*(.+)$/mi', $ham, $m)) {
                    $v = trim($m[1]);
                    if ($v !== '' && stripos($v, 'REDACTED') === false) {
                        return $v;
                    }
                }
            }
            return null;
        };

        $ns = [];
        if (preg_match_all('/^[ \t]*(?:Name Server|Nameserver|nserver|Domain Name Servers?)[ \t]*:[ \t]*(\S+)/mi', $ham, $mm)) {
            $ns = array_values(array_unique(array_map('strtolower', $mm[1])));
        }

        // Durum: "clientDeleteProhibited https://icann.org/epp#..." → sadece kod kısmı kalsın
        $durum = $al(['Domain Status', 'status']);
        if ($durum) {
            $durum = trim(preg_replace('#\s*https?://\S+#i', '', $durum));
            $durum = $durum !== '' ? $durum : null;
        }

        return [
            'registrar'  => $al(['Registrar', 'Sponsoring Registrar', 'Registrar Name']),
            'olusturma'  => $al(['Creation Date', 'Created On', 'Created Date', 'Domain Registration Date', 'created']),
            'bitis'      => $al(['Registry Expiry Date', 'Registrar Registration Expiration Date', 'Expiry Date', 'Expiration Date', 'Domain Expiration Date', 'paid-till']),
            'guncelleme' => $al(['Updated Date', 'Last Updated On', 'Last Modified', 'changed']),
            'durum'      => $durum,
            'ns'         => array_slice($ns, 0, 6),
            'ham'        => mb_substr($ham, 0, 3000),
        ];
    }

    private function whoisSorgula($domain, $uzanti)
    {
        // ── 1) RDAP (HTTP/443 tabanlı, cPanel'de port 43 kapalı olsa bile çalışır) ──
        // RDAP modern WHOIS'tir; .com/.net/.org/.info/.io vb. çoğu uzantıyı destekler.
        $rdap = $this->rdapKontrol($domain);
        if ($rdap !== null) {
            return $rdap; // true = müsait, false = kayıtlı
        }

        // ── 2) Klasik WHOIS (port 43 socket) — RDAP desteklemeyen uzantılar için ──
        // Bilinmeyen uzantı: WHOIS sunucusu tanımlı değil.
        if (!isset($this->whoisServers[$uzanti])) {
            return true; // Müsait varsay (kesin kontrol satın alma anında yapılır)
        }
        
        $server = $this->whoisServers[$uzanti];
        
        try {
            $fp = @fsockopen($server, 43, $errno, $errstr, 10);
            
            if (!$fp) {
                // WHOIS sunucusuna bağlanılamadı (port 43 kapalı olabilir).
                // "Dolu" saymak yerine müsait varsay; kesin kontrol satın almada yapılır.
                return true;
            }
            
            fputs($fp, $domain . "\r\n");
            
            $response = '';
            while (!feof($fp)) {
                $response .= fgets($fp);
            }
            
            fclose($fp);

            // Yanıt boşsa kesin karar verilemez → müsait varsay
            if (trim($response) === '') {
                return true;
            }
            
            // Türkiye uzantıları için özel kontrol
            if (strpos($uzanti, '.tr') !== false || $uzanti == 'ist' || $uzanti == 'istanbul') {
                if (stripos($response, 'Not found') !== false || 
                    stripos($response, 'No match found') !== false ||
                    stripos($response, 'No entries found') !== false ||
                    preg_match("/Not found in database/i", $response)) {
                    return true; // Müsait
                }
                return false; // Kayıtlı
            }
            
            // Diğer uzantılar için genel kontrol
            if (stripos($response, 'No match') !== false ||
                stripos($response, 'NOT FOUND') !== false ||
                stripos($response, 'No entries found') !== false ||
                stripos($response, 'No Data Found') !== false ||
                stripos($response, 'Not found') !== false ||
                stripos($response, 'Status: free') !== false ||
                stripos($response, 'is available') !== false) {
                return true; // Müsait
            }
            
            return false; // Kayıtlı
            
        } catch (\Exception $e) {
            // Hata durumunda kesin karar verme — müsait varsay
            return true;
        }
    }

    /**
     * RDAP ile domain müsaitlik kontrolü (HTTP/443 üzerinden, gerçek veri).
     * Dönüş: true = müsait, false = kayıtlı, null = RDAP karar veremedi (socket'e düş).
     */
    private function rdapKontrol($domain)
    {
        try {
            // rdap.org tüm RDAP destekli uzantılara yönlendirir
            $resp = \Illuminate\Support\Facades\Http::timeout(8)
                ->withHeaders(['Accept' => 'application/rdap+json'])
                ->get('https://rdap.org/domain/' . urlencode($domain));

            // 404 = kayıt bulunamadı = domain MÜSAİT
            if ($resp->status() === 404) {
                return true;
            }

            // 200 = kayıt var = domain KAYITLI (dolu)
            if ($resp->status() === 200) {
                return false;
            }

            // 400/501 vb. = bu uzantı RDAP desteklemiyor → socket whois'e bırak
            return null;
        } catch (\Throwable $e) {
            // Ağ hatası / timeout → karar verme, socket whois denesin
            return null;
        }
    }

    private function getDomainPrice(string $extension): array
    {
        // Uzantıyı normalize et: hem ".com" hem "com" aransın
        $extNokta  = '.' . ltrim(strtolower(trim($extension)), '.');  // ".com"
        $extDuz    = ltrim($extNokta, '.');                            // "com"

        // Öncelik domain_fiyatlar tablosu
        if (Schema::hasTable('domain_fiyatlar')) {
            $record = DB::table('domain_fiyatlar')
                ->whereIn('uzanti', [$extNokta, $extDuz])
                ->first();
            if ($record) {
                return [
                    'kayit' => (float) $record->kayit_fiyat,
                    'yenileme' => (float) $record->yenileme_fiyat,
                ];
            }
        }

        // Eski alanadi tablosu fallback
        if (Schema::hasTable('alanadi')) {
            $rows = DB::table('alanadi')->get();
            foreach ($rows as $row) {
                $uzantilar = json_decode($row->uzanti ?? '[]', true) ?: [];
                $kayitlar = json_decode($row->kayit ?? '[]', true) ?: [];
                $yenilemeler = json_decode($row->yenileme ?? '[]', true) ?: [];

                foreach ($uzantilar as $index => $uzanti) {
                    if (trim($uzanti, ". \t\n\r\0\x0B") === $extDuz) {
                        return [
                            'kayit' => isset($kayitlar[$index]) ? (float) $kayitlar[$index] : null,
                            'yenileme' => isset($yenilemeler[$index]) ? (float) $yenilemeler[$index] : null,
                        ];
                    }
                }
            }
        }

        return ['kayit' => null, 'yenileme' => null];
    }
}