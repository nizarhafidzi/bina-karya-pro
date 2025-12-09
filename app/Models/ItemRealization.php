<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRealization extends Model
{
    protected $guarded = [];

    public function weeklyRealization(): BelongsTo
    {
        return $this->belongsTo(WeeklyRealization::class);
    }

    public function rabItem(): BelongsTo
    {
        return $this->belongsTo(RabItem::class);
    }
}