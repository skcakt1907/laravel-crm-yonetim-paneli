<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kredi extends Model
{
    use HasFactory;

    protected $table = 'krediler';

    protected $fillable = [
        'uyeid',
        'tutar',
        'islem_tipi',
        'aciklama',
        'paytronay',
        'tarih',
    ];

    protected $casts = [
        'tutar' => 'decimal:2',
        'paytronay' => 'boolean',
        'tarih' => 'datetime',
    ];

    public function uye()
    {
        return $this->belongsTo(Uye::class, 'uyeid');
    }
}







