<?php

namespace App\Http\Requests\Member;

use App\Enums\MemberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(MemberStatus::values())],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
