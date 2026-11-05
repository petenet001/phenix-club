<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->route('user') !== null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(UserRole::values())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
