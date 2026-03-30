<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'profession'   => $this->profession   ? strip_tags($this->profession)   : null,
            'bio'          => $this->bio          ? strip_tags($this->bio)          : null,
            'linkedin_url' => $this->linkedin_url ? strip_tags($this->linkedin_url) : null,
            'github_url'   => $this->github_url   ? strip_tags($this->github_url)   : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'profession'   => ['nullable', 'string', 'max:255'],
            'bio'          => ['nullable', 'string', 'max:1000'],
            'linkedin_url' => [
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    $host = parse_url($value, PHP_URL_HOST) ?? '';
                    if (! str_contains($host, 'linkedin.com')) {
                        $fail('The LinkedIn URL must be a valid linkedin.com URL.');
                    }
                },
            ],
            'github_url' => [
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    $host = parse_url($value, PHP_URL_HOST) ?? '';
                    if (! str_contains($host, 'github.com')) {
                        $fail('The GitHub URL must be a valid github.com URL.');
                    }
                },
            ],
        ];
    }
}
