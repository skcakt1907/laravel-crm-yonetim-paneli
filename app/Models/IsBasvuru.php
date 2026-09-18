<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * İş Başvurusu (Rubito) — ornek.com/is-basvurusu'nun native hâli.
 */
class IsBasvuru extends Model
{
    protected $table = 'is_basvurulari';

    protected $fillable = [
        'ad_soyad', 'email', 'telefon', 'calisma_durumu', 'egitim_duzeyi',
        'pozisyon', 'deneyim_yili', 'lokasyon', 'dil', 'maas_beklenti',
        'cv_dosya', 'ek_bilgi', 'ip',
    ];

    protected $casts = [
        'deneyim_yili' => 'integer',
        'okundu'       => 'integer',
    ];
}
