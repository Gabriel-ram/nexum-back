<?php

namespace App\Models;

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
        'description',
        'issuing_entity',
        'issue_date',
        'expiration_date',
        'image_url',
        'cloudinary_public_id',
    ];

    protected $casts = [
        'issue_date'      => 'date',
        'expiration_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('certification')
            ->logOnly(['name', 'description', 'issuing_entity', 'issue_date', 'expiration_date', 'image_url'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
