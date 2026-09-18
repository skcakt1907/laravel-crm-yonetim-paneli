<?php

namespace App\Models;

use App\Traits\Ceviribilir;
use Illuminate\Database\Eloquent\Model;

class Yazilim extends Model
{
    use Ceviribilir;

    protected $table = 'yazilimlar';
    protected $guarded = [];
    public $timestamps = false;
}
