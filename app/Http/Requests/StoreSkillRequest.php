<?php

namespace App\Http\Requests;

use App\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skill_id' => ['required', 'integer', 'exists:skills,id'],
            'level'    => ['nullable', 'string', 'in:basico,intermedio,avanzado'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('skill_id')) {
                return;
            }

            $skill = Skill::find($this->skill_id);

            if (! $skill) {
                return;
            }

            if ($skill->type === 'tecnica' && ! $this->level) {
                $validator->errors()->add('level', 'El nivel es obligatorio para habilidades técnicas (basico, intermedio, avanzado).');
            }

            if ($skill->type === 'blanda' && $this->level) {
                $validator->errors()->add('level', 'Las habilidades blandas no tienen nivel.');
            }
        });
    }
}
