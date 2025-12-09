<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'reference_no',
        'amount',
        'status', // PAID, UNPAID, FAILED
        'payment_method',
        'payment_gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_gateway_response' => 'array', // Simpan JSON response dari Tripay/Midtrans
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}