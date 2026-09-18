<?php

namespace App\Providers;

use App\Support\TrafikCron;
use Illuminate\Support\ServiceProvider;

/**
 * Trafik tetiklemeli zamanlayıcıyı devreye alır.
 *
 * Yanıt tarayıcıya gönderildikten SONRA çalışır (terminating), böylece
 * ziyaretçi hiçbir gecikme hissetmez. Konsol (artisan) çalıştırmalarında
 * devreye girmez — gerçek cron varsa çakışma olmasın diye.
 */
class TrafikCronServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Sadece web istekleri; artisan/queue çalıştırmalarında karışmasın
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->app->terminating(function () {
            TrafikCron::calistir();
        });
    }
}
