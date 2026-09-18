<?php

namespace App\Models;

use App\Traits\Ceviribilir;
use Illuminate\Database\Eloquent\Model;

class Sayfa extends Model
{
    use Ceviribilir;

    protected $table = 'sayfalar';
    
    protected $fillable = [
        'dil_id', 'adi', 'seo', 'aciklama', 'icerik', 'resim',
        'keywords', 'description', 'anasayfa', 'durum', 'sira', 'hit'
    ];
    
    protected $casts = [
        'anasayfa' => 'boolean',
        'durum' => 'boolean',
    ];
    
    public function dil()
    {
        return $this->belongsTo(Dil::class, 'dil_id');
    }
}
