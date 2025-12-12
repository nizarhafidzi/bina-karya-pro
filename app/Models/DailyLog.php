<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_manager_id');
    }
}