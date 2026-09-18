<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class SozlesmeKategori extends Model
{
    protected $table = 'crm_sozlesme_kategorileri';

    protected $fillable = ['ad', 'renk', 'sira', 'durum'];

    public function sozlesmeler()
    {
        return $this->hasMany(Sozlesme::class, 'kategori_id');
    }
}
