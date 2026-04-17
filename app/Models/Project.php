<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'portfolio_id',
        'category_id',
        'title',
        'description',
        'project_url',
        'archived',
    ];

    protected $casts = [
        'archived' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project')
            ->logOnly(['title', 'description', 'project_url', 'category_id', 'archived'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'category_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'project_skills');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->orderBy('order')->orderBy('id');
    }
}
