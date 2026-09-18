<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BayiSatis extends Model
{
    protected $table = 'bayi_satislar';
    
    protected $fillable = [
        'bayi_id',
        'fatura_id',
        'satis_tutari',
        'komisyon_orani',
        'komisyon_tutari',
        'odendi',
        'odeme_tarihi',
    ];
    
    protected $casts = [
        'satis_tutari' => 'decimal:2',
        'komisyon_orani' => 'decimal:2',
        'komisyon_tutari' => 'decimal:2',
        'odendi' => 'boolean',
        'odeme_tarihi' => 'datetime',
    ];
    
    public function bayi()
    {
        return $this->belongsTo(Bayi::class, 'bayi_id');
    }
    
    public function fatura()
    {
        return $this->belongsTo(Fatura::class, 'fatura_id');
    }
}







