<?php

namespace App\Models\Kanban;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

class KanbanCardMember extends Model
{
    protected $table = 'kanban_card_members';

    protected $fillable = [
        'card_id',
        'yonetici_id',
    ];

    public function card()
    {
        return $this->belongsTo(KanbanCard::class, 'card_id');
    }

    public function yonetici()
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }
}


