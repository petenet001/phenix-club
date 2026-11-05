<?php

namespace App\Http\Requests\Expense;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'label' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::in(PaymentMethod::values())],
            'payee' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:100'],
            'spent_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return ['expense_category_id' => 'catégorie', 'label' => 'libellé', 'amount' => 'montant', 'spent_on' => 'date'];
    }
}
