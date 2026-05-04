<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'table'            => $this->table->name ?? 'Не указан', 
            'number_of_person' => $this->number_of_person,
            'status'           => $this->status_order->name ?? 'Принят',
            'created_at'       => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
