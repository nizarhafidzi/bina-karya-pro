<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasTenancyScope; // Strict Tenancy (Default)

    protected $guarded = [];

    // --- RELASI YANG HILANG (FIX UTAMA) ---
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
    // --------------------------------------

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function wbs(): HasMany
    {
        return $this->hasMany(Wbs::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }
    
    // Relasi tambahan untuk Cash Flow & Progress (Persiapan Phase selanjutnya)
    public function weeklyRealizations(): HasMany
    {
        return $this->hasMany(WeeklyRealization::class);
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class);
    }

    public function rabItems(): HasManyThrough
    {
        return $this->hasManyThrough(RabItem::class, Wbs::class);
    }
}