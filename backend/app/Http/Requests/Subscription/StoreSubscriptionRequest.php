<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id,deleted_at,NULL'],
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function attributes(): array
    {
        return [
            'member_id' => 'adhérent',
            'subscription_plan_id' => 'formule',
            'start_date' => 'date de début',
        ];
    }
}
