<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestCeviriSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $this->command->info('🌍 Çok Dilli Test Verisi Ekleniyor...');
        
        // SLIDER ÇEVİRİLERİ
        $this->command->info('📸 Slider çevirileri ekleniyor...');
        
        $sliders = DB::table('slider')->get();
        foreach ($sliders as $slider) {
            DB::table('slider')
                ->where('id', $slider->id)
                ->update([
                    'adi_en' => $this->translateToEnglish($slider->adi ?? 'Welcome to Our Website'),
                    'adi_ar' => $this->translateToArabic($slider->adi ?? 'مرحبا بكم في موقعنا'),
                    'aciklama_en' => $this->translateDescToEnglish($slider->aciklama ?? 'Professional web solutions for your business'),
                    'aciklama_ar' => $this->translateDescToArabic($slider->aciklama ?? 'حلول ويب احترافية لعملك'),
                ]);
        }
        $this->command->info("✅ {$sliders->count()} slider çevirisi eklendi!");
        
        // PAKETLERİN ÇEVİRİLERİ (yazilimlar)
        $this->command->info('📦 Paket çevirileri ekleniyor...');
        
        $paketler = DB::table('yazilimlar')->get();
        foreach ($paketler as $paket) {
            DB::table('yazilimlar')
                ->where('id', $paket->id)
                ->update([
                    'adi_en' => $this->translatePackageToEnglish($paket->adi ?? 'Web Package'),
                    'adi_ar' => $this->translatePackageToArabic($paket->adi ?? 'باقة الويب'),
                    'aciklama_en' => $this->translateDescToEnglish($paket->aciklama ?? 'Professional web hosting package'),
                    'aciklama_ar' => $this->translateDescToArabic($paket->aciklama ?? 'باقة استضافة ويب احترافية'),
                ]);
        }
        $this->command->info("✅ {$paketler->count()} paket çevirisi eklendi!");
        
        // BLOG ÇEVİRİLERİ
        $this->command->info('📝 Blog çevirileri ekleniyor...');
        
        $blogs = DB::table('blog')->get();
        foreach ($blogs as $blog) {
            DB::table('blog')
                ->where('id', $blog->id)
                ->update([
                    'adi_en' => $this->translateBlogToEnglish($blog->adi ?? 'Latest Technology News'),
                    'adi_ar' => $this->translateBlogToArabic($blog->adi ?? 'أحدث أخبار التكنولوجيا'),
                    'aciklama_en' => $this->translateDescToEnglish($blog->aciklama ?? 'Stay updated with latest tech trends'),
                    'aciklama_ar' => $this->translateDescToArabic($blog->aciklama ?? 'ابق على اطلاع بأحدث التطورات التقنية'),
                ]);
        }
        $this->command->info("✅ {$blogs->count()} blog çevirisi eklendi!");
        
        // REFERANSLAR ÇEVİRİLERİ
        $this->command->info('🏆 Referans çevirileri ekleniyor...');
        
        $referanslar = DB::table('referanslar')->get();
        foreach ($referanslar as $ref) {
            DB::table('referanslar')
                ->where('id', $ref->id)
                ->update([
                    'adi_en' => $this->translateToEnglish($ref->adi ?? 'Our Project'),
                    'adi_ar' => $this->translateToArabic($ref->adi ?? 'مشروعنا'),
                    'kisa_en' => $this->translateToEnglish($ref->kisa ?? 'Successful project'),
                    'kisa_ar' => $this->translateToArabic($ref->kisa ?? 'مشروع ناجح'),
                    'aciklama_en' => $this->translateDescToEnglish($ref->aciklama ?? 'A successful web project'),
                    'aciklama_ar' => $this->translateDescToArabic($ref->aciklama ?? 'مشروع ويب ناجح'),
                ]);
        }
        $this->command->info("✅ {$referanslar->count()} referans çevirisi eklendi!");
        
        // SAYFALAR ÇEVİRİLERİ
        $this->command->info('📄 Sayfa çevirileri ekleniyor...');
        
        $sayfalar = DB::table('sayfalar')->get();
        foreach ($sayfalar as $sayfa) {
            DB::table('sayfalar')
                ->where('id', $sayfa->id)
                ->update([
                    'adi_en' => $this->translateToEnglish($sayfa->adi ?? 'About Us'),
                    'adi_ar' => $this->translateToArabic($sayfa->adi ?? 'معلومات عنا'),
                    'kisa_en' => $this->translateToEnglish($sayfa->kisa ?? 'About our company'),
                    'kisa_ar' => $this->translateToArabic($sayfa->kisa ?? 'معلومات عن شركتنا'),
                    'aciklama_en' => $this->translateDescToEnglish($sayfa->aciklama ?? 'We provide professional web solutions'),
                    'aciklama_ar' => $this->translateDescToArabic($sayfa->aciklama ?? 'نحن نقدم حلول ويب احترافية'),
                ]);
        }
        $this->command->info("✅ {$sayfalar->count()} sayfa çevirisi eklendi!");
        
        $this->command->info('');
        $this->command->table(
            ['Tablo', 'Eklenen Çeviri'],
            [
                ['Slider', $sliders->count()],
                ['Paketler', $paketler->count()],
                ['Blog', $blogs->count()],
                ['Referanslar', $referanslar->count()],
                ['Sayfalar', $sayfalar->count()],
                ['TOPLAM', $sliders->count() + $paketler->count() + $blogs->count() + $referanslar->count() + $sayfalar->count()],
            ]
        );
        
        $this->command->info('');
        $this->command->info('🎉 Tüm çeviriler başarıyla eklendi!');
        $this->command->warn('⚠️  Bu otomatik çevirilerdir. Daha doğru çeviriler için admin panelden manuel olarak düzenleyin.');
    }
    
    // Basit İngilizce çeviri önerileri
    private function translateToEnglish($text)
    {
        $translations = [
            'Hakkımızda' => 'About Us',
            'İletişim' => 'Contact',
            'Referanslar' => 'References',
            'Projeler' => 'Projects',
            'Hizmetler' => 'Services',
            'Kurumsal' => 'Corporate',
        ];
        
        return $translations[$text] ?? 'Welcome to Our Website - ' . substr($text, 0, 30);
    }
    
    // Basit Arapça çeviri önerileri
    private function translateToArabic($text)
    {
        $translations = [
            'Hakkımızda' => 'معلومات عنا',
            'İletişim' => 'اتصل بنا',
            'Referanslar' => 'مراجعنا',
            'Projeler' => 'المشاريع',
            'Hizmetler' => 'الخدمات',
            'Kurumsal' => 'الشركات',
        ];
        
        return $translations[$text] ?? 'مرحبا بكم في موقعنا - ' . substr($text, 0, 30);
    }
    
    private function translatePackageToEnglish($text)
    {
        if (stripos($text, 'paket') !== false) {
            return 'Web Hosting Package - ' . substr($text, 0, 20);
        }
        return 'Professional Package - ' . substr($text, 0, 20);
    }
    
    private function translatePackageToArabic($text)
    {
        if (stripos($text, 'paket') !== false) {
            return 'باقة استضافة الويب - ' . substr($text, 0, 20);
        }
        return 'الباقة الاحترافية - ' . substr($text, 0, 20);
    }
    
    private function translateBlogToEnglish($text)
    {
        return 'Latest News: ' . substr($text, 0, 40);
    }
    
    private function translateBlogToArabic($text)
    {
        return 'أحدث الأخبار: ' . substr($text, 0, 40);
    }
    
    private function translateDescToEnglish($text)
    {
        return 'Professional web solutions for your business needs. ' . substr(strip_tags($text), 0, 100);
    }
    
    private function translateDescToArabic($text)
    {
        return 'حلول ويب احترافية لاحتياجات عملك. ' . substr(strip_tags($text), 0, 100);
    }
}



