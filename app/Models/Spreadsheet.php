<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spreadsheet extends Model
{
    protected $table = 'spreadsheets';
    
    protected $fillable = [
        'ad', 'aciklama', 'ikon', 'veri',
        'olusturan_id', 'yetkili_ids', 'herkes_gorur',
        'otomatik_kaydet', 'son_kaydeden_id',
    ];
    
    protected $casts = [
        'yetkili_ids'     => 'array',
        'herkes_gorur'    => 'boolean',
        'otomatik_kaydet' => 'boolean',
    ];
    
    /**
     * Belirli bir admin bu spreadsheet'i görebilir mi?
     */
    public function canAccess($adminId)
    {
        if ($this->herkes_gorur) return true;
        if ((int) $this->olusturan_id === (int) $adminId) return true;
        // yetkili_ids JSON'da metin ("5") veya sayı (5) olarak durabilir — ikisini de yakala
        $yetkililer = array_map('intval', (array) ($this->yetkili_ids ?? []));
        return in_array((int) $adminId, $yetkililer, true);
    }

    /**
     * Belirli bir admin için erişilebilir spreadsheet'ler.
     * NOT: JSON_CONTAINS tip duyarlıdır; formdan gelen ID'ler metin olarak
     * kaydedilebildiği için hem sayı hem metin varyantı sorgulanır —
     * aksi halde yetkili seçilen kişi tabloyu listede göremiyordu.
     */
    public static function accessibleBy($adminId)
    {
        return self::where(function($q) use ($adminId) {
            $q->where('herkes_gorur', true)
              ->orWhere('olusturan_id', $adminId)
              ->orWhereJsonContains('yetkili_ids', (int) $adminId)
              ->orWhereJsonContains('yetkili_ids', (string) $adminId);
        })->orderBy('updated_at', 'desc');
    }
    
    public function olusturan()
    {
        return $this->belongsTo(\App\Models\Yonetici::class, 'olusturan_id');
    }
    
    public function sonKaydeden()
    {
        return $this->belongsTo(\App\Models\Yonetici::class, 'son_kaydeden_id');
    }
}