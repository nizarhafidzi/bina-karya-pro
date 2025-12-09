<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wbs extends Model
{
    use HasFactory;

    protected $table = 'wbs'; // Penting karena nama tabel tidak standar (plural s)
    protected $guarded = [];

    // --- RELASI YANG HILANG (FIX UTAMA) ---
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    // --------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(RabItem::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Wbs::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Wbs::class, 'parent_id');
    }
}