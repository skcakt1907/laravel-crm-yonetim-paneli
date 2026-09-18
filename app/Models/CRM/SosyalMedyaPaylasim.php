<?php

namespace App\Models\CRM;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

/**
 * Yapılan tek bir paylaşım. Günde birden fazla olabilir — her paylaşım
 * ayrı satır, her birinin kendi linki ve saati var.
 *
 * tarih          : paylaşımın YAPILDIĞI gün
 * isaretlendi_at : panele İŞARETLENDİĞİ an
 * İkisi farklıysa sonradan işaretlenmiş demektir (bkz. gecIsaretlendi).
 */
class SosyalMedyaPaylasim extends Model
{
    protected $table = 'sosyal_medya_paylasimlar';

    protected $fillable = [
        'hesap_id', 'tarih', 'kalem_id', 'link', 'not', 'isaretleyen_id', 'isaretlendi_at',
    ];

    protected $casts = [
        'tarih'          => 'date',
        'isaretlendi_at' => 'datetime',
    ];

    public function hesap()
    {
        return $this->belongsTo(SosyalMedyaHesap::class, 'hesap_id');
    }

    public function isaretleyen()
    {
        return $this->belongsTo(Yonetici::class, 'isaretleyen_id');
    }

    /** Paylaşım günü ile işaretleme günü farklıysa geç işaretlenmiştir. */
    public function gecIsaretlendi(): bool
    {
        if (! $this->isaretlendi_at || ! $this->tarih) {
            return false;
        }

        return $this->isaretlendi_at->toDateString() !== $this->tarih->toDateString();
    }

    /** Hangi planlanmış kaleme ait — kalemsiz (serbest) paylaşımlarda null */
    public function kalem()
    {
        return $this->belongsTo(SosyalMedyaPlanKalem::class, 'kalem_id');
    }
}
