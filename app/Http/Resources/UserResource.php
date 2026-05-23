<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $attributes = $this->resource->getAttributes();
        $hasShiftAssignmentFlag = array_key_exists('is_working', $attributes);
        $isWorking = (bool) $this->resource->getAttribute('is_working');

        return [
            'id' => $this->id,
            'name' => $this->display_name,
            'full_name' => $this->display_name,
            'login' => $this->login,
            'role' => $this->role_code,
            'role_id' => (int) $this->role_id,
            'role_name' => $this->role?->name,
            'status' => $hasShiftAssignmentFlag
                ? ($isWorking ? 'working' : 'inactive')
                : $this->resolveStoredStatus(),
        ];
    }

    private function resolveStoredStatus(): string
    {
        $storedStatus = strtolower(trim((string) $this->status));

        if ($storedStatus === User::STATUS_FIRED) {
            return 'inactive';
        }

        return 'inactive';
    }
}
