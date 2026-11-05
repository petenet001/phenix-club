<?php

namespace App\Http\Resources\MemberApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attended_on' => $this->attended_on->toDateString(),
            'checked_in_at' => $this->checked_in_at->toIso8601String(),
            'method' => $this->method->value,
            'plan' => $this->subscription?->plan?->name,
        ];
    }
}
