<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Encaissement de plusieurs lignes en une fois (frais d'inscription + abonnement).
 */
class StorePaymentBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id,deleted_at,NULL'],
            'method' => ['required', Rule::in(PaymentMethod::values())],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:5'],
            'lines.*.type' => ['required', Rule::in(PaymentType::values())],
            'lines.*.amount' => ['required', 'integer', 'min:1'],
            'lines.*.subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
        ];
    }
}
