<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketBildirim extends Model
{
    protected $table = 'ticket_bildirimler';
    
    protected $fillable = [
        'yonetici_id',
        'ticket_id',
        'mesaj',
        'okundu',
    ];
    
    protected $casts = [
        'okundu' => 'boolean',
    ];
    
    /**
     * Yönetici
     */
    public function yonetici()
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }
    
    /**
     * Ticket
     */
    public function ticket()
    {
        return $this->belongsTo(CalisanTicket::class, 'ticket_id');
    }
}
