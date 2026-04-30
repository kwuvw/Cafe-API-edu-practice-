<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkShiftResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id'     => $this->id,
            'start'  => $this->start,
            'end'    => $this->end,
            'active' => (bool)$this->active,
        ];
    }
}
