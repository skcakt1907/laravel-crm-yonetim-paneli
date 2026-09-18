<?php

namespace App\Models\Kanban;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

class KanbanCardAttachment extends Model
{
    protected $table = 'kanban_card_attachments';

    protected $fillable = [
        'card_id',
        'yukleyen_id',
        'dosya_adi',
        'dosya_yolu',
        'dosya_tipi',
        'dosya_boyutu',
        'aciklama',
    ];

    protected $casts = [
        'dosya_boyutu' => 'integer',
    ];

    public function card()
    {
        return $this->belongsTo(KanbanCard::class, 'card_id');
    }

    public function yukleyen()
    {
        return $this->belongsTo(Yonetici::class, 'yukleyen_id');
    }

    public function getDosyaBoyutuFormatAttribute()
    {
        $bytes = $this->dosya_boyutu;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}


