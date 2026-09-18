<?php

namespace App\Models\CRM;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

/**
 * Bir güne ait ERTELEME veya İPTAL kaydı. Paylaşım değil, karardır —
 * o yüzden paylaşımlardan ayrı tabloda. Günde tek karar olduğu için
 * (hesap_id, tarih) üzerinde benzersizlik kısıtı kurulabiliyor;
 * aynı tabloda olsalardı bu mümkün olmazdı.
 */
class SosyalMedyaGunNotu extends Model
{
    protected $table = 'sosyal_medya_gun_notlari';

    protected $fillable = [
        'hesap_id', 'tarih', 'tip', 'ertelendi_tarih', 'sebep',
        'isaretleyen_id', 'isaretlendi_at',
    ];

    protected $casts = [
        'tarih'           => 'date',
        'ertelendi_tarih' => 'date',
        'isaretlendi_at'  => 'datetime',
    ];

    public const TIP_ERTELENDI = 'ertelendi';
    public const TIP_IPTAL     = 'iptal';

    public function hesap()
    {
        return $this->belongsTo(SosyalMedyaHesap::class, 'hesap_id');
    }

    public function isaretleyen()
    {
        return $this->belongsTo(Yonetici::class, 'isaretleyen_id');
    }
}
