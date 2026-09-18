<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

/**
 * Tarihe özel paylaşım kalemi — "Reels: klinik tanıtım", "30 Ağustos postu"
 *
 * Haftalık şablon (SosyalMedyaPlan) yalnızca SAYI tutar ve her hafta tekrar
 * eder. Bu tablo ise belirli bir TARİHTE yapılacak paylaşımların adlarını
 * tutar; o güne kalem girilmişse hedef otomatik olarak kalem sayısı olur.
 */
class SosyalMedyaPlanKalem extends Model
{
    protected $table = 'sosyal_medya_plan_kalemleri';

    protected $fillable = ['hesap_id', 'tarih', 'baslik', 'sira', 'olusturan_id'];

    protected $casts = [
        'tarih' => 'date',
        'sira'  => 'integer',
    ];

    public function hesap()
    {
        return $this->belongsTo(SosyalMedyaHesap::class, 'hesap_id');
    }

    /** Bu kalem için yapılmış paylaşım (varsa) — checklist işareti buradan okunur */
    public function paylasim()
    {
        return $this->hasOne(SosyalMedyaPaylasim::class, 'kalem_id');
    }
}
