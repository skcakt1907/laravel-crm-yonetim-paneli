<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Yonetici extends Authenticatable
{
    use Notifiable;
    
    protected $table = 'yoneticiler';
    
    protected $fillable = [
        'kullaniciadi',
        'sifre',
        'adi',
        'email',
        'telefon',
        'yetki',
        'rol',
        'durum',
        'son_giris',
        'son_ip',
    ];
    
    protected $hidden = [
        'sifre',
    ];
    
    protected $casts = [
        'yetki' => 'integer',
        'rol' => 'integer',
        'durum' => 'boolean',
        'son_giris' => 'datetime',
    ];
    
    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->sifre;
    }
    
    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }
    
    // Rol sabitleri
    const ROL_PATRON = 1;
    const ROL_CALISAN = 2;
    const ROL_BAYI = 3;
    const ROL_MUSTERI = 4;
    
    /**
     * Rol adını döndür
     */
    public function getRolAdiAttribute()
    {
        return match($this->rol) {
            self::ROL_PATRON => 'Patron',
            self::ROL_CALISAN => 'Çalışan',
            self::ROL_BAYI => 'Bayi',
            self::ROL_MUSTERI => 'Müşteri',
            default => 'Bilinmeyen',
        };
    }
    
    /**
     * Patron mu kontrol et
     */
    public function isPatron()
    {
        return $this->rol === self::ROL_PATRON;
    }
    
    /**
     * Çalışan mı kontrol et
     */
    public function isCalisan()
    {
        return $this->rol === self::ROL_CALISAN;
    }
    
    /**
     * Bayi mi kontrol et
     */
    public function isBayi()
    {
        return $this->rol === self::ROL_BAYI;
    }
    
    /**
     * Müşteri mi kontrol et
     */
    public function isMusteri()
    {
        return $this->rol === self::ROL_MUSTERI;
    }
    
    /**
     * Bayi bilgileri (eğer bayi ise)
     */
    public function bayi()
    {
        return $this->hasOne(Bayi::class, 'yonetici_id');
    }
    
    /**
     * Gönderilen ticket'lar
     */
    public function gonderilenTicketlar()
    {
        return $this->hasMany(CalisanTicket::class, 'gonderen_id');
    }
    
    /**
     * Alınan ticket'lar
     */
    public function alinanTicketlar()
    {
        return $this->hasMany(CalisanTicket::class, 'alici_id');
    }
}
