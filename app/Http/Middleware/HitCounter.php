<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HitCounter
{
    /**
     * Ziyaretçi istatistikleri için eski `hit` tablosunu günceller.
     *
     * Online / Bugün / Dün / Bu Ay istatistikleri admin dashboard'da
     * bu tabloya göre hesaplanıyor.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Admin / API gibi path'leri sayma
            $path = $request->path();
            $excludedPrefixes = ['admin', 'api', 'yonetim', 'up'];
            foreach ($excludedPrefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return $next($request);
                }
            }

            if (!Schema::hasTable('hit')) {
                return $next($request);
            }

            $gun = (int) date('d');
            $ay  = (int) date('m');
            $yil = (int) date('Y');
            $ip  = $request->ip();

            // Bugün aynı IP ile giriş yapılmış mı?
            $bugunGiris = DB::table('hit')
                ->where('ip', $ip)
                ->where('gun', $gun)
                ->where('ay', $ay)
                ->where('yil', $yil)
                ->count();

            if ($bugunGiris > 0) {
                // Aynı IP+gün için son kaydı bul ve sayaç artır
                $al = DB::table('hit')
                    ->where('ip', $ip)
                    ->where('gun', $gun)
                    ->where('ay', $ay)
                    ->where('yil', $yil)
                    ->orderByDesc('id')
                    ->first();

                if ($al) {
                    DB::table('hit')
                        ->where('id', $al->id)
                        ->update([
                            'sayac' => (int) ($al->sayac ?? 0) + 1,
                            'simdi' => time(),
                        ]);
                }
            } else {
                // İlk giriş ise yeni kayıt oluştur
                DB::table('hit')->insert([
                    'gun'   => $gun,
                    'ay'    => $ay,
                    'yil'   => $yil,
                    'simdi' => time(),
                    'sayac' => 1,
                    'ip'    => $ip,
                ]);
            }
        } catch (\Throwable $e) {
            // Sayaç patlarsa siteyi bozmasın, sadece loglasın
            \Log::warning('HitCounter middleware error', [
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}

