<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    // Relasi: Satu Region punya banyak Project
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // Relasi: Satu Region punya banyak standar harga (Resource Price)
    public function resourcePrices(): HasMany
    {
        return $this->hasMany(ResourcePrice::class);
    }
}