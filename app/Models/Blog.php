<?php

namespace App\Models;

use App\Traits\Ceviribilir;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use Ceviribilir;

    protected $table = 'blog';
    
    protected $fillable = [
        'dil_id', 'adi', 'seo', 'aciklama', 'icerik', 'resim',
        'keywords', 'description', 'durum', 'sira', 'hit', 'tarih'
    ];
    
    protected $casts = [
        'durum' => 'boolean',
        'tarih' => 'date',
    ];
    
    public function dil()
    {
        return $this->belongsTo(Dil::class, 'dil_id');
    }
}
