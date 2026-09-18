<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Helpers\TranslationHelper;
use App\View\Composers\TranslationComposer;
use App\Models\Satilan;
use App\Observers\SatilanObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        // Bootstrap 5 pagination kullan
        Paginator::useBootstrapFive();

        /*
         * whereTarihBetween() — iki tarih arasini GUN bazinda suzer.
         *
         * NEDEN VAR: Bu veritabanindaki tarih kolonlarinin cogu DATE/DATETIME
         * degil VARCHAR ve icinde iki bicim KARISIK duruyor:
         *     "2026-07-03"            (10 karakter, saatsiz)
         *     "2026-05-20 00:00:00"   (19 karakter, saatli)
         * Kolon metin oldugu icin whereBetween de METIN karsilastirmasi yapar
         * ve sinir gunlerinde kayit kaybolur -- iki yonde birden:
         *   - datetime sinir + saatsiz deger : '2026-07-01' >= '2026-07-01 00:00:00' FALSE
         *   - tarih-only sinir + saatli deger: '2026-07-31 14:00' <= '2026-07-31'    FALSE
         * Sonucu: raporlar/istatistikler sessizce EKSIK sayi gosterir.
         *
         * DATE() ile karsilastirinca saklanan bicim onemsizlesir. Indeks
         * kullanimini engeller ama bu tablolarda hacim dusuk; dogruluk onde.
         */
        \Illuminate\Database\Query\Builder::macro('whereTarihBetween', function (string $kolon, $bas, $son) {
            $g = fn ($d) => $d instanceof \DateTimeInterface
                ? $d->format('Y-m-d')
                : \Illuminate\Support\Carbon::parse($d)->toDateString();

            return $this->whereRaw("DATE($kolon) BETWEEN ? AND ?", [$g($bas), $g($son)]);
        });

        \Illuminate\Database\Eloquent\Builder::macro('whereTarihBetween', function (string $kolon, $bas, $son) {
            $this->getQuery()->whereTarihBetween($kolon, $bas, $son);

            return $this;
        });

        app()->useLangPath(base_path('lang'));

        // DB → Mail config bridge (admin/ayarlar/mail değişiklikleri runtime'da aktif)
        $this->loadMailConfigFromDb();
        
        // Rate Limiting - Güvenlik
        $this->configureRateLimiting();
        
        // Otomatik çeviri Blade directive'i
        Blade::directive('translate', function ($expression) {
            return "<?php echo \App\Helpers\TranslationHelper::translate($expression); ?>";
        });
        
        // Kısa versiyon
        Blade::directive('t', function ($expression) {
            return "<?php echo \App\Helpers\TranslationHelper::translate($expression); ?>";
        });
        
        // Yetki kontrolü Blade directive'i
        Blade::if('canAccess', function ($routeName) {
            return can_access_page($routeName);
        });
        
        // Tüm view'lara çeviri helper'ını ekle
        View::composer('*', TranslationComposer::class);

        // Aktif kampanya indirimleri (paket_id => indirim%) — paket kartları/detay bunu kullanır
        View::composer('*', function ($view) {
            static $map = null;
            if ($map === null) {
                $map = [];
                try {
                    if (Schema::hasTable('kampanyalar') && Schema::hasColumn('kampanyalar', 'paket_id')) {
                        $rows = \Illuminate\Support\Facades\DB::table('kampanyalar')
                            ->where('durum', 1)
                            ->whereNotNull('paket_id')
                            ->where('indirim', '>', 0)
                            ->where(function ($q) {
                                $q->whereNull('baslangic_tarihi')->orWhere('baslangic_tarihi', '<=', now());
                            })
                            ->where(function ($q) {
                                $q->whereNull('bitis_tarihi')->orWhere('bitis_tarihi', '>=', now());
                            })
                            ->get(['paket_id', 'indirim']);
                        foreach ($rows as $r) {
                            $pid = (int) $r->paket_id;
                            if (!isset($map[$pid]) || (float) $r->indirim > $map[$pid]) {
                                $map[$pid] = (float) $r->indirim;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $map = [];
                }
            }
            $view->with('paketIndirimleri', $map);
        });
        
        // Observer'ları kaydet
        Satilan::observe(SatilanObserver::class);
    }

    /**
     * admin/ayarlar/mail sayfasından kaydedilen SMTP bilgilerini runtime config'e bağla.
     * .env'deki MAIL_* değerlerini ezer — kullanıcı admin'den değiştirirse anında etkili.
     */
    protected function loadMailConfigFromDb(): void
    {
        try {
            // GÜVENLİK FRENİ: .env'de MAIL_MAILER=log / array ise DB'deki SMTP
            // bilgilerini bağlama — geliştirme makinesinde test yaparken gerçek
            // kişilere mail gitmesin. Canlıda MAIL_MAILER=smtp, etkilenmez.
            if (in_array(env('MAIL_MAILER', 'smtp'), ['log', 'array'], true)) return;

            if (!Schema::hasTable('ayarlar')) return;
            $a = \DB::table('ayarlar')->first();
            if (!$a) return;

            $host = $a->mail_host ?? null;
            $username = $a->mail_username ?? null;
            $password = $a->mail_password ?? null;

            // User+password yoksa hiçbir şey yapma (.env devrede kalsın)
            if (!$username || !$password) return;

            // Host boş veya 'smtp' gibi geçersiz ise .env'deki host kullanılsın
            if ($host && $host !== 'smtp') {
                \Config::set('mail.mailers.smtp.host', $host);
            }

            \Config::set('mail.default', 'smtp');
            \Config::set('mail.mailers.smtp.transport', 'smtp');
            \Config::set('mail.mailers.smtp.port', (int) ($a->mail_port ?? config('mail.mailers.smtp.port', 465)));
            \Config::set('mail.mailers.smtp.username', $username);
            \Config::set('mail.mailers.smtp.password', $password);
            \Config::set('mail.mailers.smtp.encryption', $a->mail_encryption ?? config('mail.mailers.smtp.encryption', 'ssl'));
            \Config::set('mail.from.address', $a->mail_from_address ?? $username);
            \Config::set('mail.from.name', $a->mail_from_name ?? config('mail.from.name', 'İş Ortağım'));
        } catch (\Throwable $e) {
            // sessiz: deploy/migration anlarında çağrılıyorsa hata atma
        }
    }

    /**
     * Rate limiting konfigürasyonu
     */
    protected function configureRateLimiting(): void
    {
        // Genel API limiti
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        
        // Login denemesi limiti (brute force koruması)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Çok fazla giriş denemesi. Lütfen 1 dakika bekleyin.'
                ], 429);
            });
        });
        
        // Form gönderimi limiti
        RateLimiter::for('form', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
        
        // İletişim formu limiti
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perHour(5)->by($request->ip())->response(function () {
                return back()->with('error', 'Çok fazla mesaj gönderdiniz. Lütfen daha sonra tekrar deneyin.');
            });
        });
    }
}
