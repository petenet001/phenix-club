<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'duration_days' => $this->plan->duration_days,
            ]),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'price_at_subscription' => $this->price_at_subscription,
            'amount_paid' => $this->amount_paid,
            'balance_due' => $this->balance_due,
            'sessions_count' => $this->whenLoaded('plan', fn () => $this->plan->sessions_count),
            'sessions_used' => $this->sessions_used,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator ? ['id' => $this->creator->id, 'name' => $this->creator->name] : null),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
