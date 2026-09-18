<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ar-Ge Anketi cevabı (Rubito).
 */
class ArgeAnketi extends Model
{
    protected $table = 'arge_anketleri';

    protected $fillable = [
        'ad_soyad', 'email', 'telefon', 'dogum_tarihi',
        'marka_guclu', 'marka_gelistir', 'geri_bildirim', 'rakipler', 'rakip_kampanya',
        'ajans_calisti', 'ajans_katki', 'ajans_beklenti', 'duydu_mu', 'butce',
        'ip', 'okundu',
    ];

    protected $casts = [
        'dogum_tarihi' => 'date',
        'okundu'       => 'integer',
    ];
}
