<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'code' => $this->member->code,
                'full_name' => $this->member->full_name,
                'phone' => $this->member->phone,
            ]),
            'subscription' => $this->whenLoaded('subscription', fn () => $this->subscription ? [
                'id' => $this->subscription->id,
                'plan' => $this->subscription->plan?->name,
                'start_date' => $this->subscription->start_date->toDateString(),
                'end_date' => $this->subscription->end_date->toDateString(),
                'balance_due' => $this->subscription->balance_due,
            ] : null),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'amount' => $this->amount,
            'method' => $this->method->value,
            'method_label' => $this->method->label(),
            'reference' => $this->reference,
            'paid_at' => $this->paid_at->toIso8601String(),
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'received_by' => $this->whenLoaded('receiver', fn () => $this->receiver ? ['id' => $this->receiver->id, 'name' => $this->receiver->name] : null),
            'notes' => $this->notes,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'receipt_url' => url("/api/payments/{$this->id}/receipt"),
        ];
    }
}
