<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(['M', 'F'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('members', 'phone')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'joined_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'last_name' => 'nom',
            'first_name' => 'prénom',
            'phone' => 'téléphone',
            'birth_date' => 'date de naissance',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Un adhérent utilise déjà ce numéro de téléphone.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/\s+/', '', (string) $this->phone)]);
        }
    }
}
