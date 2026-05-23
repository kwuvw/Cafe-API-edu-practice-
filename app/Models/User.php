<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUS_WORKING = 'working';

    public const STATUS_FIRED = 'fired';

    private const ROLE_CODE_MAP = [
        1 => 'admin',
        2 => 'waiter',
        3 => 'cook',
    ];

    public $timestamps = false;

    protected $fillable = [
        'name',
        'surname',
        'patronymic',
        'login',
        'password',
        'photo_file',
        'role_id',
        'status'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    protected $casts = [
        'password' => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function shiftWorkers(): HasMany
    {
        return $this->hasMany(ShiftWorker::class, 'user_id');
    }

    public function getRoleCodeAttribute(): string
    {
        return self::ROLE_CODE_MAP[(int) $this->role_id] ?? '';
    }

    public function getDisplayNameAttribute(): string
    {
        $fullName = trim(implode(' ', array_filter([
            $this->surname,
            $this->name,
            $this->patronymic,
        ])));

        return $fullName !== '' ? $fullName : (string) $this->name;
    }

    public function getDashboardRouteAttribute(): string
    {
        return match ($this->role_code) {
            'waiter' => '/waiter.html',
            'cook' => '/cook.html',
            default => '/main.html',
        };
    }
}
