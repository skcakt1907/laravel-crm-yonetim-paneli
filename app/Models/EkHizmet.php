<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ek Hizmet (madde 15) — paket/ilan detayında "Yanında Satın Alınabilecekler".
 */
class EkHizmet extends Model
{
    protected $table = 'ek_hizmetler';

    // kategoriler: virgüllü kategori id listesi ("45" / "45,31"). Boş = tüm kategoriler.
    protected $fillable = ['ad', 'fiyat', 'ikon', 'aciklama', 'kategoriler', 'sira', 'durum'];

    protected $casts = [
        'fiyat' => 'decimal:2',
        'durum' => 'integer',
        'sira'  => 'integer',
    ];
}
