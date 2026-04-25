<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class WorkExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'position'    => $this->position    ? strip_tags(trim($this->position))    : null,
            'company'     => $this->company     ? strip_tags(trim($this->company))     : null,
            'location'    => $this->location    ? strip_tags(trim($this->location))    : null,
            'description' => $this->description ? strip_tags(trim($this->description)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'position'         => ['required', 'string', 'max:255'],
            'company'          => ['required', 'string', 'max:255'],
            'location'         => ['nullable', 'string', 'max:255'],
            'employment_type'  => ['required', 'in:remote,on_site,hybrid,freelance'],
            'start_date'       => [
                'required',
                'date_format:Y-m',
                function ($attribute, $value, $fail) {
                    $date = Carbon::createFromFormat('Y-m', $value)->startOfMonth();
                    if ($date->isFuture()) {
                        $fail('The start date cannot be in the future.');
                    }
                },
            ],
            'end_date'         => [
                'nullable',
                'date_format:Y-m',
                function ($attribute, $value, $fail) {
                    if (! $value || ! $this->start_date) {
                        return;
                    }
                    $start = Carbon::createFromFormat('Y-m', $this->start_date)->startOfMonth();
                    $end   = Carbon::createFromFormat('Y-m', $value)->startOfMonth();
                    if ($end->lt($start)) {
                        $fail('The end date must be greater than or equal to the start date.');
                    }
                },
            ],
            'description'      => ['nullable', 'string', 'max:5000'],
            'verification_url' => ['nullable', 'url', 'max:500'],
            'skill_ids'        => ['nullable', 'array', 'max:30'],
            'skill_ids.*'      => ['integer', 'exists:skills,id'],
        ];
    }
}
