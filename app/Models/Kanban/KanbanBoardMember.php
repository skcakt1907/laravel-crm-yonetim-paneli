<?php

namespace App\Models\Kanban;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

class KanbanBoardMember extends Model
{
    protected $table = 'kanban_board_members';

    protected $fillable = [
        'board_id',
        'yonetici_id',
        'rol',
    ];

    public function board()
    {
        return $this->belongsTo(KanbanBoard::class, 'board_id');
    }

    public function yonetici()
    {
        return $this->belongsTo(Yonetici::class, 'yonetici_id');
    }
}


