<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id,deleted_at,NULL'],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
            'type' => ['nullable', Rule::in(PaymentType::values())],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::in(PaymentMethod::values())],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
            'period_start' => ['nullable', 'date_format:Y-m-d'],
            'period_end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['member_id' => 'adhérent', 'amount' => 'montant', 'method' => 'mode de paiement', 'reference' => 'référence'];
    }
}
