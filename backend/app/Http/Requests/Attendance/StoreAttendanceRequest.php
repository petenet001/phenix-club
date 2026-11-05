<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required_without:qr_token', 'nullable', 'integer', 'exists:members,id,deleted_at,NULL'],
            'qr_token' => ['required_without:member_id', 'nullable', 'uuid'],
            'method' => ['nullable', Rule::in(AttendanceMethod::values())],
            'force' => ['sometimes', 'boolean'],
            'force_reason' => ['required_if:force,true', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required_without' => 'Indiquez un adhérent ou un QR code.',
            'force_reason.required_if' => 'Un motif est obligatoire pour forcer le pointage.',
        ];
    }
}
