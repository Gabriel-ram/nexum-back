<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'skill_id'   => $this->skill_id,
            'name'       => $this->skill->name,
            'type'       => $this->skill->type,
            'category'   => $this->skill->category,
            'level'      => $this->level,
            'is_active'  => $this->is_active,
            'status'     => 'approved',
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
