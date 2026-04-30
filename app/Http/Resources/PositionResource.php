<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'    => $this->id,
            'item'  => $this->menu->name,  
            'price' => $this->menu->price,
            'count' => $this->count,
        ];
    }
}
