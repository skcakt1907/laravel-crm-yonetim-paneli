<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('kuponlar')) {
            return; // Tablo yoksa migration'ı atla
        }
        
        // Her kolon için ayrı ayrı kontrol et ve ekle
        $columns_to_add = [
            'indirim' => "DECIMAL(10,2) NULL",
            'tip' => "ENUM('yuzde','tutar') DEFAULT 'yuzde'",
            'kullanim_limiti' => "INT NULL",
            'kullanim_sayisi' => "INT DEFAULT 0",
            'baslangic_tarih' => "DATE NULL",
            'bitis_tarih' => "DATE NULL",
            'durum' => "TINYINT(1) DEFAULT 1",
            'tarih' => "TIMESTAMP NULL"
        ];
        
        foreach ($columns_to_add as $column => $definition) {
            // Kolon var mı kontrol et
            $columns = DB::select("SHOW COLUMNS FROM `kuponlar` LIKE '{$column}'");
            if (empty($columns)) {
                // Kolon yoksa ekle
                $after_column = $this->getAfterColumn($column);
                $sql = "ALTER TABLE `kuponlar` ADD COLUMN `{$column}` {$definition}";
                if ($after_column) {
                    $sql .= " AFTER `{$after_column}`";
                }
                DB::statement($sql);
            }
        }
        
        // Mevcut verileri yeni kolonlara aktar
        try {
            $kuponlar = DB::table('kuponlar')->get();
            foreach ($kuponlar as $kupon) {
                $update_data = [];
                
                // miktar varsa ve indirim boşsa, indirim'e aktar
                if (isset($kupon->miktar) && $kupon->miktar && (!isset($kupon->indirim) || !$kupon->indirim)) {
                    $update_data['indirim'] = (float) $kupon->miktar;
                }
                
                // tur varsa ve tip boşsa, tip'e aktar
                if (isset($kupon->tur) && (!isset($kupon->tip) || !$kupon->tip)) {
                    $update_data['tip'] = ($kupon->tur == 1) ? 'yuzde' : 'tutar';
                }
                
                // bas_tarih varsa baslangic_tarih'e aktar
                if (isset($kupon->bas_tarih) && $kupon->bas_tarih && (!isset($kupon->baslangic_tarih) || !$kupon->baslangic_tarih)) {
                    try {
                        $date = null;
                        $formats = ['Y-m-d', 'd.m.Y', 'Y/m/d', 'd-m-Y'];
                        foreach ($formats as $format) {
                            try {
                                $date = \Carbon\Carbon::createFromFormat($format, trim($kupon->bas_tarih));
                                break;
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                        if ($date) {
                            $update_data['baslangic_tarih'] = $date->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        // Format uyumsuzsa atla
                    }
                }
                
                // bit_tarih varsa bitis_tarih'e aktar
                if (isset($kupon->bit_tarih) && $kupon->bit_tarih && (!isset($kupon->bitis_tarih) || !$kupon->bitis_tarih)) {
                    try {
                        $date = null;
                        $formats = ['Y-m-d', 'd.m.Y', 'Y/m/d', 'd-m-Y'];
                        foreach ($formats as $format) {
                            try {
                                $date = \Carbon\Carbon::createFromFormat($format, trim($kupon->bit_tarih));
                                break;
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                        if ($date) {
                            $update_data['bitis_tarih'] = $date->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        // Format uyumsuzsa atla
                    }
                }
                
                if (count($update_data) > 0) {
                    DB::table('kuponlar')->where('id', $kupon->id)->update($update_data);
                }
            }
        } catch (\Exception $e) {
            // Hata olursa devam et
        }
    }
    
    /**
     * Kolonun hangi kolondan sonra ekleneceğini belirle
     */
    private function getAfterColumn($column)
    {
        $after_map = [
            'indirim' => 'miktar',
            'tip' => 'indirim',
            'kullanim_limiti' => 'tip',
            'kullanim_sayisi' => 'kullanim_limiti',
            'baslangic_tarih' => 'bas_tarih',
            'bitis_tarih' => 'bit_tarih',
            'durum' => 'bitis_tarih',
            'tarih' => 'durum'
        ];
        
        return $after_map[$column] ?? null;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns_to_drop = ['indirim', 'tip', 'kullanim_limiti', 'kullanim_sayisi', 'baslangic_tarih', 'bitis_tarih', 'durum', 'tarih'];
        
        foreach ($columns_to_drop as $column) {
            $columns = DB::select("SHOW COLUMNS FROM `kuponlar` LIKE '{$column}'");
            if (!empty($columns)) {
                DB::statement("ALTER TABLE `kuponlar` DROP COLUMN `{$column}`");
            }
        }
    }
};
