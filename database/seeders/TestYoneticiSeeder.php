<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestYoneticiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testAccounts = [
            [
                'kullaniciadi' => 'patron',
                'sifre' => Hash::make('123456'),
                'adi' => 'Ahmet',
                'soyadi' => 'Yılmaz',
                'email' => 'patron@test.com',
                'telefon' => '05551234567',
                'yetki' => 1,
                'rol' => 1, // Patron
                'durum' => 1,
                'son_giris' => now(),
                'son_ip' => '127.0.0.1',
            ],
            [
                'kullaniciadi' => 'calisan1',
                'sifre' => Hash::make('123456'),
                'adi' => 'Mehmet',
                'soyadi' => 'Demir',
                'email' => 'calisan1@test.com',
                'telefon' => '05551234568',
                'yetki' => 2,
                'rol' => 2, // Çalışan
                'durum' => 1,
                'son_giris' => now(),
                'son_ip' => '127.0.0.1',
            ],
            [
                'kullaniciadi' => 'calisan2',
                'sifre' => Hash::make('123456'),
                'adi' => 'Ayşe',
                'soyadi' => 'Kaya',
                'email' => 'calisan2@test.com',
                'telefon' => '05551234569',
                'yetki' => 2,
                'rol' => 2, // Çalışan
                'durum' => 1,
                'son_giris' => now(),
                'son_ip' => '127.0.0.1',
            ],
            [
                'kullaniciadi' => 'bayi',
                'sifre' => Hash::make('123456'),
                'adi' => 'Fatma',
                'soyadi' => 'Şahin',
                'email' => 'bayi@test.com',
                'telefon' => '05551234570',
                'yetki' => 2,
                'rol' => 3, // Bayi
                'durum' => 1,
                'son_giris' => now(),
                'son_ip' => '127.0.0.1',
            ],
            [
                'kullaniciadi' => 'musteri',
                'sifre' => Hash::make('123456'),
                'adi' => 'Ali',
                'soyadi' => 'Çelik',
                'email' => 'musteri@test.com',
                'telefon' => '05551234571',
                'yetki' => 2,
                'rol' => 4, // Müşteri
                'durum' => 1,
                'son_giris' => now(),
                'son_ip' => '127.0.0.1',
            ],
        ];

        foreach ($testAccounts as $account) {
            // Aynı kullanıcı adı varsa ekleme
            $exists = DB::table('yoneticiler')
                ->where('kullaniciadi', $account['kullaniciadi'])
                ->exists();

            if (!$exists) {
                // Email kolonu yoksa çıkar
                $columns = DB::getSchemaBuilder()->getColumnListing('yoneticiler');
                if (!in_array('email', $columns)) {
                    unset($account['email']);
                }
                if (!in_array('soyadi', $columns)) {
                    unset($account['soyadi']);
                }
                
                DB::table('yoneticiler')->insert($account);
                $this->command->info("✅ {$account['kullaniciadi']} hesabı oluşturuldu!");
            } else {
                $this->command->warn("⚠️  {$account['kullaniciadi']} zaten mevcut, atlandı.");
            }
        }

        $this->command->info("\n🎉 Test hesapları hazır!\n");
        $this->command->table(
            ['Rol', 'Kullanıcı Adı', 'Şifre', 'Email'],
            [
                ['👑 Patron', 'patron', '123456', 'patron@test.com'],
                ['👤 Çalışan 1', 'calisan1', '123456', 'calisan1@test.com'],
                ['👤 Çalışan 2', 'calisan2', '123456', 'calisan2@test.com'],
                ['🤝 Bayi', 'bayi', '123456', 'bayi@test.com'],
                ['👥 Müşteri', 'musteri', '123456', 'musteri@test.com'],
            ]
        );
    }
}

