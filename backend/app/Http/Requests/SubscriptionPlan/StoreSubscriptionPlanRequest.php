<?php

namespace App\Http\Requests\SubscriptionPlan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('subscription_plans', 'name')->ignore($this->route('subscription_plan'))],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3660'],
            'price' => ['required', 'integer', 'min:0'],
            'sessions_count' => ['nullable', 'integer', 'min:1'],
            'registration_fee' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'duration_days' => 'durée (jours)',
            'price' => 'prix',
            'sessions_count' => 'nombre de séances',
        ];
    }
}
