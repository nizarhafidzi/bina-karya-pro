<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'max_projects',
        'max_users',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_monthly' => 'decimal:2',
        'max_projects' => 'integer',
        'max_users' => 'integer',
    ];

    // Relasi: Satu paket bisa dimiliki banyak subscription
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TeamSubscription::class);
    }
}