<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'           => $this->name           ? strip_tags($this->name)           : $this->name,
            'description'    => $this->description    ? strip_tags($this->description)    : $this->description,
            'issuing_entity' => $this->issuing_entity ? strip_tags($this->issuing_entity) : $this->issuing_entity,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:255'],
            'description'     => ['sometimes', 'nullable', 'string', 'max:1000'],
            'issuing_entity'  => ['sometimes', 'string', 'max:255'],
            'issue_date'      => ['sometimes', 'date_format:m/Y'],
            'expiration_date' => [
                'sometimes',
                'nullable',
                'date_format:m/Y',
                function (string $attribute, mixed $value, Closure $fail) {
                    /** @var \App\Models\Certification $certification */
                    $certification = $this->route('certification');

                    $rawIssue = $this->input('issue_date')
                        ?? $certification->issue_date->format('m/Y');

                    try {
                        $expiry = Carbon::createFromFormat('m/Y', $value)->startOfMonth();
                        $issue  = Carbon::createFromFormat('m/Y', $rawIssue)->startOfMonth();

                        if (! $expiry->greaterThan($issue)) {
                            $fail('The expiration date must be after the issue date.');
                        }
                    } catch (\Exception) {
                        // issue_date failed its own validation — skip comparison
                    }
                },
            ],
        ];
    }
}
