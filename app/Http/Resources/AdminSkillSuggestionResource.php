<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSkillSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'category'      => $this->category,
            'name'          => $this->name,
            'level'         => $this->level,
            'justification' => $this->justification,
            'status'        => $this->status,
            'reviewed_at'   => $this->reviewed_at?->toISOString(),
            'reviewed_by'   => $this->reviewer ? [
                'id'   => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null,
            'skill_id'      => $this->skill_id,
            'user'          => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
