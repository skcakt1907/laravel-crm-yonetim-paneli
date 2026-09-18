<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * METUnic API istemcisi (test/canlı ortam).
 *
 * Kimlik doğrulama: POST /login/auth (username, password) → oturum çerezi.
 * Çerez cache'te tutulur; 401/403 gelirse bir kez yenilenip istek tekrarlanır.
 *
 * Ayarlar (config/services.php → 'metunic'):
 *   url      => METUNIC_API_URL      (örn. https://api-test.metunic.com.tr/v1)
 *   username => METUNIC_USERNAME
 *   password => METUNIC_PASSWORD
 *
 * NOT: API, isteklerin yalnızca bildirilen IP'den (159.69.62.124) gelmesine izin verir.
 * Yani bu servis yalnızca sunucu üzerinde çalışır; localhost'tan çalışmaz.
 */
class MetunicService
{
    protected const CACHE_KEY = 'metunic_oturum_cookie';
    protected const CACHE_DK  = 20; // oturum çerezi cache süresi (dakika)

    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $cfg = config('services.metunic', []);
        $this->baseUrl  = rtrim($cfg['url'] ?? env('METUNIC_API_URL', 'https://api-test.metunic.com.tr/v1'), '/');
        $this->username = (string) ($cfg['username'] ?? env('METUNIC_USERNAME', ''));
        $this->password = (string) ($cfg['password'] ?? env('METUNIC_PASSWORD', ''));
    }

    /* =========================================================
     *  GENEL / TEST
     * ========================================================= */

    /** Bağlantı testi: giriş + kredi + örnek uygunluk sorgusu. Admin test sayfası kullanır. */
    public function baglantiTesti(): array
    {
        $sonuc = ['ayar' => ['url' => $this->baseUrl, 'kullanici_tanimli' => $this->username !== '']];

        // 1) Giriş
        $cookie = $this->oturumCookie(true);
        $sonuc['giris'] = $cookie !== '' ? '✓ BAŞARILI (oturum çerezi alındı)' : '✗ BAŞARISIZ (log dosyasına bakın)';
        if ($cookie === '') {
            return $sonuc;
        }

        // 2) Kredi bilgisi
        $kredi = $this->krediGetir();
        $sonuc['kredi'] = $kredi['ok'] ? $kredi['veri'] : ('✗ ' . ($kredi['hata'] ?? 'alınamadı'));

        // 3) Örnek domain sorgusu
        $d = $this->domainSorgula('ornek-deneme-' . time() . '.com');
        $sonuc['domain_sorgu'] = $d['ok'] ? $d['veri'] : ('✗ ' . ($d['hata'] ?? 'alınamadı'));

        // 4) Servis listesi (ilk sayfa)
        $s = $this->servisListesi();
        $sonuc['servis_listesi'] = $s['ok'] ? $s['veri'] : ('✗ ' . ($s['hata'] ?? 'alınamadı'));

        return $sonuc;
    }

    /* =========================================================
     *  DOMAIN İŞLEMLERİ
     * ========================================================= */

    /** Alan adı uygunluk sorgusu. */
    public function domainSorgula(string $domain): array
    {
        return $this->istek('GET', '/domains/check', ['domainName' => $domain]);
    }

    /** TLD bazında fiyat getir (örn. tld: com, duration: 1). */
    public function fiyatGetir(string $tld, int $yil = 1): array
    {
        return $this->istek('GET', '/pricings/pricings-tld', ['tld' => ltrim($tld, '.'), 'duration' => $yil]);
    }

    /** Hesaptaki kredi (cüzdan) bilgisi. */
    public function krediGetir(): array
    {
        return $this->istek('GET', '/transactions/credit');
    }

    /** Hesaptaki tüm servisleri (domainleri) listele — Domain Takip senkronu bunun üstüne kurulacak. */
    public function servisListesi(array $filtre = []): array
    {
        return $this->istek('GET', '/services/client-services', $filtre);
    }

    /** Tek servis (domain) bilgisi. */
    public function servisGetir(int $serviceId): array
    {
        return $this->istek('GET', '/services/' . $serviceId);
    }

    /** Servis durumu. */
    public function servisDurum(int $serviceId): array
    {
        return $this->istek('GET', '/services/' . $serviceId . '/status');
    }

    /** Süreye göre servis yenile (yil: 1-10). */
    public function servisYenile(int $serviceId, int $yil = 1): array
    {
        return $this->istek('POST', '/services/' . $serviceId . '/renew-duration', ['duration' => $yil]);
    }

    /* =========================================================
     *  CONTACT (İLETİŞİM) YÖNETİMİ — TLD satın alma için gerekli
     * ========================================================= */

    /** Contact listesi. */
    public function contactListesi(): array
    {
        return $this->istek('GET', '/contacts');
    }

    /** Tek contact getir. */
    public function contactGetir(int $id): array
    {
        return $this->istek('GET', '/contacts/' . $id);
    }

    /**
     * Contact ekle. Zorunlu: firstName, lastName, email, address1, city, state, zip, country, phoneNumber.
     * country: ISO kodu (örn. TR). phoneNumber: +90... formatı.
     * Dönen result içindeki contact ID, TLD satın almada owner/billing/technical/admin olarak kullanılır.
     */
    public function contactEkle(array $params): array
    {
        return $this->istek('POST', '/contacts/add', $params);
    }

    /* =========================================================
     *  SATIN ALMA (Swagger 1.4.3 ile birebir)
     * ========================================================= */

    /**
     * (TLD) Alan adı satın al — .com/.net vb.
     * Tüm parametreler query olarak gider (API böyle bekliyor).
     * owner/billing/technical/admin: contactEkle()'den dönen contact ID'leri (aynı ID dördüne de verilebilir).
     */
    public function domainSatinAlTld(
        string $domain,
        string $ns1,
        string $ns2,
        int $yil,
        int $ownerId,
        ?int $billingId = null,
        ?int $technicalId = null,
        ?int $adminId = null,
        array $ekNs = []
    ): array {
        $q = [
            'domainName' => $domain,
            'ns1'        => $ns1,
            'ns2'        => $ns2,
            'duration'   => $yil,
            'owner'      => $ownerId,
            'billing'    => $billingId ?? $ownerId,
            'technical'  => $technicalId ?? $ownerId,
            'admin'      => $adminId ?? $ownerId,
        ];
        foreach (array_values($ekNs) as $i => $ns) {
            if ($i < 3 && $ns) { $q['ns' . ($i + 3)] = $ns; }
        }
        return $this->istek('POST', '/orders/tld', $q);
    }

    /**
     * (TR) Alan adı satın al — .tr uzantıları.
     * Zorunlu: registrant_type, registrant_address1, registrant_address2, registrant_country,
     * registrant_city, registrant_postal_code, registrant_phone, registrant_email_address,
     * domain, ns1, ns2, duration. Şahıs için registrant_name + registrant_citizen_id;
     * şirket için registrant_organization + registrant_tax_office + registrant_tax_number.
     */
    public function domainSatinAlTr(array $params): array
    {
        return $this->istek('POST', '/orders/tr', $params);
    }

    /* =========================================================
     *  ALT YAPI: OTURUM + İSTEK
     * ========================================================= */

    /** Oturum çerezini getir (cache'ten veya yeni giriş yaparak). */
    protected function oturumCookie(bool $zorlaYenile = false): string
    {
        if (!$zorlaYenile) {
            $c = Cache::get(self::CACHE_KEY);
            if (is_string($c) && $c !== '') {
                return $c;
            }
        }

        if ($this->username === '' || $this->password === '') {
            Log::warning('Metunic: kullanıcı adı/parola tanımlı değil (.env METUNIC_USERNAME / METUNIC_PASSWORD).');
            return '';
        }

        try {
            $resp = Http::timeout(30)
                ->acceptJson()
                ->post($this->baseUrl . '/login/auth?' . http_build_query([
                    'username' => $this->username,
                    'password' => $this->password,
                ]));

            if (!$resp->successful()) {
                Log::error('Metunic auth başarısız', ['durum' => $resp->status(), 'govde' => mb_substr($resp->body(), 0, 500)]);
                return '';
            }

            // Set-Cookie başlıklarından "ad=değer" çiftlerini topla
            $setCookies = $resp->header('Set-Cookie') !== ''
                ? ($resp->getHeaders()['Set-Cookie'] ?? [$resp->header('Set-Cookie')])
                : ($resp->getHeaders()['set-cookie'] ?? []);

            $ciftler = [];
            foreach ((array) $setCookies as $sc) {
                $ilk = trim(explode(';', $sc)[0] ?? '');
                if ($ilk !== '' && strpos($ilk, '=') !== false) {
                    $ciftler[] = $ilk;
                }
            }

            $cookie = implode('; ', $ciftler);
            if ($cookie === '') {
                Log::error('Metunic auth: yanıt geldi ama Set-Cookie bulunamadı', ['govde' => mb_substr($resp->body(), 0, 500)]);
                return '';
            }

            Cache::put(self::CACHE_KEY, $cookie, now()->addMinutes(self::CACHE_DK));
            return $cookie;
        } catch (\Throwable $e) {
            Log::error('Metunic auth istisna: ' . $e->getMessage());
            return '';
        }
    }

    /** Ortak istek katmanı: çerez ekler, 401/403'te bir kez yeniden giriş yapıp tekrarlar. */
    protected function istek(string $method, string $yol, array $query = [], array $body = [], bool $tekrar = true): array
    {
        $cookie = $this->oturumCookie();
        if ($cookie === '') {
            return ['ok' => false, 'durum' => 0, 'veri' => null, 'hata' => 'Oturum açılamadı (auth başarısız)'];
        }

        try {
            $http = Http::timeout(40)
                ->acceptJson()
                ->withHeaders(['Cookie' => $cookie]);

            $url = $this->baseUrl . $yol;

            $resp = match (strtoupper($method)) {
                'GET'    => $http->get($url, $query),
                'POST'   => $http->post($url . ($query ? ('?' . http_build_query($query)) : ''), $body),
                'PUT'    => $http->put($url . ($query ? ('?' . http_build_query($query)) : ''), $body),
                'DELETE' => $http->delete($url . ($query ? ('?' . http_build_query($query)) : ''), $body),
                default  => throw new \InvalidArgumentException('Geçersiz metod: ' . $method),
            };

            // Oturum düşmüşse bir kez yenile ve tekrarla
            if (in_array($resp->status(), [401, 403], true) && $tekrar) {
                Cache::forget(self::CACHE_KEY);
                $this->oturumCookie(true);
                return $this->istek($method, $yol, $query, $body, false);
            }

            $json = null;
            try { $json = $resp->json(); } catch (\Throwable $e) { /* JSON değilse ham gövde kullanılacak */ }

            if (!$resp->successful()) {
                Log::warning('Metunic istek başarısız', [
                    'yol' => $yol, 'durum' => $resp->status(),
                    'govde' => mb_substr($resp->body(), 0, 500),
                ]);
                return [
                    'ok'    => false,
                    'durum' => $resp->status(),
                    'veri'  => $json,
                    'hata'  => is_array($json) ? ($json['message'] ?? $json['error'] ?? ('HTTP ' . $resp->status())) : ('HTTP ' . $resp->status()),
                ];
            }

            return ['ok' => true, 'durum' => $resp->status(), 'veri' => $json ?? $resp->body(), 'hata' => null];
        } catch (\Throwable $e) {
            Log::error('Metunic istek istisna: ' . $e->getMessage(), ['yol' => $yol]);
            return ['ok' => false, 'durum' => 0, 'veri' => null, 'hata' => $e->getMessage()];
        }
    }

    /* =========================================================
     *  LOOKUP (ülke / il kodları) — .tr satın almada gerekli
     * ========================================================= */

    /** (TR) Ülke listesi — /orders/tr registrant_country için ülke KODU (Türkiye=215). */
    public function ulkeListesi(): array
    {
        return $this->istek('GET', '/lookup/tr/countries');
    }

    /** (TR) İl/eyalet listesi — registrant_city için il KODU. $ulkeKodu: ulkeListesi kodu. */
    public function ilListesi(string $ulkeKodu): array
    {
        return $this->istek('GET', '/lookup/tr/states/' . rawurlencode($ulkeKodu));
    }
}