<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'url',
        'cloudinary_public_id',
        'original_name',
        'order',
        'size',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
