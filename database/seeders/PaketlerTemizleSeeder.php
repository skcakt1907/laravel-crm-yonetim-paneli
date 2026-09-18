<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaketlerTemizleSeeder extends Seeder
{
    public function run(): void
    {
        // Tüm Türkçe paketlerdeki karışık metinleri temizle
        DB::statement("
            UPDATE yazilimlar 
            SET 
                adi_en = REPLACE(REPLACE(REPLACE(adi_en, 'Web Hosting Package - ', ''), ' - Yoksan Yoksun Paket', ''), 'باقة استضافة الويب - ', ''),
                adi_ar = REPLACE(REPLACE(REPLACE(adi_ar, 'باقة استضافة الويب - ', ''), ' - Yoksan Yoksun Paket', ''), 'Web Hosting Package - ', ''),
                aciklama_en = REPLACE(REPLACE(aciklama_en, 'Professional web solutions for your business needs.  \r\n', ''), 'حلول ويب احترافية لاحتياجات عملك.  \r\n', ''),
                aciklama_ar = REPLACE(REPLACE(aciklama_ar, 'حلول ويب احترافية لاحتياجات عملك.  \r\n', ''), 'Professional web solutions for your business needs.  \r\n', '')
            WHERE dil = 1
        ");
        
        // Boş olan çevirileri doldur (Türkçe metinlerden)
        $paketler = DB::table('yazilimlar')->where('dil', 1)->get();
        
        foreach ($paketler as $paket) {
            $updates = [];
            
            // İngilizce başlık yoksa Türkçe başlığı kullan
            if (empty($paket->adi_en) || strpos($paket->adi_en, 'Yoksan') !== false || strpos($paket->adi_en, 'باقة') !== false) {
                $updates['adi_en'] = $paket->adi . ' Package';
            }
            
            // Arapça başlık yoksa Türkçe başlığı kullan
            if (empty($paket->adi_ar) || strpos($paket->adi_ar, 'Yoksan') !== false || strpos($paket->adi_ar, 'Web Hosting') !== false) {
                $updates['adi_ar'] = 'باقة ' . $paket->adi;
            }
            
            // Boş açıklamalar için genel çeviri ekle
            if (empty(trim(strip_tags($paket->aciklama_en))) || strlen(trim(strip_tags($paket->aciklama_en))) < 50) {
                $updates['aciklama_en'] = 'Professional web package designed for your business. Contact us for detailed information.';
            }
            
            if (empty(trim(strip_tags($paket->aciklama_ar))) || strlen(trim(strip_tags($paket->aciklama_ar))) < 50) {
                $updates['aciklama_ar'] = 'باقة ويب احترافية مصممة لعملك. اتصل بنا للحصول على معلومات مفصلة.';
            }
            
            if (!empty($updates)) {
                DB::table('yazilimlar')->where('id', $paket->id)->update($updates);
            }
        }
        
        echo "✅ " . count($paketler) . " paket çevirisi temizlendi!\n";
        
        // Resimleri kontrol et
        $resimYoklar = DB::table('yazilimlar')
            ->where('dil', 1)
            ->where(function($q) {
                $q->whereNull('resim')
                  ->orWhere('resim', '')
                  ->orWhere('resim', 'null');
            })
            ->count();
        
        echo "⚠️ Resmi olmayan paket sayısı: $resimYoklar\n";
        
        if ($resimYoklar > 0) {
            // Varsayılan resim ata
            DB::table('yazilimlar')
                ->where('dil', 1)
                ->where(function($q) {
                    $q->whereNull('resim')
                      ->orWhere('resim', '')
                      ->orWhere('resim', 'null');
                })
                ->update(['resim' => 'default-package.jpg']);
            
            echo "✅ Varsayılan resim atandı: default-package.jpg\n";
        }
    }
}
