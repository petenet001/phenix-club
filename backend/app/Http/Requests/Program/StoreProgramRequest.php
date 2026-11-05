<?php

namespace App\Http\Requests\Program;

use App\Enums\TrainingProgramKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(TrainingProgramKind::values())],
            'title' => ['required', 'string', 'max:150'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'content' => ['required', 'array'],
            'content.goal' => ['nullable', 'string', 'max:255'],
            'content.days' => ['nullable', 'array', 'max:7'],
            'content.days.*' => ['string', Rule::in(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'])],
            'content.sessions' => ['nullable', 'array'],
            'content.sessions.*.day' => ['required_with:content.sessions', 'string', 'max:20'],
            'content.sessions.*.exercises' => ['nullable', 'array'],
            'content.sessions.*.exercises.*.name' => ['required', 'string', 'max:100'],
            'content.sessions.*.exercises.*.sets' => ['nullable', 'integer', 'min:1', 'max:20'],
            'content.sessions.*.exercises.*.reps' => ['nullable', 'string', 'max:30'],
            'content.sessions.*.exercises.*.notes' => ['nullable', 'string', 'max:255'],
            'content.meals' => ['nullable', 'array'],
            'content.meals.*.time' => ['required_with:content.meals', 'string', 'max:50'],
            'content.meals.*.items' => ['nullable', 'array'],
            'content.meals.*.items.*' => ['string', 'max:150'],
            'content.advice' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'impose' => ['sometimes', 'boolean'],
        ];
    }
}
