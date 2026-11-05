<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulaire du site web : champs réduits, pas de photo, pas de notes internes.
 */
class StorePublicRegistrationRequest extends FormRequest
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
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Ce numéro est déjà enregistré, passez à l\'accueil du club.',
            'website.prohibited' => 'Requête invalide.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/\s+/', '', (string) $this->phone)]);
        }
    }
}
