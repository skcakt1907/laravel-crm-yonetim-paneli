<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketKategori extends Model
{
    protected $table = 'ticket_kategoriler';
    
    protected $fillable = [
        'adi',
        'renk',
        'ikon',
        'sira',
        'durum',
    ];
    
    protected $casts = [
        'durum' => 'boolean',
        'sira' => 'integer',
    ];
    
    /**
     * Bu kategorideki ticket'lar
     */
    public function tickets()
    {
        return $this->hasMany(CalisanTicket::class, 'kategori_id');
    }
}
