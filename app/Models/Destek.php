<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destek extends Model
{
    use HasFactory;

    protected $table = 'destek';

    public $timestamps = false;

    protected $fillable = [
        'uyeid',
        'baslik',
        'hizmet',
        'mesaj',
        'oncelik',
        'durum',
        'ustid',
        'tarih',
        'son_cevap',
        // Eski sistem kolonları
        'departman',
        'dosya',
        'ip',
        'yztarih',
        'son_tarih',
    ];

    protected $casts = [
        'oncelik' => 'integer',
        'durum' => 'integer',
        'ustid' => 'integer',
        'tarih' => 'datetime',
        'son_cevap' => 'datetime',
    ];

    public function uye()
    {
        return $this->belongsTo(Uye::class, 'uyeid');
    }

    public function ustDestek()
    {
        return $this->belongsTo(Destek::class, 'ustid');
    }

    public function cevaplar()
    {
        return $this->hasMany(Destek::class, 'ustid');
    }
}







