<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class SosyalMedyaKayit extends Model
{
    protected $table = 'sosyal_medya_kayitlari';

    protected $fillable = [
        'crm_musteri_id', 'baslik', 'firma', 'email', 'telefon',
        'genel_not', 'olusturan_id',
    ];

    public function hesaplar()
    {
        return $this->hasMany(SosyalMedyaHesap::class, 'kayit_id')->orderBy('sira')->orderBy('id');
    }

    public function musteri()
    {
        return $this->belongsTo(Customer::class, 'crm_musteri_id');
    }
}
