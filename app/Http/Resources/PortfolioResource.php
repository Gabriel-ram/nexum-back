<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user'         => [
                'id'         => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name'  => $this->user->last_name,
                'email'      => $this->user->email,
            ],
            'profession'   => $this->profession,
            'biography'    => $this->biography,
            'phone'        => $this->phone,
            'location'     => $this->location,
            'avatar_url'   => $this->avatar_path ? cloudinary()->image($this->avatar_path)->toUrl() : null,
            'linkedin_url' => $this->linkedin_url,
            'github_url'   => $this->github_url,
            'design_pattern'  => $this->design_pattern,
            'global_privacy'  => $this->global_privacy,
            'views_count'     => $this->views_count,
            'created_at'   => $this->created_at->toISOString(),
            'updated_at'   => $this->updated_at->toISOString(),
        ];
    }
}
