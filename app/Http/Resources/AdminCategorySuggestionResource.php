<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCategorySuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'justification' => $this->justification,
            'status'        => $this->status,
            'reviewed_at'   => $this->reviewed_at?->toISOString(),
            'reviewed_by'   => $this->reviewer ? [
                'id'   => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null,
            'category_id'   => $this->category_id,
            'project'       => [
                'id'    => $this->project->id,
                'title' => $this->project->title,
            ],
            'user'          => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
