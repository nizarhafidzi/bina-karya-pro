<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSchedule extends Model
{
    use HasTenancyScope;
    protected $guarded = [];

    public function rabItem(): BelongsTo
    {
        return $this->belongsTo(RabItem::class);
    }
}