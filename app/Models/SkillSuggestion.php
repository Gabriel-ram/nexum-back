<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillSuggestion extends Model
{
    protected $fillable = [
        'portfolio_id',
        'user_id',
        'type',
        'category',
        'name',
        'level',
        'justification',
        'status',
        'reviewed_by',
        'reviewed_at',
        'skill_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
