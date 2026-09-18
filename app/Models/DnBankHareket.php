<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * DN Bank coin hareketi (defter kaydı).
 * tutar: (+) yükleme/ekleme, (-) harcama/iade.
 */
class DnBankHareket extends Model
{
    protected $table = 'dnbank_hareketleri';

    protected $fillable = [
        'uye_id', 'kredi_id', 'tip', 'tutar', 'bakiye_sonra',
        'aciklama', 'fatura_id', 'yonetici_id',
    ];

    protected $casts = [
        'tutar'        => 'decimal:2',
        'bakiye_sonra' => 'decimal:2',
    ];

    public function uye()
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }
}
