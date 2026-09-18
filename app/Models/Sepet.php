<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sepet extends Model
{
    use HasFactory;

    protected $table = 'sepet';

    protected $fillable = [
        'user_id',
        'urun_id',
        'urun_tipi',
        'urun_adi',
        'fiyat',
        'miktar',
        'aciklama',
    ];

    protected $casts = [
        'fiyat' => 'decimal:2',
        'miktar' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(Uye::class, 'user_id');
    }
}




