<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bayi extends Model
{
    protected $table = 'bayiler';
    
    protected $fillable = [
        'uye_id',
        'bayi_kodu',
        'komisyon_orani',
        'toplam_kazanc',
        'cekilebilir_bakiye',
        'cekilen_toplam',
        'adres',
        'adres_tarifi',
        'banka_adi',
        'iban',
        'hesap_sahibi',
        'vergi_no',
        'vergi_dairesi',
        'durum',
        'onay_durumu',
        'onay_tarihi',
    ];
    
    protected $casts = [
        'komisyon_orani' => 'decimal:2',
        'toplam_kazanc' => 'decimal:2',
        'cekilebilir_bakiye' => 'decimal:2',
        'cekilen_toplam' => 'decimal:2',
        'durum' => 'boolean',
        'onay_durumu' => 'boolean',
        'onay_tarihi' => 'datetime',
    ];
    
    /**
     * Bayi ile üye ilişkisi
     */
    public function uye()
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }
    
    /**
     * Bayi satışları
     */
    public function satislar()
    {
        return $this->hasMany(BayiSatis::class, 'bayi_id');
    }
    
    /**
     * Ödeme talepleri
     */
    public function odemeTalepleri()
    {
        return $this->hasMany(BayiOdemeTalebi::class, 'bayi_id');
    }
    
    /**
     * Bekleyen ödeme talepleri
     */
    public function bekleyenOdemeler()
    {
        return $this->odemeTalepleri()->where('durum', 'beklemede');
    }
    
    /**
     * Bu ayki satışlar
     */
    public function buAykiSatislar()
    {
        return $this->satislar()
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'));
    }
    
    /**
     * Bu ayki kazanç
     */
    public function buAykiKazanc()
    {
        return $this->buAykiSatislar()->sum('komisyon_tutari');
    }
    
    /**
     * Unique bayi kodu oluştur
     */
    public static function generateBayiKodu()
    {
        do {
            $kod = 'B' . date('Y') . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('bayi_kodu', $kod)->exists());
        
        return $kod;
    }
}
