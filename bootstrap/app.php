<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\ViewServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Web middleware grubu (sıralama önemli)
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SetCurrency::class,
            \App\Http\Middleware\HitCounter::class,        // Ziyaretçi sayaçları (online / bugün / dün / bu ay)
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\PageMaintenanceMode::class,
            \App\Http\Middleware\BrowserCache::class,
            \App\Http\Middleware\CompressResponse::class,
            \App\Http\Middleware\InvalidateDashboardCache::class,
        ]);
        
        // Rate limiting alias'ı zaten aşağıda tanımlanıyor
        
        // Alias middleware
        $middleware->alias([
            'uye.auth' => \App\Http\Middleware\UyeAuthMiddleware::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'rol' => \App\Http\Middleware\RolKontrol::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'tablolar.kilit' => \App\Http\Middleware\TablolarKilit::class,
        ]);

        // CSRF muafiyeti — Ödeme sağlayıcı bildirim (callback/webhook) URL'leri.
        // PayTR/iyzico sunucudan-sunucuya POST gönderir ve CSRF token taşımaz;
        // muaf tutulmazsa 419 TokenMismatch alır ve ödeme onayı asla işlenmez.
        // Gmail'in "tek tıkla abonelikten çık" özelliği de sunucudan POST atar
        // (RFC 8058) ve CSRF token taşımaz. Muaf tutulmazsa Gmail bu adresi
        // "çalışmıyor" sayar ve toplu maillerimiz spam'e düşmeye devam eder.
        // Güvenlik burada imzalı URL ile sağlanıyor (hasValidSignature).
        $middleware->validateCsrfTokens(except: [
            'payment/paytr/callback',
            'payment/iyzico/callback',
            'bulten-cik/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 (CSRF token mismatch / session expired) — kullanıcıyı tekrar giriş sayfasına yönlendir
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            $login = $request->is('admin/*') ? route('admin.giris', ['fresh' => 1]) : url('/giris');
            return redirect($login)->with('error', 'Oturum süresi doldu veya başka bir sekmede oturum kapatıldı. Lütfen tekrar giriş yapın.');
        });
    })->create();