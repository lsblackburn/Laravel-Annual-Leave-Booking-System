<?php

namespace App\Http\Requests;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->request->has('employment_start_date')) {
            return;
        }

        $this->merge([
            'employment_start_date' => $this->normalizeEmploymentStartDate($this->input('employment_start_date')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user') ?? $this->user();
        $employmentStartDateRules = $this->canUpdateEmploymentStartDate()
            ? ['sometimes', 'nullable', 'date', 'before:today', 'regex:/^\d{4}-\d{2}-\d{2}(?:$|[T\s])/']
            : ['prohibited'];

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user?->id),
            ],

            'employment_start_date' => $employmentStartDateRules,
        ];
    }

    private function canUpdateEmploymentStartDate(): bool
    {
        return $this->route('user') instanceof User || $this->user()?->isAdmin();
    }

    private function normalizeEmploymentStartDate(mixed $value): mixed
    {
        if (! is_string($value) || ! preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
            return $value;
        }

        return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
    }
}
