<?php

namespace App\Http\Resources;

use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $late = app(MemberService::class)->lateInfo($this->resource);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'last_name' => $this->last_name,
            'first_name' => $this->first_name,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'qr_token' => $this->qr_token,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'registration_source' => $this->registration_source->value,
            'registration_source_label' => $this->registration_source->label(),
            'validated_at' => $this->validated_at?->toIso8601String(),
            'validated_by' => $this->whenLoaded('validator', fn () => $this->validator ? ['id' => $this->validator->id, 'name' => $this->validator->name] : null),
            'joined_at' => $this->joined_at->toDateString(),
            'notes' => $this->notes,
            'app_access' => $this->app_password !== null,
            'app_access_granted_at' => $this->app_access_granted_at?->toIso8601String(),
            'app_last_login_at' => $this->app_last_login_at?->toIso8601String(),
            'current_subscription' => $this->whenLoaded('currentSubscription', fn () => $this->currentSubscription
                ? new SubscriptionResource($this->currentSubscription)
                : null),
            'last_attendance_at' => $this->whenLoaded('lastAttendance', fn () => $this->lastAttendance?->checked_in_at->toIso8601String()),
            'is_late' => $late['is_late'],
            'days_late' => $late['days_late'],
            'amount_due' => $late['amount_due'],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
