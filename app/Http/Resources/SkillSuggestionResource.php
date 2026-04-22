<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'suggestion_id' => $this->id,
            'name'          => $this->name,
            'type'          => $this->type,
            'category'      => $this->category,
            'level'         => $this->level,
            'is_active'     => true,
            'status'        => $this->status,
            'justification' => $this->justification,
            'created_at'    => $this->created_at->toISOString(),
            'updated_at'    => $this->updated_at->toISOString(),
        ];
    }
}
