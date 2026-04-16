<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Certification extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'portfolio_id',
        'name',
        'issuing_entity',
        'issue_date',
        'expiration_date',
        'image_url',
        'cloudinary_public_id',
        'is_active',
    ];

    protected $casts = [
        'issue_date'      => 'date',
        'expiration_date' => 'date',
        'is_active'       => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('certification')
            ->logOnly(['name', 'issuing_entity', 'issue_date', 'expiration_date', 'image_url', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
