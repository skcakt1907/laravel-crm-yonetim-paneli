<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BayiOdemeTalebi extends Model
{
    protected $table = 'bayi_odeme_talepleri';
    
    protected $fillable = [
        'bayi_id',
        'talep_tutari',
        'durum',
        'aciklama',
        'red_nedeni',
        'onay_tarihi',
        'onaylayan_admin_id',
    ];
    
    protected $casts = [
        'talep_tutari' => 'decimal:2',
        'onay_tarihi' => 'datetime',
    ];
    
    public function bayi()
    {
        return $this->belongsTo(Bayi::class, 'bayi_id');
    }
}







