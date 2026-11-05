<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'duration_days' => $this->duration_days,
            'price' => $this->price,
            'sessions_count' => $this->sessions_count,
            'registration_fee' => $this->registration_fee,
            'effective_registration_fee' => $this->effectiveRegistrationFee(),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'subscriptions_count' => $this->whenCounted('subscriptions'),
        ];
    }
}
