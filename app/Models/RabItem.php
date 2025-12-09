<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RabItem extends Model
{
    use HasFactory;
    
    // Gunakan Trait ini jika ingin RabItem terlindungi tenant scope (Optional, tapi Recommended)
    // use HasTenancyScope; 

    protected $guarded = [];

    // --- RELASI YANG HILANG (FIX UTAMA) ---
    public function ahsMaster(): BelongsTo
    {
        return $this->belongsTo(AhsMaster::class, 'ahs_master_id');
    }
    // --------------------------------------

    public function materials(): HasMany
    {
        return $this->hasMany(RabItemMaterial::class);
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(Wbs::class);
    }

    // --- RELASI YANG BARU DITAMBAHKAN (SOLUSI ERROR) ---
    public function schedules(): HasMany
    {
        return $this->hasMany(ProjectSchedule::class);
    }
    // --------------------------------------------------
}