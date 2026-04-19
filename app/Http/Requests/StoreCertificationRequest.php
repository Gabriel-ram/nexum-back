<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificationRequest extends FormRequest
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
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'issuing_entity'  => ['required', 'string', 'max:255'],
            'issue_date'      => ['required', 'date_format:m/Y'],
            'expiration_date' => [
                'nullable',
                'date_format:m/Y',
                function (string $attribute, mixed $value, Closure $fail) {
                    $issueDate = $this->input('issue_date');

                    if (! $issueDate) {
                        return;
                    }

                    try {
                        $expiry = Carbon::createFromFormat('m/Y', $value)->startOfMonth();
                        $issue  = Carbon::createFromFormat('m/Y', $issueDate)->startOfMonth();

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
