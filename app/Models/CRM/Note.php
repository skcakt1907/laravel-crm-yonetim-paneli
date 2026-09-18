<?php

namespace App\Models\CRM;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $table = 'crm_notes';

    protected $fillable = [
        'musteri_id',
        'firsat_id',
        'olusturan_id',
        'baslik',
        'icerik',
    ];

    public function musteri()
    {
        return $this->belongsTo(Customer::class, 'musteri_id');
    }

    public function firsat()
    {
        return $this->belongsTo(Opportunity::class, 'firsat_id');
    }

    public function olusturan()
    {
        return $this->belongsTo(Yonetici::class, 'olusturan_id');
    }
}

