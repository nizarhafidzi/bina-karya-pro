<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyResourcePlan extends Model
{
    use HasFactory, HasTenancyScope;

    protected $guarded = [];

    /**
     * Helper Attribute: Menentukan nilai mana yang dipakai.
     * Jika ada 'adjusted_qty' (manual), gunakan itu.
     * Jika tidak, gunakan 'system_qty' (hitungan otomatis).
     */
    public function getFinalQtyAttribute()
    {
        return $this->adjusted_qty ?? $this->system_qty;
    }

    // --- RELASI ---

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}