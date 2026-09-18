<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Admin oturumu kontrolü
        if (!session()->has('admin_logged_in') || !session('admin_logged_in')) {
            return redirect()->route('admin.giris')->with('error', 'Lütfen giriş yapın.');
        }

        return $next($request);
    }
}
