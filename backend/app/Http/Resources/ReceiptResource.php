<?php

namespace App\Http\Resources;

use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $receipts = app(ReceiptService::class);

        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'paid_at' => $this->paid_at->toIso8601String(),
            'member' => [
                'full_name' => $this->member->full_name,
                'code' => $this->member->code,
                'phone' => $this->member->phone,
            ],
            'type_label' => $this->type->label(),
            'plan' => $this->subscription?->plan?->name,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'amount' => $this->amount,
            'amount_in_words' => $receipts->amountInWords($this->amount),
            'method_label' => $this->method->label(),
            'reference' => $this->reference,
            'balance_due' => $this->subscription?->balance_due ?? 0,
            'received_by' => $this->receiver?->name,
            'notes' => $this->notes,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'club' => $receipts->club() + ['logo_url' => asset('images/logo-phenix.png')],
        ];
    }
}
