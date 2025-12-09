<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasTenancyScope;

    protected $guarded = [];

    // --- FUNCTION INI WAJIB ADA ---
    // Function ini memberitahu Trait HasTenancyScope untuk:
    // "Jangan hanya ambil punya saya, ambil juga data punya Global (NULL)"
    public static function isHybrid(): bool 
    { 
        return true; 
    }

    public function prices() 
    { 
        return $this->hasMany(ResourcePrice::class); 
    }
}