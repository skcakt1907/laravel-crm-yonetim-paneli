<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('slider')) {
            return;
        }
        
        // Tüm sliderlardaki karışık İngilizce/Arapça metinleri temizle
        $hasDilColumn = Schema::hasColumn('slider', 'dil');
        $query = DB::table('slider');
        if ($hasDilColumn) {
            $query->where('dil', 1);
        }
        $query->update([
            'adi_en' => DB::raw("REPLACE(REPLACE(adi, ' - İş Ortağım Paneli', ''), 'SOSYAL MEDYA YÖNETİMİ', 'Social Media Management')"),
            'adi_ar' => DB::raw("REPLACE(REPLACE(adi, ' - İş Ortağım Paneli', ''), 'SOSYAL MEDYA YÖNETİMİ', 'إدارة وسائل التواصل الاجتماعي')"),
        ]);
        
        // Specific slider updates
        $sliders = [
            [
                'adi' => 'İş Ortağım Paneli',
                'adi_en' => 'My Business Partner Panel',
                'adi_ar' => 'لوحة شريك العمل',
                'aciklama' => 'Hemen üye ol veya giriş yap!',
                'aciklama_en' => 'Register now or log in immediately!',
                'aciklama_ar' => 'سجل الآن أو قم بتسجيل الدخول!',
            ],
            [
                'adi' => 'Sosyal Medya Yönetimi',
                'adi_en' => 'Social Media Management',
                'adi_ar' => 'إدارة وسائل التواصل الاجتماعي',
                'aciklama' => 'Sosyal Medyada Yoksan Yoksun! Profesyonel Bir Ekiple Markanızı İnternette Kitlelere Ulaştırın',
                'aciklama_en' => 'If you are not on social media, you are missing out! Reach masses with your brand on the internet with a professional team',
                'aciklama_ar' => 'إذا لم تكن على وسائل التواصل الاجتماعي، فأنت تفتقد الكثير! وصّل علامتك التجارية إلى الجماهير على الإنترنت مع فريق محترف',
            ],
        ];
        
        foreach ($sliders as $index => $slider) {
            $query = DB::table('slider');
            if ($hasDilColumn) {
                $query->where('dil', 1);
            }
            $query->skip($index)
                ->take(1)
                ->update($slider);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
