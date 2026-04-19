<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El middleware role:admin ya protege la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'        => $this->name        ? strip_tags(trim($this->name))        : null,
            'description' => $this->description ? strip_tags($this->description)       : $this->description,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100', 'unique:project_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A project category with this name already exists.',
        ];
    }
}
