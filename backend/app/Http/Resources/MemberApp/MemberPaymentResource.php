<?php

namespace App\Http\Resources\MemberApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Paiement vu par l'adhérent (sans données internes du personnel). */
class MemberPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'paid_at' => $this->paid_at->toIso8601String(),
            'amount' => $this->amount,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'method_label' => $this->method->label(),
            'plan' => $this->subscription?->plan?->name,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'cancelled' => $this->cancelled_at !== null,
        ];
    }
}
