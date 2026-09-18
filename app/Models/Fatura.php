<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fatura extends Model
{
    use HasFactory;

    protected $table = 'faturalar';

    protected $fillable = [
        'uyeid',
        'fatura_no',
        'aciklama',
        'tutar',
        'kdv',
        'toplam',
        'durum',
        'tarih',
        // Tahsilat GUNU — raporlar/gunluk hareketler bu alana bakar.
        // Fillable'da OLMADIGI icin $fatura->update(['odenen_tarih'=>...])
        // sessizce yok sayiliyordu (mass-assignment korumasi).
        'odenen_tarih',
        // Islemin tam ZAMANI — musteriye gosterim ve fatura PDF'i.
        'odeme_tarihi',
        'odeme_yontemi',
    ];

    protected $casts = [
        'tutar' => 'decimal:2',
        'kdv' => 'decimal:2',
        'toplam' => 'decimal:2',
        'durum' => 'boolean',
        'tarih' => 'datetime',
        'odeme_tarihi' => 'datetime',
    ];

    public function uye()
    {
        return $this->belongsTo(Uye::class, 'uyeid');
    }
}







