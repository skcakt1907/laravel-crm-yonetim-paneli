<?php

namespace App\Models\CRM;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Model;

class TaskMessage extends Model
{
    protected $table = 'crm_task_messages';

    protected $fillable = [
        'task_id',
        'gonderen_id',
        'gonderen_adi',
        'mesaj',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function gonderen()
    {
        return $this->belongsTo(Yonetici::class, 'gonderen_id');
    }
}
