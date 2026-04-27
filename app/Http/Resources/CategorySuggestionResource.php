<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorySuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'project_id'    => $this->project_id,
            'name'          => $this->name,
            'justification' => $this->justification,
            'status'        => $this->status,
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
