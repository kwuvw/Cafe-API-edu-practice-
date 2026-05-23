<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkShiftResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        $isActive = (bool) $this->active;

        return [
            'id'     => $this->id,
            'start'  => $this->start?->format('Y-m-d H:i:s'),
            'end'    => $this->end?->format('Y-m-d H:i:s'),
            'active' => $isActive,
            'status' => $isActive ? 'active' : 'closed',
        ];
    }
}
