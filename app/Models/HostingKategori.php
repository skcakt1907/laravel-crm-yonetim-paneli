<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostingKategori extends Model
{
    use HasFactory;

    protected $table = 'hosting_kategori';

    protected $fillable = [
        'adi',
        'kisa',
        'aciklama',
        'resim',
        'seo',
        'sira',
        'durum',
    ];

    protected $casts = [
        'sira' => 'integer',
        'durum' => 'boolean',
    ];

    public function hostingler()
    {
        return $this->hasMany(Hosting::class, 'kategori');
    }

    public function satilanlar()
    {
        return $this->hasMany(Satilan::class, 'kategori_id');
    }
}







