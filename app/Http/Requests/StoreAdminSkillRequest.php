<?php

namespace App\Http\Requests;

use App\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El middleware role:admin ya protege la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'     => $this->name     ? strip_tags(trim($this->name))     : null,
            'category' => $this->category ? strip_tags(trim($this->category)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                // Unicidad dentro de la misma categoría (mismo type implícito)
                Rule::unique('skills', 'name')->where(function ($query) {
                    $type = Skill::where('category', $this->category)->value('type');
                    return $query->where('type', $type);
                }),
            ],
            // Solo se permiten categorías que ya existen: no se pueden crear nuevas
            'category' => [
                'required',
                'string',
                Rule::in(Skill::distinct()->pluck('category')->all()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category.in'     => 'The selected category does not exist. You cannot create new categories.',
            'name.unique'     => 'A skill with this name already exists in the selected category.',
        ];
    }
}
