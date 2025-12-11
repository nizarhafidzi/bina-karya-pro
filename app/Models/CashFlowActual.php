<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CashFlowActual
 * * Model ini khusus menangani REALISASI (Uang Beneran).
 * Menggunakan Global Scope untuk memfilter hanya data berstatus 'paid'.
 */
class CashFlowActual extends Model
{
    use HasFactory;
    use HasTenancyScope;

    protected $table = 'cash_flows';
    protected $guarded = [];

    protected static function booted(): void
    {
        // Filter Otomatis: Hanya ambil yang statusnya 'paid' (Realisasi)
        static::addGlobalScope('actual', function (Builder $builder) {
            $builder->where('status', 'paid');
        });

        static::creating(function ($model) {
            $model->status = 'paid';
            // FIX: Isi default category jika kosong agar tidak error SQL
            if (empty($model->category)) {
                $model->category = 'general'; 
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}