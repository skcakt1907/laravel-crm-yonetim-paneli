<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    use HasFactory;

    protected $table = 'crm_pipelines';

    protected $fillable = [
        'adi',
        'aciklama',
        'sira',
        'varsayilan',
    ];

    protected $casts = [
        'varsayilan' => 'boolean',
        'sira' => 'integer',
    ];

    public function stages()
    {
        return $this->hasMany(Stage::class, 'pipeline_id')->orderBy('sira');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'pipeline_id');
    }
}

