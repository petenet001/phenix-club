<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checkin_tolerance_days' => ['sometimes', 'integer', 'min:0', 'max:60'],
            'late_payment_days' => ['sometimes', 'integer', 'min:0', 'max:90'],
            'inactivity_days' => ['sometimes', 'integer', 'min:1', 'max:180'],
            'expiry_soon_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'registration_fee' => ['sometimes', 'integer', 'min:0'],
            'receipt_prefix' => ['sometimes', 'string', 'alpha', 'max:5'],
            'club_name' => ['sometimes', 'string', 'max:100'],
            'club_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'club_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'online_registration_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
