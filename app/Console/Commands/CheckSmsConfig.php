<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SmsService;

class CheckSmsConfig extends Command
{
    protected $signature = 'sms:check-config';
    protected $description = 'SMS ayarlarını kontrol et ve test et';

    public function handle()
    {
        $this->info('=== SMS AYARLARI KONTROLÜ ===');
        $this->newLine();

        // 1. .env kontrolü
        $this->info('1. .env Dosyası Kontrolü:');
        $envConfig = config('services.netgsm');
        $this->line('   NETGSM_USERCODE: ' . ($envConfig['usercode'] ?? 'YOK'));
        $this->line('   NETGSM_PASSWORD: ' . (isset($envConfig['password']) ? '***' . substr($envConfig['password'], -3) : 'YOK'));
        $this->line('   NETGSM_MSGHEADER: ' . ($envConfig['msgheader'] ?? 'YOK'));
        $this->line('   NETGSM_API_URL: ' . ($envConfig['api_url'] ?? 'YOK'));
        $this->newLine();

        // 2. Veritabanı kontrolü
        $this->info('2. Veritabanı Ayarları:');
        try {
            $ayarlar = DB::table('ayarlar')->first();
            if ($ayarlar) {
                $this->line('   sms_kullanici_adi: ' . ($ayarlar->sms_kullanici_adi ?? 'YOK'));
                $this->line('   sms_sifre: ' . (isset($ayarlar->sms_sifre) ? '***' . substr($ayarlar->sms_sifre, -3) : 'YOK'));
                $this->line('   sms_baslik: ' . ($ayarlar->sms_baslik ?? 'YOK'));
                $this->line('   sms_post_url: ' . ($ayarlar->sms_post_url ?? 'YOK'));
            } else {
                $this->error('   ayarlar tablosunda kayıt yok!');
            }
        } catch (\Exception $e) {
            $this->error('   Veritabanı hatası: ' . $e->getMessage());
        }
        $this->newLine();

        // 3. SmsService'in kullandığı değerler
        $this->info('3. SmsService Aktif Değerler:');
        try {
            $smsService = new SmsService();
            $reflection = new \ReflectionClass($smsService);
            
            $usercode = $reflection->getProperty('usercode');
            $usercode->setAccessible(true);
            $this->line('   usercode: ' . $usercode->getValue($smsService));
            
            $password = $reflection->getProperty('password');
            $password->setAccessible(true);
            $pwd = $password->getValue($smsService);
            $this->line('   password: ' . (strlen($pwd) > 0 ? '***' . substr($pwd, -3) : 'BOŞ'));
            
            $msgheader = $reflection->getProperty('msgheader');
            $msgheader->setAccessible(true);
            $this->line('   msgheader: ' . $msgheader->getValue($smsService));
            
            $apiUrl = $reflection->getProperty('apiUrl');
            $apiUrl->setAccessible(true);
            $this->line('   apiUrl: ' . $apiUrl->getValue($smsService));
        } catch (\Exception $e) {
            $this->error('   SmsService hatası: ' . $e->getMessage());
        }
        $this->newLine();

        // 4. PHP Extension kontrolü
        $this->info('4. PHP Extension Kontrolü:');
        $this->line('   cURL: ' . (extension_loaded('curl') ? '✓ Yüklü' : '✗ YOK'));
        $this->line('   OpenSSL: ' . (extension_loaded('openssl') ? '✓ Yüklü' : '✗ YOK'));
        $this->line('   PHP Version: ' . PHP_VERSION);
        $this->newLine();

        // 5. Test SMS gönderimi
        if ($this->confirm('Test SMS göndermek ister misiniz?', false)) {
            $phone = $this->ask('Telefon numarası (başında 0 olmadan):');
            if ($phone) {
                $this->info('SMS gönderiliyor...');
                $result = $smsService->send($phone, 'Test mesajı - ' . now()->format('d.m.Y H:i'));
                
                if ($result['success']) {
                    $this->info('✓ SMS başarıyla gönderildi! (Kod: ' . ($result['data']['code'] ?? 'N/A') . ')');
                } else {
                    $this->error('✗ SMS gönderilemedi: ' . $result['message']);
                    if (isset($result['data']['code'])) {
                        $this->line('   Hata Kodu: ' . $result['data']['code']);
                    }
                }
            }
        }

        $this->newLine();
        $this->info('=== KONTROL TAMAMLANDI ===');
    }
}
