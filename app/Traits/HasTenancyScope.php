<?php

namespace App\Traits;

use App\Models\Team;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTenancyScope
{
    /**
     * Boot the trait.
     */
    protected static function bootHasTenancyScope(): void
    {
        // 1. Auto-fill team_id saat create data
        static::creating(function ($model) {
            if (auth()->check() && ! $model->team_id) {
                $tenant = Filament::getTenant();
                if ($tenant) {
                    $model->team_id = $tenant->id;
                }
            }
        });

        // 2. Terapkan Global Scope untuk filter data
        static::addGlobalScope('tenancy_scope', function (Builder $builder) {
            if (auth()->check()) {
                $tenant = Filament::getTenant();
                
                // Jika sedang di panel Admin/Global, mungkin kita ingin melihat semua (bypass)
                // Tapi untuk keamanan, di panel App:
                if ($tenant) {
                    if (static::isHybrid()) {
                        // HYBRID: Ambil data milik Tim Sendiri ATAU Global (NULL)
                        $builder->where(function($query) use ($tenant) {
                            $query->where('team_id', $tenant->id)
                                  ->orWhereNull('team_id');
                        });
                    } else {
                        // STRICT: Hanya ambil data milik Tim Sendiri
                        $builder->where('team_id', $tenant->id);
                    }
                }
            }
        });
    }

    // Default: Strict (Non-Hybrid)
    // Override function ini di Model Resource/AHS menjadi return true
    public static function isHybrid(): bool
    {
        return false;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}