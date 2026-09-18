<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

/**
 * Haftalık paylaşım şablonu — "Marka X · Instagram · Salı: 3 paylaşım"
 *
 * gun: 1=Pazartesi ... 7=Pazar (ISO-8601, Carbon::dayOfWeekIso ile uyumlu)
 * Tarihe özel değişiklik için bkz. SosyalMedyaPlanIstisna — o şablonu ezer.
 */
class SosyalMedyaPlan extends Model
{
    protected $table = 'sosyal_medya_planlar';

    protected $fillable = ['hesap_id', 'gun', 'hedef_adet', 'aktif'];

    protected $casts = [
        'gun'        => 'integer',
        'hedef_adet' => 'integer',
        'aktif'      => 'boolean',
    ];

    public const GUNLER = [
        1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe',
        5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar',
    ];

    public function hesap()
    {
        return $this->belongsTo(SosyalMedyaHesap::class, 'hesap_id');
    }
}
