<?php

namespace App\Models\Kanban;

use Illuminate\Database\Eloquent\Model;

class KanbanCardChecklist extends Model
{
    protected $table = 'kanban_card_checklists';

    protected $fillable = [
        'card_id',
        'baslik',
        'sira',
    ];

    protected $casts = [
        'sira' => 'integer',
    ];

    public function card()
    {
        return $this->belongsTo(KanbanCard::class, 'card_id');
    }

    public function items()
    {
        return $this->hasMany(KanbanChecklistItem::class, 'checklist_id')->orderBy('sira');
    }
}


