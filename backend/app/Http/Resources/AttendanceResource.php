<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'code' => $this->member->code,
                'full_name' => $this->member->full_name,
                'phone' => $this->member->phone,
                'photo_url' => $this->member->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->member->photo_path) : null,
                'status' => $this->member->status->value,
            ]),
            'subscription' => $this->whenLoaded('subscription', fn () => $this->subscription ? [
                'id' => $this->subscription->id,
                'plan' => $this->subscription->plan?->name,
                'end_date' => $this->subscription->end_date->toDateString(),
                'sessions_count' => $this->subscription->plan?->sessions_count,
                'sessions_used' => $this->subscription->sessions_used,
            ] : null),
            'attended_on' => $this->attended_on->toDateString(),
            'checked_in_at' => $this->checked_in_at->toIso8601String(),
            'method' => $this->method->value,
            'forced' => $this->forced,
            'force_reason' => $this->force_reason,
            'recorded_by' => $this->whenLoaded('recorder', fn () => $this->recorder ? ['id' => $this->recorder->id, 'name' => $this->recorder->name] : null),
        ];
    }
}
