<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'start',
        'end',
        'active'
    ];
}
