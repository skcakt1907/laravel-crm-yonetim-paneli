<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

/**
 * Tarihe özel hedef — haftalık şablonu EZER.
 *
 * İki işi birden görür:
 *   hedef_adet = 5  ->  "12 Eylül kampanya, 5 paylaşım"
 *   hedef_adet = 0  ->  "29 Ekim tatil, paylaşım yok"
 *
 * Bu sayede resmî tatiller için ayrı bir tablo/mekanizma gerekmiyor.
 */
class SosyalMedyaPlanIstisna extends Model
{
    protected $table = 'sosyal_medya_plan_istisnalar';

    protected $fillable = ['hesap_id', 'tarih', 'hedef_adet', 'sebep', 'olusturan_id'];

    protected $casts = [
        'tarih'      => 'date',
        'hedef_adet' => 'integer',
    ];

    public function hesap()
    {
        return $this->belongsTo(SosyalMedyaHesap::class, 'hesap_id');
    }
}
