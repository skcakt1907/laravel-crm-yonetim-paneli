<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satilan extends Model
{
    use HasFactory;

    protected $table = 'satilanlar';

    protected $fillable = [
        'uyeid',
        'domain',
        'paket_adi',
        'paket_id',
        'kategori_id',
        'tipi',
        'fiyat',
        'durum',
        'baslangic_tarihi',
        'bitis_tarihi',
    ];

    protected $casts = [
        'tipi' => 'integer',
        'fiyat' => 'decimal:2',
        'durum' => 'integer',
        'baslangic_tarihi' => 'datetime',
        'bitis_tarihi' => 'datetime',
    ];

    public function uye()
    {
        return $this->belongsTo(Uye::class, 'uyeid');
    }

    public function hosting_kategori()
    {
        return $this->belongsTo(HostingKategori::class, 'kategori_id');
    }
}







