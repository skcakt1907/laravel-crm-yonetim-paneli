<?php

namespace App\Models\Kanban;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

class KanbanCardActivity extends Model
{
    protected $table = 'kanban_card_activities';

    protected $fillable = ['card_id', 'yazar_id', 'tip', 'aciklama'];

    public function card()
    {
        return $this->belongsTo(KanbanCard::class, 'card_id');
    }

    public function yazar()
    {
        return $this->belongsTo(Yonetici::class, 'yazar_id');
    }
}
