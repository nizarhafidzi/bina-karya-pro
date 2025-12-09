<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'owner_id'];

    // --- RELASI YANG HILANG (PENYEBAB ERROR) ---
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Relasi ke Anggota Tim
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    
    // Relasi ke Subscription
    public function subscription(): HasOne
    {
        return $this->hasOne(TeamSubscription::class)->latestOfMany();
    }

    // Relasi ke Project (Persiapan Phase 2)
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}