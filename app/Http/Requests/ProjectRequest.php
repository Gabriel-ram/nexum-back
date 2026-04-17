<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title'       => $this->title       ? strip_tags($this->title)       : null,
            'description' => $this->description ? strip_tags($this->description) : null,
            'project_url' => $this->project_url ? strip_tags($this->project_url) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_url' => ['nullable', 'url', 'max:500'],
            'skill_ids'   => ['nullable', 'array', 'max:30'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ];
    }
}
