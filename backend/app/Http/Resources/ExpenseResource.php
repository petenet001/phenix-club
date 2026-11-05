<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
            'label' => $this->label,
            'amount' => $this->amount,
            'method' => $this->method->value,
            'method_label' => $this->method->label(),
            'payee' => $this->payee,
            'reference' => $this->reference,
            'spent_on' => $this->spent_on->toDateString(),
            'receipt_url' => $this->receipt_path ? Storage::disk('public')->url($this->receipt_path) : null,
            'notes' => $this->notes,
            'recorded_by' => $this->whenLoaded('recorder', fn () => $this->recorder ? ['id' => $this->recorder->id, 'name' => $this->recorder->name] : null),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
        ];
    }
}
