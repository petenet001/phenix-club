<?php

namespace App\Http\Requests\Reminder;

use App\Enums\ReminderChannel;
use App\Enums\ReminderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id,deleted_at,NULL'],
            'type' => ['required', Rule::in(ReminderType::values())],
            'channel' => ['required', Rule::in(ReminderChannel::values())],
            'message' => ['nullable', 'string', 'max:1000'],
            'override' => ['sometimes', 'boolean'],
        ];
    }
}
