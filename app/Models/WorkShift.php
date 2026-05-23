<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class WorkShift extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'start',
        'end',
        'active',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'active' => 'boolean',
    ];

    public function shiftWorkers(): HasMany
    {
        return $this->hasMany(ShiftWorker::class, 'work_shift_id');
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(
            Order::class,
            ShiftWorker::class,
            'work_shift_id',
            'shift_worker_id',
            'id',
            'id'
        );
    }
}
