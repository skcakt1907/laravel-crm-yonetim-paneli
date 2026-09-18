<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $table = 'crm_stages';

    protected $fillable = [
        'pipeline_id',
        'adi',
        'olasilik',
        'renk',
        'sira',
    ];

    protected $casts = [
        'olasilik' => 'integer',
        'sira' => 'integer',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'stage_id');
    }
}

