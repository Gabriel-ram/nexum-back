<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Portfolio extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'profession',
        'biography',
        'phone',
        'location',
        'avatar_path',
        'linkedin_url',
        'github_url',
        'design_pattern',
        'global_privacy',
        'views_count',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('portfolio')
            ->logOnly(['profession', 'biography', 'phone', 'location', 'global_privacy', 'design_pattern', 'linkedin_url', 'github_url', 'avatar_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
