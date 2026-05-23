<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusOrder extends Model
{
    public const TAKEN = 1;

    public const PREPARING = 2;

    public const READY = 3;

    public const PAID = 4;

    public const CANCELED = 5;

    public $timestamps = false;
}
