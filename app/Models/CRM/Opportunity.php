<?php

namespace App\Models\CRM;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory;

    protected $table = 'crm_opportunities';

    protected $fillable = [
        'musteri_id',
        'pipeline_id',
        'stage_id',
        'baslik',
        'tutar',
        'para_birimi',
        'durum',
        'sorumlu_id',
        'beklenen_kapanis',
        'oncelik',
        'aciklama',
    ];

    protected $casts = [
        'tutar' => 'float',
        'beklenen_kapanis' => 'datetime',
        'oncelik' => 'integer',
    ];

    public function musteri()
    {
        return $this->belongsTo(Customer::class, 'musteri_id');
    }

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    public function sorumlu()
    {
        return $this->belongsTo(Yonetici::class, 'sorumlu_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'firsat_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'firsat_id');
    }
}

