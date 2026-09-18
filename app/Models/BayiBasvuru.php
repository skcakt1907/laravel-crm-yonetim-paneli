<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BayiBasvuru extends Model
{
    protected $table = 'bayi_basvurulari';

    /**
     * Formdan gelen alanlar. Değerlendirme alanları (durum, onaylayan_id,
     * uye_id, yonetici_id, bayi_id ...) BİLEREK dışarıda — sunucu tarafında
     * explicit atanır (mass-assignment koruması).
     */
    protected $fillable = [
        'firma_adi', 'ad_soyad', 'email', 'telefon',
        'il', 'ilce', 'faaliyet', 'mesaj', 'kvkk', 'ip',
    ];

    protected $casts = [
        'kvkk'        => 'boolean',
        'okundu'      => 'boolean',
        'onay_tarihi' => 'datetime',
    ];

    public function bekliyorMu(): bool
    {
        return $this->durum === 'beklemede';
    }
}
