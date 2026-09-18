<?php

namespace App\Models\Kanban;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

class KanbanCardComment extends Model
{
    protected $table = 'kanban_card_comments';

    protected $fillable = [
        'card_id',
        'yazar_id',
        'yorum',
    ];

    public function card()
    {
        return $this->belongsTo(KanbanCard::class, 'card_id');
    }

    public function yazar()
    {
        return $this->belongsTo(Yonetici::class, 'yazar_id');
    }
}


