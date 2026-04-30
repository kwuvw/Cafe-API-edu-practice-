<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => "{$this->surname} {$this->name} {$this->patronymic}",
            'login' => $this->login,
            'role' => $this->role->name ?? 'Сотрудник', // Предполагая связь с таблицей ролей
        ];
    }
}
