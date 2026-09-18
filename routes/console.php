<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\File;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Log temizleme komutu
Artisan::command('logs:clear', function () {
    $logPath = storage_path('logs');
    $files = File::glob($logPath . '/*.log');
    $deletedCount = 0;
    
    foreach ($files as $file) {
        // 7 günden eski log dosyalarını sil
        if (filemtime($file) < strtotime('-7 days')) {
            File::delete($file);
            $deletedCount++;
        }
    }
    
    // laravel.log dosyasını temizle (silme değil)
    $mainLog = $logPath . '/laravel.log';
    if (File::exists($mainLog) && File::size($mainLog) > 50 * 1024 * 1024) { // 50MB'dan büyükse
        File::put($mainLog, '');
        $this->info('Main log file cleared (was > 50MB)');
    }
    
    $this->info("Deleted {$deletedCount} old log files.");
})->purpose('Clear old log files');

// Cache temizleme komutu
Artisan::command('cache:cleanup', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    $this->info('Cache and views cleared.');
})->purpose('Clear cache and compiled views');

// ─────────────────────────────────────────────────────────────────────
// ZAMANLANMIŞ GÖREVLER
//
// ÖNEMLİ: Bu sunucuda proc_open KAPALI (paylaşımlı hosting kısıtlaması).
// Schedule::command() her görevi alt-süreç açarak çalıştırmaya çalışır ve
// "The Process class relies on proc_open" hatasıyla patlar.
// Bu yüzden TÜM görevler Schedule::call + Artisan::call ile aynı süreç
// içinde çalıştırılır. Yeni görev eklerken de bu kalıbı kullan!
// ─────────────────────────────────────────────────────────────────────

// Günlük bakım
Schedule::call(fn () => Artisan::call('logs:clear'))
    ->name('logs-clear')->daily()->at('03:00');

// Cache + derlenmiş blade temizliği — haftada 2 kez (Pazar & Çarşamba 04:00).
// cache:cleanup = cache:clear + view:clear. FTP ile dosya güncellendiğinde
// eski derlenmiş blade'ler takılı kalmasın diye view:clear şart.
// NOT: config/route cache'e DOKUNMAZ (onları silmek siteyi yavaşlatırdı).
// '0 4 * * 0,3' = Pazar ve Çarşamba, saat 04:00
Schedule::call(fn () => Artisan::call('cache:cleanup'))
    ->name('cache-cleanup')->cron('0 4 * * 0,3');

// Ödeme hatırlatma - her gün 09:00
Schedule::call(fn () => Artisan::call('mail:odeme-hatirlat'))
    ->name('odeme-hatirlat')->withoutOverlapping()->dailyAt('09:00');

// Aylık bildirimli ödeme hatırlatma (mail + panel bildirimi) - her gün 09:30
Schedule::call(fn () => Artisan::call('mail:aylik-odeme-hatirlat'))
    ->name('aylik-odeme-hatirlat')->withoutOverlapping()->dailyAt('09:30');

// Domain yenileme hatırlatma (mail + SMS) - her gün 10:00
Schedule::call(fn () => Artisan::call('mail:domain-yenileme-hatirlat'))
    ->name('domain-yenileme-hatirlat')->dailyAt('10:00');

/*
 * Sözleşme bitiş hatırlatması — her gün 09:45, bitişe 7 gün kala.
 * Komut (sozlesme:hatirlat) yazılmıştı ama zamanlamaya hiç bağlanmamıştı,
 * bu yüzden hiç çalışmıyordu. Her sözleşme için tek sefer gönderilir
 * (crm_sozlesmeler.bitis_bildirim_at damgası); bitiş tarihi değiştirilirse
 * damga sıfırlanır ve yeni tarihe göre tekrar gönderilir.
 */
Schedule::call(fn () => Artisan::call('sozlesme:hatirlat', ['--gun' => 7]))
    ->name('sozlesme-hatirlat')->dailyAt('09:45');

// Domain YÖNETİM ekibi uyarısı (7 gün içinde bitecekler özeti) - her gün 10:15
Schedule::call(fn () => Artisan::call('mail:domain-admin-uyari'))
    ->name('domain-admin-uyari')->dailyAt('10:15');

// Hizmet/hosting bitiş hatırlatma - her gün 10:30
Schedule::call(fn () => Artisan::call('mail:hizmet-bitis-hatirlat'))
    ->name('hizmet-bitis-hatirlat')->dailyAt('10:30');

// Domain sesli arama (NetGSM TTS) — 7 gün içinde bitecek domain müşterilerini arar - her gün 11:00
Schedule::call(fn () => Artisan::call('domain:sesli-ara'))
    ->name('domain-sesli-ara')->dailyAt('11:00');

// Teklif değerlendirme hatırlatması — 24 saat yanıtsız kalan tekliflere tek seferlik hatırlatma (saatlik kontrol)
Schedule::call(fn () => Artisan::call('mail:teklif-hatirlat'))
    ->name('teklif-hatirlat')->hourly()->withoutOverlapping();

// Randevu hatırlatma döngüsü (SMS + Mail + panel bildirimi) - her dakika
Schedule::call(fn () => Artisan::call('randevu:hatirlat'))
    ->name('randevu-hatirlat')->everyMinute()->withoutOverlapping();

// DM sesli mesaj temizliği - her gün 03:30, 45 günden eskiler
Schedule::call(fn () => Artisan::call('dm:ses-temizle'))
    ->name('dm-ses-temizle')->dailyAt('03:30');

// Yeni yüklenen resimler için otomatik webp üretimi - her dakika (pratikte anlık)
Schedule::call(fn () => Artisan::call('webp:uret'))
    ->name('webp-uret')->everyMinute()->withoutOverlapping();

/* ════════════════════════════════════════════════════════════════
   MUHASEBE GELİŞTİRME TALEPLERİ (görev #217) — 30.07.2026
   NOT: proc_open kapalı olduğu için ->command() değil
        Schedule::call(fn () => Artisan::call(...)) kullanılıyor.
   ════════════════════════════════════════════════════════════════ */

// #217/6 — Aylık bildirimli ALACAKLAR hatırlatması, her gün 09:15
Schedule::call(fn () => Artisan::call('alacak:hatirlat'))
    ->name('alacak-hatirlat')->withoutOverlapping()->dailyAt('09:15');

// #217/10 — Otomatik proforma fatura: bitişine 5-30 gün kalan hizmetler, her gün 08:30
// Kesilen proformalar durum=0 olduğu için "Bekleyen Faturalar" ekranına düşer.
Schedule::call(fn () => Artisan::call('proforma:uret'))
    ->name('proforma-uret')->dailyAt('08:30');

// #217/8 — Günlük gelir-gider raporu, her gün 19:00, muhasebeye mail
Schedule::call(fn () => Artisan::call('finans:gunluk-rapor', ['--tarih' => now()->toDateString()]))
    ->name('finans-gunluk-rapor')->dailyAt('19:00');

// Gün sonu bildirimi — CRM + personel aktivite özeti, finans raporuyla aynı saatte
Schedule::call(fn () => Artisan::call('gun:sonu-bildirimi', ['--tarih' => now()->toDateString()]))
    ->name('gun-sonu-bildirimi')->dailyAt('19:00');

// Sosyal medya gün sonu raporu — ayrı mail, ayrı alıcı listesi (Seda Hanım dahil).
// 19:05: aynı dakikada üç rapor birden tetiklenmesin diye 5 dakika sonraya alındı;
// istenirse 19:00'a çekilebilir, komut kendi içinde günde-tek-kez kilidine sahip.
// Saat PANELDEN ayarlanir (Plan ekrani > Rapor Ayarlari). Veritabanina
// ulasilamazsa varsayilana duser -- yoksa bu satir patlar ve zamanlayicidaki
// DIGER tum gorevler de calismaz.
Schedule::call(fn () => Artisan::call('sosyal-medya:gun-sonu', ['--tarih' => now()->toDateString()]))
    ->name('sosyal-medya-gun-sonu')
    ->dailyAt(\App\Services\SosyalMedyaRaporu::raporSaati());

// #217/9 — Borç takip raporu, her ayın 28'inde 09:00 (Nurseli, Dilan, Nesimi)
Schedule::call(fn () => Artisan::call('finans:borc-raporu'))
    ->name('finans-borc-raporu')->monthlyOn(28, '09:00');
