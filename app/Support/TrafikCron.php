<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * TRAFİK TETİKLEMELİ ZAMANLAYICI ("cron'suz cron")
 *
 * Sunucuda gerçek cron (php artisan schedule:run) kurulamadığında, siteye gelen
 * normal ziyaretlerin üstüne binerek zamanı gelmiş görevleri çalıştırır.
 * Yanıt kullanıcıya gönderildikten SONRA (terminating) koşar; ziyaretçi beklemez.
 *
 * NEDEN schedule:run ÇAĞIRMIYORUZ?
 * Laravel'in scheduler'ı dakika hassasiyetlidir: dailyAt('09:30') görevi ancak
 * schedule:run tam 09:30 dakikasında çalışırsa tetiklenir. Trafiğe bağlı
 * tetiklemede o dakikayı yakalama garantisi yoktur → günlük işler kaçardı.
 * Bu yüzden burada TELAFİLİ (catch-up) mantık var: "bugün çalışmış mı + saati
 * geçmiş mi" diye bakar, 09:30'u kaçırsa bile ilk fırsatta çalıştırır.
 *
 * GÜVENLİK KAPAKLARI
 *  - Aynı anda tek çalışma (kilit dosyası)
 *  - İstek başına EN FAZLA 1 görev → hiçbir istek uzun sürmez
 *  - En çok gecikmiş görev önce → günlük işler, dakikalık işlerin altında ezilmez
 *  - Her şey try/catch içinde; hata siteyi ASLA etkilemez
 *  - .env'de TRAFIK_CRON=false ile tamamen kapatılır
 *
 * GERÇEK CRON KURULURSA: .env'e TRAFIK_CRON=false ekle ki iki sistem
 * aynı işi iki kez yapmaya çalışmasın.
 */
class TrafikCron
{
    /** İki çalışma arasındaki en az süre (saniye) — sunucuyu yormamak için */
    private const ASGARI_ARALIK = 20;

    /**
     * Görev listesi — routes/console.php'deki zamanlamanın aynısı.
     * tip: dakikalik | saatlik | gunluk | haftalik
     */
    private const GOREVLER = [
        ['komut' => 'randevu:hatirlat',              'tip' => 'dakikalik'],
        ['komut' => 'webp:uret',                     'tip' => 'dakikalik'],
        ['komut' => 'mail:teklif-hatirlat',          'tip' => 'saatlik'],
        ['komut' => 'logs:clear',                    'tip' => 'gunluk',   'saat' => '03:00'],
        ['komut' => 'dm:ses-temizle',                'tip' => 'gunluk',   'saat' => '03:30'],
        ['komut' => 'mail:odeme-hatirlat',           'tip' => 'gunluk',   'saat' => '09:00'],
        ['komut' => 'mail:aylik-odeme-hatirlat',     'tip' => 'gunluk',   'saat' => '09:30'],
        ['komut' => 'mail:domain-yenileme-hatirlat', 'tip' => 'gunluk',   'saat' => '10:00'],
        ['komut' => 'mail:domain-admin-uyari',       'tip' => 'gunluk',   'saat' => '10:15'],
        ['komut' => 'mail:hizmet-bitis-hatirlat',    'tip' => 'gunluk',   'saat' => '10:30'],
        ['komut' => 'domain:sesli-ara',              'tip' => 'gunluk',   'saat' => '11:00'],
        ['komut' => 'cache:clear',                   'tip' => 'haftalik', 'gun' => 0, 'saat' => '04:00'],

        /*
         * MUHASEBE (görev #217) — 31.07.2026'da eklendi.
         *
         * DİKKAT: Bu işler routes/console.php'ye de yazılı ama sunucuda gerçek
         * cron olmadığı için asıl çalıştıran YER BURASI. Listeye eklenmezse
         * hiç tetiklenmezler (ilk pakette bu atlanmıştı).
         */
        ['komut' => 'proforma:uret',                 'tip' => 'gunluk',   'saat' => '08:30'],
        ['komut' => 'alacak:hatirlat',               'tip' => 'gunluk',   'saat' => '09:15'],
        ['komut' => 'sozlesme:hatirlat',             'tip' => 'gunluk',   'saat' => '09:45'],
        ['komut' => 'finans:gunluk-rapor',           'tip' => 'gunluk',   'saat' => '23:30'],
        ['komut' => 'finans:borc-raporu',            'tip' => 'aylik',    'gun' => 28, 'saat' => '09:00'],
    ];

    /** Ana giriş — provider bunu çağırır. Hiçbir koşulda dışarı hata sızdırmaz. */
    public static function calistir(): void
    {
        try {
            // VARSAYILAN KAPALI. Dosyaları yüklemek tek başına hiçbir şeyi
            // çalıştırmaz; canlıda bilerek .env'e TRAFIK_CRON=true eklenmelidir.
            // (Yanlışlıkla müşteriye mail/SMS/sesli arama gitmesin diye.)
            if (!filter_var(env('TRAFIK_CRON', false), FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            @ignore_user_abort(true);

            // Yanıtı ziyaretçiye kapat ki görev süresi ONU BEKLETMESİN.
            // Kapatamıyorsak (yanıt hâlâ açık) hiçbir görev çalıştırmayız —
            // 15 saniyelik bir mail görevi için ziyaretçiyi bekletmek kabul edilemez.
            if (!static::yanitiKapat()) {
                return;
            }

            $kilit = static::kilitAl();
            if (!$kilit) {
                return; // başka bir istek zaten çalışıyor
            }

            try {
                $gorev = static::siradakiGorev();
                if ($gorev) {
                    static::gorevCalistir($gorev);
                }
            } finally {
                static::kilitBirak($kilit);
            }
        } catch (\Throwable $e) {
            // Zamanlayıcı hiçbir zaman siteyi düşürmez
            try {
                Log::warning('TrafikCron hatası: ' . $e->getMessage());
            } catch (\Throwable $x) {
                // log bile yazılamıyorsa sessizce çık
            }
        }
    }

    /**
     * Yanıtı ziyaretçiye kapatıp arka planda devam etmeyi dener.
     * PHP-FPM → fastcgi_finish_request, LiteSpeed → litespeed_finish_request.
     * Hiçbiri yoksa false döner ve görev ÇALIŞTIRILMAZ (ziyaretçi beklemesin).
     */
    private static function yanitiKapat(): bool
    {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
            return true;
        }

        if (function_exists('litespeed_finish_request')) {
            @litespeed_finish_request();
            return true;
        }

        return false;
    }

    /** Çalıştırılacak tek görev: en uzun süredir gecikmiş olan. Yoksa null. */
    private static function siradakiGorev(): ?array
    {
        $durum = static::durumOku();
        $simdi = Carbon::now();

        // Sunucuyu yormamak için: son çalışmadan bu yana çok az zaman geçtiyse dur
        $sonHerhangi = $durum['_son_calisma'] ?? null;
        if ($sonHerhangi && Carbon::parse($sonHerhangi)->diffInSeconds($simdi) < self::ASGARI_ARALIK) {
            return null;
        }

        $aday = null;
        $enFazlaGecikme = -1;

        foreach (self::GOREVLER as $gorev) {
            $vade = static::vadeZamani($gorev, $simdi);
            if (!$vade) {
                continue;
            }

            $son = isset($durum[$gorev['komut']]) ? Carbon::parse($durum[$gorev['komut']]) : null;

            // Bu vadeden sonra zaten çalışmışsa geç
            if ($son && $son->gte($vade)) {
                continue;
            }

            $gecikme = $vade->diffInSeconds($simdi);
            if ($gecikme > $enFazlaGecikme) {
                $enFazlaGecikme = $gecikme;
                $aday = $gorev;
            }
        }

        return $aday;
    }

    /**
     * Görevin "en son ne zaman çalışmış olması gerekirdi" anı.
     * Şu an o ana ulaşılmadıysa null (henüz sırası değil).
     */
    private static function vadeZamani(array $gorev, Carbon $simdi): ?Carbon
    {
        switch ($gorev['tip']) {
            case 'dakikalik':
                return $simdi->copy()->startOfMinute();

            case 'saatlik':
                return $simdi->copy()->startOfHour();

            case 'gunluk':
                [$s, $d] = array_pad(explode(':', $gorev['saat'] ?? '00:00'), 2, '0');
                $vade = $simdi->copy()->setTime((int) $s, (int) $d, 0);
                return $simdi->gte($vade) ? $vade : null; // saati gelmediyse bekle

            case 'haftalik':
                [$s, $d] = array_pad(explode(':', $gorev['saat'] ?? '00:00'), 2, '0');
                $vade = $simdi->copy()->startOfWeek(Carbon::SUNDAY)
                    ->addDays((int) ($gorev['gun'] ?? 0))
                    ->setTime((int) $s, (int) $d, 0);
                return $simdi->gte($vade) ? $vade : null;

            /*
             * AYLIK (31.07.2026) — ayın belirli gününde çalışan işler için.
             * Borç takip raporu ayın 28'inde gidiyor; bu tip olmadan
             * trafik tetiklemeli sistemde hiç çalışmıyordu.
             * Ayın gün sayısı yetersizse (örn. 30'u seçildi, Şubat) ay sonuna çekilir.
             */
            case 'aylik':
                [$s, $d] = array_pad(explode(':', $gorev['saat'] ?? '00:00'), 2, '0');
                $gun  = min((int) ($gorev['gun'] ?? 1), $simdi->copy()->endOfMonth()->day);
                $vade = $simdi->copy()->startOfMonth()->addDays($gun - 1)
                    ->setTime((int) $s, (int) $d, 0);
                return $simdi->gte($vade) ? $vade : null;
        }

        return null;
    }

    /** Görevi çalıştır ve son çalışma anını kaydet. */
    private static function gorevCalistir(array $gorev): void
    {
        $komut = $gorev['komut'];
        $basla = microtime(true);

        try {
            Artisan::call($komut);
            $sure = round(microtime(true) - $basla, 2);
            Log::info('TrafikCron çalıştırdı: ' . $komut . ' (' . $sure . ' sn)');
        } catch (\Throwable $e) {
            Log::warning('TrafikCron görev hatası (' . $komut . '): ' . $e->getMessage());
        }

        // Hata alsa da kaydet — aksi hâlde her istekte tekrar deneyip siteyi yorar
        $durum = static::durumOku();
        $durum[$komut] = Carbon::now()->toDateTimeString();
        $durum['_son_calisma'] = Carbon::now()->toDateTimeString();
        static::durumYaz($durum);
    }

    // ── Durum dosyası (migration gerektirmesin diye JSON) ────────────────

    private static function durumYolu(): string
    {
        return storage_path('app/trafik-cron.json');
    }

    private static function durumOku(): array
    {
        $yol = static::durumYolu();
        if (!is_file($yol)) {
            return [];
        }
        $ham = @file_get_contents($yol);
        $veri = $ham ? json_decode($ham, true) : null;

        return is_array($veri) ? $veri : [];
    }

    private static function durumYaz(array $durum): void
    {
        $yol = static::durumYolu();
        @is_dir(dirname($yol)) || @mkdir(dirname($yol), 0755, true);
        @file_put_contents($yol, json_encode($durum, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    // ── Kilit (aynı anda iki istek çalıştırmasın) ────────────────────────

    private static function kilitAl()
    {
        $yol = storage_path('app/trafik-cron.lock');
        $fp = @fopen($yol, 'c');
        if (!$fp) {
            return null;
        }
        if (!@flock($fp, LOCK_EX | LOCK_NB)) {
            @fclose($fp);
            return null;
        }

        return $fp;
    }

    private static function kilitBirak($fp): void
    {
        @flock($fp, LOCK_UN);
        @fclose($fp);
    }

    /** Panelde göstermek için: son çalışma durumu. */
    public static function durum(): array
    {
        return static::durumOku();
    }
}
