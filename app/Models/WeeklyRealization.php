<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyRealization extends Model
{
    use HasTenancyScope;
    protected $guarded = [];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function itemRealizations(): HasMany
    {
        return $this->hasMany(ItemRealization::class);
    }
}