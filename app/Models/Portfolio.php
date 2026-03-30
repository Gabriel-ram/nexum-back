<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Portfolio extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'profession',
        'biography',
        'phone',
        'location',
        'profile_image_path',
        'banner_image_path',
        'design_pattern',
        'global_privacy',
        'views_count',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('portfolio')
            ->logOnly(['profession', 'biography', 'phone', 'location', 'global_privacy', 'design_pattern'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
