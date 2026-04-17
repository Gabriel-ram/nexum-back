<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'project_url' => $this->project_url,
            'archived'    => $this->archived,
            'category'    => $this->whenLoaded('category', fn () => $this->category
                ? ['id' => $this->category->id, 'name' => $this->category->name]
                : null
            ),
            'skills'      => $this->whenLoaded('skills', fn () => $this->skills->map(fn ($skill) => [
                'id'       => $skill->id,
                'name'     => $skill->name,
                'type'     => $skill->type,
                'category' => $skill->category,
            ])),
            'files'       => ProjectFileResource::collection($this->whenLoaded('files')),
            'created_at'  => $this->created_at->toISOString(),
            'updated_at'  => $this->updated_at->toISOString(),
        ];
    }
}
