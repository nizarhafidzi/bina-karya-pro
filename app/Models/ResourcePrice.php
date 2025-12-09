<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePrice extends Model
{
    use HasTenancyScope; // Gunakan trait ini agar otomatis ter-scope

    protected $guarded = [];

    // Override: Data ini Hybrid (Bisa dari Pusat, Bisa Custom Tenant)
    public static function isHybrid(): bool 
    { 
        return true; 
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}