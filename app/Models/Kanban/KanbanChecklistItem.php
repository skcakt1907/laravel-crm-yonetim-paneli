<?php

namespace App\Models\Kanban;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

class KanbanChecklistItem extends Model
{
    protected $table = 'kanban_checklist_items';

    protected $fillable = [
        'checklist_id',
        'metin',
        'tamamlandi',
        'tamamlayan_id',
        'tamamlanma_tarihi',
        'sira',
    ];

    protected $casts = [
        'tamamlandi' => 'boolean',
        'tamamlanma_tarihi' => 'datetime',
        'sira' => 'integer',
    ];

    public function checklist()
    {
        return $this->belongsTo(KanbanCardChecklist::class, 'checklist_id');
    }

    public function tamamlayan()
    {
        return $this->belongsTo(Yonetici::class, 'tamamlayan_id');
    }
}


