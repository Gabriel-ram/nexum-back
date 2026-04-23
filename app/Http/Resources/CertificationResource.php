<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'issuing_entity'  => $this->issuing_entity,
            'issue_date'      => $this->issue_date->format('m/Y'),
            'expiration_date' => $this->expiration_date?->format('m/Y'),
            'image_url'       => $this->image_url,
            'is_active'       => $this->is_active,
            'created_at'      => $this->created_at->toISOString(),
            'updated_at'      => $this->updated_at->toISOString(),
        ];
    }
}
