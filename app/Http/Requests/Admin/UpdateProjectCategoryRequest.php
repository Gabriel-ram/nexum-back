<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El middleware role:admin ya protege la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name ? strip_tags(trim($this->name)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('project_categories', 'name')->ignore($this->route('category')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A project category with this name already exists.',
        ];
    }
}
