<?php

namespace App\Models\Kanban;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class KanbanBoard extends Model
{
    protected $table = 'kanban_boards';

    protected $fillable = [
        'adi',
        'aciklama',
        'kategori',
        'renk',
        'kapak',
        'olusturan_id',
        'durum',
        'kilitli',
        'ozel',
    ];

    protected $casts = [
        'durum' => 'boolean',
        'kilitli' => 'boolean',
        'ozel' => 'boolean',
    ];

    /**
     * Pano kapak görselinin tam URL'i (yoksa null).
     */
    public function getKapakUrlAttribute(): ?string
    {
        if (empty($this->kapak)) {
            return null;
        }

        // İsteğin host'una göre URL üret (dev: 127.0.0.1:8000, canlı: gerçek domain)
        return asset('storage/' . ltrim($this->kapak, '/'));
    }

    public function olusturan()
    {
        return $this->belongsTo(Yonetici::class, 'olusturan_id');
    }

    public function lists()
    {
        return $this->hasMany(KanbanList::class, 'board_id')->orderBy('sira', 'asc');
    }

    public function aktifLists()
    {
        return $this->lists()->where('durum', 1);
    }

    public function members()
    {
        return $this->hasMany(KanbanBoardMember::class, 'board_id');
    }

    /**
     * Kullanıcının bu board'a erişim yetkisi var mı?
     */
    public function canAccess($yoneticiId)
    {
        // Oluşturan (sahibi) her zaman erişebilir
        if ($this->olusturan_id == $yoneticiId) {
            return true;
        }

        // Özel DEĞİLSE herkes görebilir (canlı varsayılan davranışı korunur)
        if (empty($this->ozel)) {
            return true;
        }

        // Özel pano → sadece eklenen üyeler
        if (!Schema::hasTable('kanban_board_members')) {
            return false;
        }
        try {
            return $this->members()->where('yonetici_id', $yoneticiId)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Kullanıcının bu board'da düzenleme yetkisi var mı?
     */
    public function canEdit($yoneticiId)
    {
        // Oluşturan (sahibi) her zaman düzenleyebilir
        if ($this->olusturan_id == $yoneticiId) {
            return true;
        }

        // Pano KİLİTLİYSE: sadece sahibi düzenleyebilir
        if (!empty($this->kilitli)) {
            return false;
        }

        // ÖZEL pano → sadece sahip/editor üyeler düzenleyebilir
        if (!empty($this->ozel)) {
            if (!Schema::hasTable('kanban_board_members')) {
                return false;
            }
            try {
                $member = $this->members()->where('yonetici_id', $yoneticiId)->first();
                return $member && in_array($member->rol, ['sahip', 'editor']);
            } catch (\Exception $e) {
                return false;
            }
        }

        // Aksi halde herkes düzenleyebilir (canlı varsayılan davranışı korunur)
        return true;
    }
}