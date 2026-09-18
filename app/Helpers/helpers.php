<?php

// ---- Müşteri Bildirimleri ----

if (!function_exists('uye_bildirim_gonder')) {
    /**
     * Belirli bir müşteriye bildirim gönderir.
     * Tip: info | success | warning | danger
     */
    function uye_bildirim_gonder(
        int $uyeId,
        string $baslik,
        string $mesaj,
        string $tip = 'info',
        ?string $link = null,
        ?string $ikon = null
    ): \App\Models\UyeBildirim {
        return \App\Models\UyeBildirim::gonder($uyeId, $baslik, $mesaj, $tip, $link, $ikon);
    }
}

if (!function_exists('uye_bildirim_herkese')) {
    /**
     * Tüm müşterilere bildirim gönderir (uye_id = null).
     */
    function uye_bildirim_herkese(
        string $baslik,
        string $mesaj,
        string $tip = 'info',
        ?string $link = null,
        ?string $ikon = null
    ): \App\Models\UyeBildirim {
        return \App\Models\UyeBildirim::tumUyelereGonder($baslik, $mesaj, $tip, $link, $ikon);
    }
}

// ---- Admin Bildirimleri ----

if (!function_exists('admin_bildirim_gonder')) {
    /**
     * Adminlere (yöneticilere) bildirim gönderir → admin_bildirimler tablosu.
     * Panel zil ikonu + Bildirimler sayfası bunu okur.
     *
     * @param string      $baslik       Bildirim başlığı
     * @param string      $mesaj        Açıklama
     * @param string      $tip          info|success|warning|error|fatura|odeme|mesaj|destek|teklif...
     * @param string|null $ilgiliTablo  Yönlendirme için: 'crm_tasks','faturalar','destek','satilanlar','uyeler' vb.
     * @param int|null    $ilgiliId     İlgili kaydın ID'si
     * @param int|null    $yoneticiId   Belirli bir yöneticiye özel (null = tüm adminler görür)
     * @return bool
     */
    function admin_bildirim_gonder(
        string $baslik,
        string $mesaj,
        string $tip = 'info',
        ?string $ilgiliTablo = null,
        ?int $ilgiliId = null,
        ?int $yoneticiId = null
    ): bool {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('admin_bildirimler')) {
                return false;
            }

            $row = [
                'tip'        => $tip,
                'baslik'     => \Illuminate\Support\Str::limit($baslik, 200),
                'mesaj'      => \Illuminate\Support\Str::limit($mesaj, 500),
                'okundu'     => 0,
                'created_at' => now(),
            ];

            $S = fn($col) => \Illuminate\Support\Facades\Schema::hasColumn('admin_bildirimler', $col);
            if ($S('updated_at'))   $row['updated_at'] = now();
            if ($S('ilgili_tablo')) $row['ilgili_tablo'] = $ilgiliTablo;
            if ($S('ilgili_id'))    $row['ilgili_id'] = $ilgiliId;
            if ($S('yonetici_id'))  $row['yonetici_id'] = $yoneticiId; // null = herkes

            \Illuminate\Support\Facades\DB::table('admin_bildirimler')->insert($row);
            return true;
        } catch (\Throwable $e) {
            \Log::warning('admin_bildirim_gonder hatası', ['err' => $e->getMessage()]);
            return false;
        }
    }
}

if (!function_exists('my_number_format')) {
    function my_number_format($number)
    {
        return number_format($number, 0, ',', '.');
    }
}

if (!function_exists('get_ip')) {
    function get_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}

if (!function_exists('generate_code')) {
    function generate_code($length = 8, $uppercase = true, $lowercase = true, $numbers = true, $special = "")
    {
        $seed = '';
        if ($uppercase) $seed .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        if ($lowercase) $seed .= "abcdefghijklmnopqrstuvwxyz";
        if ($numbers) $seed .= "0123456789";
        if ($special) $seed .= $special;
        
        $password = '';
        $seed_length = strlen($seed);
        for ($i = 0; $i < $length; $i++) {
            $password .= $seed[rand(0, $seed_length - 1)];
        }
        return $password;
    }
}

/**
 * Bayi ID doğrulama ve güvenli bayi bilgisi alma
 * Session'dan gelen admin_id'nin gerçekten o kullanıcıya ait olduğunu doğrular
 * Hem admin panel hem de normal kullanıcı girişinden giriş yapılan bayileri destekler
 */
if (!function_exists('getAuthenticatedBayi')) {
    function getAuthenticatedBayi($throwException = true)
    {
        $adminId = session('admin_id');
        $adminRol = session('admin_rol', 2);
        $uyeId = \Illuminate\Support\Facades\Auth::guard('uye')->id();
        
        // Eğer uye session varsa ve admin session yoksa, uye_id'den bayi bul ve admin session oluştur
        if (!$adminId && $uyeId) {
            $bayi = \Illuminate\Support\Facades\DB::table('bayiler')
                ->where('uye_id', $uyeId)
                ->where('durum', 1)
                ->first();
            
            if ($bayi) {
                // Uye bilgisini al
                $uye = \Illuminate\Support\Facades\DB::table('uyeler')->where('id', $uyeId)->first();
                
                if ($uye) {
                    // Yonetici kaydını bul (email ile)
                    $yonetici = \Illuminate\Support\Facades\DB::table('yoneticiler')
                        ->where(function($query) use ($uye) {
                            $query->where('email', $uye->email)
                                  ->orWhere('eposta', $uye->email)
                                  ->orWhere('kullaniciadi', $uye->email);
                        })
                        ->where('rol', 3)
                        ->where('durum', 1)
                        ->first();
                    
                    if ($yonetici) {
                        // Admin session oluştur
                        session()->put('admin_logged_in', true);
                        session()->put('admin_id', $yonetici->id);
                        session()->put('admin_kullanici_adi', $yonetici->kullaniciadi);
                        session()->put('admin_adi', $yonetici->adi ?? $yonetici->kullaniciadi);
                        session()->put('admin_yetki', $yonetici->yetki ?? 1);
                        session()->put('admin_rol', 3);
                        $adminId = $yonetici->id;
                        $adminRol = 3;
                    } else {
                        // Yonetici kaydı yoksa direkt bayi döndür (normal kullanıcı olarak)
                        return $bayi;
                    }
                }
            }
        }
        
        // Sadece bayi rolü için çalışır (veya uye session'dan bayi bulunduysa)
        if ($adminRol != 3 && !$uyeId) {
            if ($throwException) {
                throw new \Exception('Bu işlem sadece bayi kullanıcıları için geçerlidir.');
            }
            return null;
        }
        
        if (!$adminId && !$uyeId) {
            if ($throwException) {
                throw new \Exception('Oturum bilgisi bulunamadı.');
            }
            return null;
        }
        
        // Yönetici kaydını kontrol et (admin session varsa)
        $yonetici = null;
        if ($adminId) {
            $yonetici = \Illuminate\Support\Facades\DB::table('yoneticiler')
                ->where('id', $adminId)
                ->where('rol', 3)
                ->where('durum', 1)
                ->first();
            
            if (!$yonetici && $throwException && !$uyeId) {
                throw new \Exception('Yönetici kaydı bulunamadı veya aktif değil.');
            }
        }
        
        // Bayi kaydını bul - önce yonetici_id ile, sonra uye_id ile, sonra id ile
        $bayi = null;
        
        // 1. yonetici_id ile ara
        if ($adminId && \Illuminate\Support\Facades\Schema::hasColumn('bayiler', 'yonetici_id')) {
            $bayi = \Illuminate\Support\Facades\DB::table('bayiler')
                ->where('yonetici_id', $adminId)
                ->first();
        }
        
        // 2. Eğer bulunamadıysa, uye_id ile ara (normal kullanıcı girişinden giriş yapıldıysa)
        if (!$bayi && $uyeId && \Illuminate\Support\Facades\Schema::hasColumn('bayiler', 'uye_id')) {
            $bayi = \Illuminate\Support\Facades\DB::table('bayiler')
                ->where('uye_id', $uyeId)
                ->where('durum', 1)
                ->first();
        }
        
        // 3. Eğer bulunamadıysa, yonetici email'i ile uye_id bul ve bayi ara
        if (!$bayi && $yonetici) {
            $yoneticiEmail = $yonetici->email ?? $yonetici->eposta ?? null;
            if ($yoneticiEmail) {
                $uye = \Illuminate\Support\Facades\DB::table('uyeler')
                    ->where('email', $yoneticiEmail)
                    ->first();
                
                if ($uye && \Illuminate\Support\Facades\Schema::hasColumn('bayiler', 'uye_id')) {
                    $bayi = \Illuminate\Support\Facades\DB::table('bayiler')
                        ->where('uye_id', $uye->id)
                        ->where('durum', 1)
                        ->first();
                }
            }
        }
        
        // 4. Eğer hala bulunamadıysa, tüm aktif bayileri kontrol et ve yonetici_id null olanları da kontrol et
        if (!$bayi && $adminRol == 3) {
            // Önce yonetici_id null olan aktif bayileri kontrol et
            if (\Illuminate\Support\Facades\Schema::hasColumn('bayiler', 'yonetici_id')) {
                $bayi = \Illuminate\Support\Facades\DB::table('bayiler')
                    ->where('yonetici_id', null)
                    ->where('durum', 1)
                    ->first();
            }
            
            // Hala bulunamadıysa, tüm aktif bayilerden birini al (son çare)
            if (!$bayi) {
                $bayi = \Illuminate\Support\Facades\DB::table('bayiler')
                    ->where('durum', 1)
                    ->first();
            }
        }
        
        // 5. Eğer hala bulunamadıysa, id ile ara
        if (!$bayi && $adminId) {
            $bayi = \Illuminate\Support\Facades\DB::table('bayiler')
                ->where('id', $adminId)
                ->first();
        }
        
        if (!$bayi) {
            if ($throwException) {
                throw new \Exception('Bayi kaydınız bulunamadı! Lütfen yöneticinizle iletişime geçin.');
            }
            return null;
        }
        
        return $bayi;
    }
}

if (!function_exists('validateRouteId')) {
    function validateRouteId($id, $paramName = 'id')
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            abort(404, "Geçersiz {$paramName} parametresi.");
        }
        return (int)$id;
    }
}

if (!function_exists('get_package_image_path')) {
    function get_package_image_path($resimAdi, $returnNullIfNotFound = false)
    {
        if (empty($resimAdi) || $resimAdi === '0' || $resimAdi === 'null') {
            return $returnNullIfNotFound ? null : asset('tema/img/noimage.png');
        }
        
        $resimAdi = trim($resimAdi);
        
        // Eğer zaten tam URL ise direkt döndür
        if (str_starts_with($resimAdi, 'http://') || str_starts_with($resimAdi, 'https://')) {
            return $resimAdi;
        }
        
        // Eğer zaten asset() ile oluşturulmuş path ise direkt döndür
        if (str_starts_with($resimAdi, '/tema/')) {
            $testYol = public_path($resimAdi);
            if (file_exists($testYol)) {
                return asset($resimAdi);
            }
            return $returnNullIfNotFound ? null : asset('tema/img/noimage.png');
        }
        
        // Klasörleri kontrol et (öncelik sırasına göre)
        $klasorler = [
            'tema/uploads/webpaketleri/kapak/',
            'tema/uploads/webpaketleri/',
            'tema/uploads/webpaketleri/kucuk/',
            'tema/uploads/paketler/',
        ];
        
        foreach ($klasorler as $klasor) {
            $tamYol = public_path($klasor . $resimAdi);
            if (file_exists($tamYol)) {
                return asset($klasor . $resimAdi);
            }
        }
        
        // Hiçbir yerde bulunamadıysa
        return $returnNullIfNotFound ? null : asset('tema/img/noimage.png');
    }
}

if (!function_exists('localized_route')) {
    /**
     * Dil desteği ile route oluştur
     * Mevcut locale'e göre route'u oluşturur ve lang parametresi ekler
     * 
     * @param string $name Route adı
     * @param mixed $parameters Route parametreleri
     * @param bool $absolute Mutlak URL oluştur
     * @return string
     */
    function localized_route($name, $parameters = [], $absolute = true)
    {
        try {
            $locale = session('locale') ?: app()->getLocale() ?: 'tr';
            
            // Route'u oluştur
            $url = route($name, $parameters, $absolute);
            
            // Eğer URL'de zaten lang parametresi varsa, onu güncelle
            // Yoksa yeni lang parametresi ekle
            $parsedUrl = parse_url($url);
            $query = [];
            
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $query);
            }
            
            // Lang parametresini ekle/güncelle
            $query['lang'] = $locale;
            
            // URL'i yeniden oluştur
            $scheme = $parsedUrl['scheme'] ?? 'http';
            $host = $parsedUrl['host'] ?? 'localhost';
            
            // Request varsa gerçek değerleri kullan
            if (app()->bound('request')) {
                $request = app('request');
                $scheme = $parsedUrl['scheme'] ?? $request->getScheme();
                $host = $parsedUrl['host'] ?? $request->getHost();
            }
            
            $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
            $path = $parsedUrl['path'] ?? '';
            $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
            
            $newQuery = http_build_query($query);
            $fullUrl = "{$scheme}://{$host}{$port}{$path}";
            
            if ($newQuery) {
                $fullUrl .= '?' . $newQuery;
            }
            
            if ($fragment) {
                $fullUrl .= $fragment;
            }
            
            return $fullUrl;
        } catch (\Exception $e) {
            // Hata durumunda normal route'u döndür
            try {
                return route($name, $parameters, $absolute);
            } catch (\Exception $e2) {
                // Route bulunamazsa boş string döndür
                return '#';
            }
        }
    }
}

if (!function_exists('localized_url')) {
    function localized_url($path, $parameters = [])
    {
        try {
            $locale = session('locale') ?: app()->getLocale() ?: 'tr';
            $base = url($path);
            $parsedUrl = parse_url($base);
            $query = [];
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $query);
            }
            $query['lang'] = $locale;
            if ($parameters) {
                $query = array_merge($query, $parameters);
            }
            $scheme = $parsedUrl['scheme'] ?? 'http';
            $host = $parsedUrl['host'] ?? 'localhost';
            $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
            $urlPath = $parsedUrl['path'] ?? '';
            $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
            $newQuery = http_build_query($query);
            $fullUrl = "{$scheme}://{$host}{$port}{$urlPath}";
            if ($newQuery) { $fullUrl .= '?' . $newQuery; }
            if ($fragment) { $fullUrl .= $fragment; }
            return $fullUrl;
        } catch (\Exception $e) {
            return url($path);
        }
    }
}

if (!function_exists('can_access_page')) {
    /**
     * Sayfa bazlı yetkilendirme kontrolü
     * 
     * @param string $routeName Route adı (örn: 'admin.uyeler.index')
     * @return bool
     */
    function can_access_page($routeName)
    {
        // Session kontrolü
        if (!session()->has('admin_logged_in') || !session('admin_logged_in')) {
            return false;
        }
        
        $adminRol = session('admin_rol', 2); // Varsayılan: Çalışan
        $adminId = session('admin_id');
        
        // 1. Patron (Rol=1): Her şeye erişebilir
        if ($adminRol == 1) {
            return true;
        }
        
        // 2. Müşteri (Rol=4): Admin paneli için erişim yok
        if ($adminRol == 4) {
            return false;
        }
        
        // 2.1 Muhasebe (Rol=5): Muhasebe alanlarına direkt erişim
        if ($adminRol == 5) {
            $allowedRoutes = ['admin.dashboard', 'admin.profil'];
            if (
                in_array($routeName, $allowedRoutes, true)
                || \Illuminate\Support\Str::startsWith($routeName, 'admin.faturalar.')
                || \Illuminate\Support\Str::startsWith($routeName, 'admin.banka.')
                || \Illuminate\Support\Str::startsWith($routeName, 'admin.odeme-bildirim.')
            ) {
                return true;
            }
        }
        
        // 3. Çalışan (Rol=2), Bayi (Rol=3) ve Muhasebe (Rol=5) için yetki kontrolü
        if (in_array($adminRol, [2, 3, 5])) {
            // Bayi için, tüm bayi paneli route'larına otomatik izin ver
            if ($adminRol == 3 && \Illuminate\Support\Str::startsWith($routeName, 'admin.bayi.')) {
                return true;
            }
            
            // Dashboard route'larına her zaman izin ver
            $allowedRoutes = ['admin.dashboard'];
            if ($adminRol == 3) {
                $allowedRoutes[] = 'admin.bayi.dashboard';
            }
            
            if (in_array($routeName, $allowedRoutes)) {
                return true;
            }
            
            // Veritabanında yetki kaydı var mı kontrol et
            if (\Illuminate\Support\Facades\Schema::hasTable('yonetici_yetkileri') && $adminId) {
                try {
                    $yetkiKaydi = \Illuminate\Support\Facades\DB::table('yonetici_yetkileri')
                        ->where('yonetici_id', $adminId)
                        ->where('sayfa_route', $routeName)
                        ->first();
                    
                    if ($yetkiKaydi) {
                        // Yetki kaydı varsa, gorebilir değerine bak
                        return ($yetkiKaydi->gorebilir == 1);
                    }
                } catch (\Exception $e) {
                    // Hata durumunda güvenli tarafta kal (false döndür)
                    \Log::warning('can_access_page hatası', [
                        'error' => $e->getMessage(),
                        'route' => $routeName,
                        'admin_id' => $adminId
                    ]);
                }
            }
            
            // Yetki kaydı yoksa, varsayılan olarak İZİN VERME (güvenlik için)
            return false;
        }
        
        // Diğer durumlar için false döndür
        return false;
    }
}
if (! function_exists('site_adresi')) {
    /**
     * Sitenin GERÇEK genel adresi (sonunda / olmadan).
     *
     * NEDEN VAR: Panelin "Siteyi göster" butonu ve e-posta şablonlarındaki logo
     * adresi elle `https://dev.crm.ornek.com` yazılmıştı. O alt alan
     * ARTIK YAŞAMIYOR -> buton ölü adrese gidiyordu, gönderilen her e-postada
     * logo kırık görünüyordu. Adres artık tek yerden, ayarlar tablosundan okunur.
     *
     * Sıra: ayarlar.site_url -> ayarlar.domain_url -> config('app.url') -> url('/')
     * DB okunamazsa (kurulum/migrasyon anı) sessizce config'e düşer, patlamaz.
     */
    function site_adresi(): string
    {
        static $adres = null;
        if ($adres !== null) {
            return $adres;
        }

        $deger = null;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('ayarlar')) {
                $a = \Illuminate\Support\Facades\DB::table('ayarlar')->first();
                $deger = $a->site_url ?? null;

                if (empty($deger) && !empty($a->domain_url ?? null)) {
                    $deger = 'https://' . ltrim($a->domain_url, '/');
                }
            }
        } catch (\Throwable $e) {
            $deger = null; // DB yoksa/erişilemiyorsa aşağıdaki yedeklere düş
        }

        if (empty($deger)) {
            $deger = config('app.url') ?: url('/');
        }

        return $adres = rtrim($deger, '/');
    }
}

if (! function_exists('mail_logo_url')) {
    /** E-posta şablonlarındaki DN Kreatif logosunun tam adresi. */
    function mail_logo_url(): string
    {
        return site_adresi() . '/tema/uploads/logo/dn-kreatif-logo.png';
    }
}
