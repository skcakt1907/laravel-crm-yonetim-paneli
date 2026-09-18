<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalisanTicket extends Model
{
    protected $table = 'calisan_tickets';
    
    protected $fillable = [
        'gonderen_id',
        'alici_id',
        'konu',
        'mesaj',
        'oncelik',
        'durum',
        'kategori_id',
        'ust_ticket_id',
        'okunma_tarihi',
        'cozulme_tarihi',
    ];
    
    protected $casts = [
        'oncelik' => 'integer',
        'durum' => 'integer',
        'okunma_tarihi' => 'datetime',
        'cozulme_tarihi' => 'datetime',
    ];
    
    // Öncelik sabitleri
    const ONCELIK_DUSUK = 1;
    const ONCELIK_NORMAL = 2;
    const ONCELIK_YUKSEK = 3;
    const ONCELIK_ACIL = 4;
    
    // Durum sabitleri
    const DURUM_ACIK = 0;
    const DURUM_DEVAM = 1;
    const DURUM_COZULDU = 2;
    const DURUM_KAPATILDI = 3;
    
    /**
     * Gönderen yönetici
     */
    public function gonderen()
    {
        return $this->belongsTo(Yonetici::class, 'gonderen_id');
    }
    
    /**
     * Alıcı yönetici
     */
    public function alici()
    {
        return $this->belongsTo(Yonetici::class, 'alici_id');
    }
    
    /**
     * Kategori
     */
    public function kategori()
    {
        return $this->belongsTo(TicketKategori::class, 'kategori_id');
    }
    
    /**
     * Üst ticket (cevap için)
     */
    public function ustTicket()
    {
        return $this->belongsTo(CalisanTicket::class, 'ust_ticket_id');
    }
    
    /**
     * Alt cevaplar
     */
    public function cevaplar()
    {
        return $this->hasMany(CalisanTicket::class, 'ust_ticket_id')->orderBy('created_at', 'asc');
    }
    
    /**
     * Bildirimler
     */
    public function bildirimler()
    {
        return $this->hasMany(TicketBildirim::class, 'ticket_id');
    }
    
    /**
     * Öncelik adı
     */
    public function getOncelikAdiAttribute()
    {
        return match($this->oncelik) {
            self::ONCELIK_DUSUK => 'Düşük',
            self::ONCELIK_NORMAL => 'Normal',
            self::ONCELIK_YUKSEK => 'Yüksek',
            self::ONCELIK_ACIL => 'Acil',
            default => 'Bilinmeyen',
        };
    }
    
    /**
     * Öncelik rengi
     */
    public function getOncelikRengiAttribute()
    {
        return match($this->oncelik) {
            self::ONCELIK_DUSUK => 'secondary',
            self::ONCELIK_NORMAL => 'info',
            self::ONCELIK_YUKSEK => 'warning',
            self::ONCELIK_ACIL => 'danger',
            default => 'secondary',
        };
    }
    
    /**
     * Durum adı
     */
    public function getDurumAdiAttribute()
    {
        return match($this->durum) {
            self::DURUM_ACIK => 'Açık',
            self::DURUM_DEVAM => 'Devam Ediyor',
            self::DURUM_COZULDU => 'Çözüldü',
            self::DURUM_KAPATILDI => 'Kapatıldı',
            default => 'Bilinmeyen',
        };
    }
    
    /**
     * Durum rengi
     */
    public function getDurumRengiAttribute()
    {
        return match($this->durum) {
            self::DURUM_ACIK => 'primary',
            self::DURUM_DEVAM => 'warning',
            self::DURUM_COZULDU => 'success',
            self::DURUM_KAPATILDI => 'secondary',
            default => 'secondary',
        };
    }
    
    /**
     * Okundu mu?
     */
    public function isOkundu()
    {
        return $this->okunma_tarihi !== null;
    }
}
