<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTermin extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'target_progress' => 'float',
        'percentage_value' => 'float',
        'nominal_value' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}