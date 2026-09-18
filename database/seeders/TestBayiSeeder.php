<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestBayiSeeder extends Seeder
{
    public function run(): void
    {
        // Bayi yöneticisinin ID'sini al
        $bayiYonetici = DB::table('yoneticiler')->where('kullaniciadi', 'bayi')->first();
        
        if (!$bayiYonetici) {
            $this->command->error('Bayi yöneticisi bulunamadı!');
            return;
        }
        
        // Bir üye ID'si al (yoksa oluştur)
        $uyeId = DB::table('uyeler')->orderBy('id', 'desc')->value('id');
        
        if (!$uyeId) {
            // Test üyesi oluştur
            DB::table('uyeler')->insert([
                'ad' => 'Test',
                'soyad' => 'Bayi',
                'email' => 'testbayi@test.com',
                'sifre' => bcrypt('123456'),
                'telefon' => '05551234570',
                'durum' => 1,
                'tarih' => date('Y-m-d'),
            ]);
            $uyeId = DB::getPdo()->lastInsertId();
        }
        
        // Bayi kaydı var mı kontrol et
        $exists = DB::table('bayiler')->where('uye_id', $uyeId)->exists();
        
        if (!$exists) {
            DB::table('bayiler')->insert([
                'uye_id' => $uyeId,
                'bayi_kodu' => 'BAY' . str_pad($bayiYonetici->id, 4, '0', STR_PAD_LEFT),
                'komisyon_orani' => 10.00,
                'toplam_kazanc' => 0,
                'cekilebilir_bakiye' => 0,
                'cekilen_toplam' => 0,
                'adres' => 'İstanbul, Türkiye',
                'adres_tarifi' => 'Test bayi adresi',
                'banka_adi' => 'Ziraat Bankası',
                'iban' => 'TR330006100519786457841326',
                'hesap_sahibi' => 'Fatma Şahin',
                'durum' => 1,
                'onay_durumu' => 1,
                'onay_tarihi' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $bayiId = DB::getPdo()->lastInsertId();
            
            // Test satışları ekle
            for ($i = 1; $i <= 5; $i++) {
                DB::table('bayi_satislar')->insert([
                    'bayi_id' => $bayiId,
                    'fatura_id' => $i,
                    'satis_tutari' => 1000 * $i,
                    'komisyon_orani' => 10,
                    'komisyon_tutari' => 100 * $i,
                    'odendi' => $i <= 2 ? 1 : 0,
                    'odeme_tarihi' => $i <= 2 ? now() : null,
                    'created_at' => now()->subDays(6 - $i),
                    'updated_at' => now()->subDays(6 - $i),
                ]);
            }
            
            $this->command->info('✅ Test bayi kaydı ve satışları oluşturuldu!');
            $this->command->table(
                ['Bilgi', 'Değer'],
                [
                    ['Bayi ID', $bayiId],
                    ['Bayi Kodu', 'BAY' . str_pad($bayiYonetici->id, 4, '0', STR_PAD_LEFT)],
                    ['Üye ID', $uyeId],
                    ['Komisyon Oranı', '%10'],
                    ['Test Satış Sayısı', '5'],
                ]
            );
        } else {
            $this->command->warn('⚠️  Bayi kaydı zaten mevcut!');
        }
    }
}

