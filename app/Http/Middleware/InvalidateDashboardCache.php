<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InvalidateDashboardCache
{
    /**
     * Admin tarafında veri değiştiren herhangi bir istek (POST/PUT/PATCH/DELETE)
     * dashboard istatistik cache'ini sıfırlar.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        // Sadece admin altındaki rotalar
        if (! $request->is('admin/*')) {
            return $response;
        }

        // Login/logout ve readonly endpoint'leri etkilemesin
        $path = $request->path();
        $skip = ['admin/giris', 'admin/login', 'admin/cikis', 'admin/logout'];
        foreach ($skip as $s) {
            if (str_starts_with($path, $s)) {
                return $response;
            }
        }

        // Yazma başarılıysa (2xx / 3xx) cache'i sıfırla
        if ($response->getStatusCode() < 400) {
            Cache::forget('admin_dashboard_stats');
        }

        return $response;
    }
}
