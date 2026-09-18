<?php

namespace App\Models;

use App\Traits\Ceviribilir;
use Illuminate\Database\Eloquent\Model;

class Hizmet extends Model
{
    use Ceviribilir;

    protected $table = 'hizmetler';
    protected $guarded = [];
    public $timestamps = false;
}
