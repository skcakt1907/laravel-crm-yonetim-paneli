<?php

namespace App\Models\Kanban;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class KanbanCard extends Model
{
    protected $table = 'kanban_cards';

    protected $fillable = [
        'list_id',
        'baslik',
        'aciklama',
        'atanan_id',
        'son_tarih',
        'oncelik',
        'durum',
        'sira',
        'etiketler',
        'linkler',
        'renk',
        'kapak',
        'archived_at',
    ];

    protected $casts = [
        'son_tarih' => 'date',
        'sira' => 'integer',
        'etiketler' => 'array',
        'linkler' => 'array',
        'archived_at' => 'datetime',
    ];

    protected $appends = ['kapak_url'];

    /**
     * Kapak görselinin tam URL'i (yoksa null).
     */
    public function getKapakUrlAttribute(): ?string
    {
        if (empty($this->kapak)) {
            return null;
        }

        return asset('storage/' . ltrim($this->kapak, '/'));
    }

    public function list()
    {
        return $this->belongsTo(KanbanList::class, 'list_id');
    }

    public function atanan()
    {
        return $this->belongsTo(Yonetici::class, 'atanan_id');
    }

    public function comments()
    {
        return $this->hasMany(KanbanCardComment::class, 'card_id')->orderBy('created_at', 'asc');
    }

    public function checklists()
    {
        return $this->hasMany(KanbanCardChecklist::class, 'card_id')->orderBy('sira');
    }

    public function attachments()
    {
        return $this->hasMany(KanbanCardAttachment::class, 'card_id')->orderBy('created_at', 'desc');
    }

    public function members()
    {
        return $this->hasMany(KanbanCardMember::class, 'card_id');
    }

    public function activities()
    {
        return $this->hasMany(KanbanCardActivity::class, 'card_id')->latest();
    }

    public function scopeActive($q)
    {
        return $q->whereNull('archived_at');
    }

    public function getOncelikRenkAttribute()
    {
        return match($this->oncelik) {
            'dusuk' => 'success',
            'normal' => 'info',
            'yuksek' => 'warning',
            'acil' => 'danger',
            default => 'info',
        };
    }

    public function getOncelikIconAttribute()
    {
        return match($this->oncelik) {
            'dusuk' => 'mdi-arrow-down',
            'normal' => 'mdi-minus',
            'yuksek' => 'mdi-arrow-up',
            'acil' => 'mdi-alert',
            default => 'mdi-minus',
        };
    }
}