<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkExperienceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'position'         => $this->position,
            'company'          => $this->company,
            'location'         => $this->location,
            'employment_type'  => $this->employment_type,
            'start_date'       => $this->start_date->format('Y-m'),
            'end_date'         => $this->end_date?->format('Y-m'),
            'description'      => $this->description,
            'verification_url' => $this->verification_url,
            'skills'           => $this->whenLoaded('skills', fn () => $this->skills->map(fn ($skill) => [
                'id'       => $skill->id,
                'name'     => $skill->name,
                'type'     => $skill->type,
                'category' => $skill->category,
            ])),
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),
        ];
    }
}
