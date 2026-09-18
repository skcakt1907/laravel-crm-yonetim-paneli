<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SliderTemizleSeeder extends Seeder
{
    public function run(): void
    {
        // Tüm sliderları güncelle
        $updates = [
            [
                'id' => 20,
                'adi' => 'İş Ortağım Paneli',
                'adi_en' => 'My Business Partner Panel',
                'adi_ar' => 'لوحة شريك العمل',
                'aciklama' => 'Hemen üye ol veya giriş yap!',
                'aciklama_en' => 'Register now or log in immediately!',
                'aciklama_ar' => 'سجل الآن أو قم بتسجيل الدخول!',
            ],
        ];
        
        foreach ($updates as $update) {
            $id = $update['id'];
            unset($update['id']);
            
            DB::table('slider')->where('id', $id)->update($update);
        }
        
        // Diğer sliderları kontrol et ve temizle
        DB::statement("
            UPDATE slider 
            SET 
                adi_en = REPLACE(REPLACE(REPLACE(adi_en, 'Welcome to Our Website - ', ''), ' - İş Ortağım Paneli', ''), 'SOSYAL MEDYA YÖNETİMİ', ''),
                adi_ar = REPLACE(REPLACE(REPLACE(adi_ar, 'مرحبا بكم في موقعنا - ', ''), ' - İş Ortağım Paneli', ''), 'SOSYAL MEDYA YÖNETİMİ', ''),
                aciklama_en = REPLACE(REPLACE(aciklama_en, 'Professional web solutions for your business needs. ', ''), '. Hemen üye ol veya giriş yap!', ''),
                aciklama_ar = REPLACE(REPLACE(aciklama_ar, 'حلول ويب احترافية لاحتياجات عملك. ', ''), '. Hemen üye ol veya giriş yap!', '')
            WHERE dil = 1
        ");
        
        echo "✅ Slider çevirileri temizlendi!\n";
    }
}
