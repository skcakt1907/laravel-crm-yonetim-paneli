<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = ['tr', 'en', 'ar'];
        $defaultLocale = 'tr';
        $localeMap = [
            'tr' => 1,
            'en' => 2,
            'ar' => 3,
        ];

        $applyLocale = function (string $locale) use ($localeMap): void {
            app()->setLocale($locale);
            session([
                'locale' => $locale,
                'k_dil' => $localeMap[$locale] ?? 1,
            ]);
        };

        // Redirect'ten muaf tutulacak route'lar
        $excludedPaths = [
            'admin',
            'api',
            '_class',
            'payment',
            'odeme',
            'sitemap.xml',
        ];

        $currentPath = $request->path();
        $shouldExclude = false;
        foreach ($excludedPaths as $excludedPath) {
            if (str_starts_with($currentPath, $excludedPath)) {
                $shouldExclude = true;
                break;
            }
        }

        // 1. URL'den dil parametresi (öncelikli)
        $requestedLocale = $request->get('lang');
        if ($requestedLocale && in_array($requestedLocale, $availableLocales, true)) {
            $applyLocale($requestedLocale);
            // Cookie'lere kaydet — withCookie() StreamedResponse/file download'larda yok,
            // headers->setCookie() tüm response tiplerinde çalışır (export route'ları kırılmasın).
            $response = $next($request);
            $response->headers->setCookie(cookie('locale_detected', '1', 60 * 24 * 365)); // 1 yıl
            $response->headers->setCookie(cookie('locale_preference', $requestedLocale, 60 * 24 * 365)); // Seçilen dili kaydet
            return $response;
        }

        // 2. Cookie ve Session kontrolü
        $localeDetectedCookie = $request->cookie('locale_detected');
        $cookieLocale = $request->cookie('locale_preference');
        $sessionLocale = session('locale');
        
        // 3. Tarayıcı dilini doğru algıla
        $detectedLocale = $defaultLocale;
        
        // Accept-Language header'ını direkt oku
        $acceptLanguage = $request->server('HTTP_ACCEPT_LANGUAGE', '');
        if ($acceptLanguage) {
            // Örnek: "tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7"
            $languages = [];
            $parts = explode(',', $acceptLanguage);
            foreach ($parts as $part) {
                $langPart = trim(explode(';', $part)[0]);
                $langCode = strtolower(substr($langPart, 0, 2));
                if (in_array($langCode, $availableLocales, true)) {
                    $detectedLocale = $langCode;
                    break; // İlk eşleşen dili kullan
                }
            }
        }
        
        // Eğer hala varsayılan dilse, getLanguages() metodunu dene
        if ($detectedLocale === $defaultLocale) {
            $browserLanguages = $request->getLanguages();
            foreach ($browserLanguages as $browserLang) {
                $langCode = strtolower(substr($browserLang, 0, 2));
                if (in_array($langCode, $availableLocales, true)) {
                    $detectedLocale = $langCode;
                    break;
                }
            }
        }
        
        // Tarayıcı dili cookie/session'dan farklıysa, tarayıcı dilini kullan
        $shouldUseBrowserLocale = false;
        if (!$localeDetectedCookie) {
            // İlk ziyaret - tarayıcı dilini kullan
            $shouldUseBrowserLocale = true;
        }
        
        // Tarayıcı dili kullanılacaksa redirect yap (sadece GET isteklerinde!)
        if ($request->isMethod('GET') && $shouldUseBrowserLocale && !$shouldExclude && !$request->has('lang')) {
            // URL'e dil parametresi ekleyerek redirect yap
            $currentUrl = $request->fullUrl();
            $parsedUrl = parse_url($currentUrl);
            $queryParams = [];
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            // lang parametresini güncelle
            $queryParams['lang'] = $detectedLocale;
            $newQuery = http_build_query($queryParams);
            // Port bilgisini koru
            $scheme = $parsedUrl['scheme'] ?? $request->getScheme();
            $host = $parsedUrl['host'] ?? $request->getHost();
            $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : $request->getPort();
            $portPart = ($port && !in_array($port, [80, 443], true)) ? ':' . $port : '';
            $path = $parsedUrl['path'] ?? '/';
            $redirectUrl = "{$scheme}://{$host}{$portPart}{$path}?{$newQuery}";
            
            // Cookie'lere kaydet (güncelle) ve session'ı da temizle
            session()->forget('locale');
            session()->reflash(); // flash mesajlar (toast) dil-redirect'te kaybolmasin
            $response = redirect($redirectUrl);
            return $response
                ->withCookie(cookie('locale_detected', '1', 60 * 24 * 365)) // 1 yıl
                ->withCookie(cookie('locale_preference', $detectedLocale, 60 * 24 * 365)); // Algılanan dili kaydet/güncelle
        }
        
        // Session'dan dil (redirect yapılmadıysa)
        if ($sessionLocale && in_array($sessionLocale, $availableLocales, true)) {
            $applyLocale($sessionLocale);
            // Eğer URL'de lang parametresi yoksa ve dil Türkçe değilse, URL'e ekle (sadece GET)
            if ($request->isMethod('GET') && !$request->has('lang') && $sessionLocale !== $defaultLocale) {
                $currentUrl = $request->fullUrl();
                $parsedUrl = parse_url($currentUrl);
                $queryParams = [];
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                }
                if (!isset($queryParams['lang'])) {
                    $queryParams['lang'] = $sessionLocale;
                    $newQuery = http_build_query($queryParams);
                    // Port bilgisini koru
                    $scheme = $parsedUrl['scheme'] ?? $request->getScheme();
                    $host = $parsedUrl['host'] ?? $request->getHost();
                    $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : $request->getPort();
                    $portPart = ($port && !in_array($port, [80, 443], true)) ? ':' . $port : '';
                    $path = $parsedUrl['path'] ?? '/';
                    $redirectUrl = "{$scheme}://{$host}{$portPart}{$path}?{$newQuery}";
                    session()->reflash(); // flash mesajlar (toast) dil-redirect'te kaybolmasin
                    return redirect($redirectUrl);
                }
            }
            return $next($request);
        }
        
        // Cookie'den dil (redirect yapılmadıysa)
        if ($cookieLocale && in_array($cookieLocale, $availableLocales, true)) {
            $applyLocale($cookieLocale);
            // Eğer URL'de lang parametresi yoksa ve dil Türkçe değilse, URL'e ekle (sadece GET)
            if ($request->isMethod('GET') && !$request->has('lang') && $cookieLocale !== $defaultLocale) {
                $currentUrl = $request->fullUrl();
                $parsedUrl = parse_url($currentUrl);
                $queryParams = [];
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                }
                if (!isset($queryParams['lang'])) {
                    $queryParams['lang'] = $cookieLocale;
                    $newQuery = http_build_query($queryParams);
                    // Port bilgisini koru
                    $scheme = $parsedUrl['scheme'] ?? $request->getScheme();
                    $host = $parsedUrl['host'] ?? $request->getHost();
                    $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : $request->getPort();
                    $portPart = ($port && !in_array($port, [80, 443], true)) ? ':' . $port : '';
                    $path = $parsedUrl['path'] ?? '/';
                    $redirectUrl = "{$scheme}://{$host}{$portPart}{$path}?{$newQuery}";
                    session()->reflash(); // flash mesajlar (toast) dil-redirect'te kaybolmasin
                    return redirect($redirectUrl);
                }
            }
            return $next($request);
        }

        // 4. Varsayılan dil (redirect yapılmadıysa)
        $applyLocale($detectedLocale);
        
        // Eğer algılanan dil Türkçe değilse ve URL'de lang parametresi yoksa, URL'e ekle (sadece GET)
        if ($request->isMethod('GET') && !$request->has('lang') && $detectedLocale !== $defaultLocale && !$shouldExclude) {
            $currentUrl = $request->fullUrl();
            $parsedUrl = parse_url($currentUrl);
            $queryParams = [];
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
            }
            if (!isset($queryParams['lang'])) {
                $queryParams['lang'] = $detectedLocale;
                $newQuery = http_build_query($queryParams);
                // Port bilgisini koru
                $scheme = $parsedUrl['scheme'] ?? $request->getScheme();
                $host = $parsedUrl['host'] ?? $request->getHost();
                $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : $request->getPort();
                $portPart = ($port && !in_array($port, [80, 443], true)) ? ':' . $port : '';
                $path = $parsedUrl['path'] ?? '/';
                $redirectUrl = "{$scheme}://{$host}{$portPart}{$path}?{$newQuery}";
                session()->reflash(); // flash mesajlar (toast) dil-redirect'te kaybolmasin
                return redirect($redirectUrl);
            }
        }

        return $next($request);
    }
}
