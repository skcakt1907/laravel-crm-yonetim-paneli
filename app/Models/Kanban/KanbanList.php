<?php

namespace App\Models\Kanban;

use Illuminate\Database\Eloquent\Model;

class KanbanList extends Model
{
    protected $table = 'kanban_lists';

    protected $fillable = [
        'board_id',
        'adi',
        'aciklama',
        'renk',
        'sira',
        'durum',
        'archived_at',
    ];

    protected $casts = [
        'sira' => 'integer',
        'durum' => 'boolean',
    ];

    public function board()
    {
        return $this->belongsTo(KanbanBoard::class, 'board_id');
    }

    public function cards()
    {
        return $this->hasMany(KanbanCard::class, 'list_id')->orderBy('sira', 'asc');
    }

    public function aktifCards()
    {
        return $this->cards()->where('durum', 'aktif');
    }

    public function scopeActive($q)
    {
        return $q->whereNull('archived_at');
    }
}
