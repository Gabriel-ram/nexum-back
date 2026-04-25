<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $portfolioSkill = $this->route('portfolioSkill');
        $isBlanda       = $portfolioSkill && $portfolioSkill->skill->type === 'blanda';

        return [
            'level' => ['required', 'string', $isBlanda
                ? 'in:en_formacion,desarrollada,fortalecida'
                : 'in:basico,intermedio,avanzado',
            ],
        ];
    }
}
