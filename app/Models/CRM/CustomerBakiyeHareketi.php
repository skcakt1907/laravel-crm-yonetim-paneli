<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class CustomerBakiyeHareketi extends Model
{
    protected $table = 'crm_customer_bakiye_hareketleri';

    protected $fillable = [
        'musteri_id',
        'tutar',
        'tip',
        'aciklama',
        'yonetici_id',
    ];

    protected $casts = [
        'tutar' => 'decimal:2',
    ];

    public function musteri()
    {
        return $this->belongsTo(Customer::class, 'musteri_id');
    }
}
