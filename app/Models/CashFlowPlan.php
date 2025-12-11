<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CashFlowPlan
 * * Model ini khusus menangani RENCANA (Budgeting).
 * Menggunakan Global Scope untuk memfilter hanya data berstatus 'planned'.
 */
class CashFlowPlan extends Model
{
    use HasFactory;
    use HasTenancyScope; // Wajib: Agar Tenant A tidak melihat data Tenant B

    protected $table = 'cash_flows'; // Sharing tabel dengan Actual
    protected $guarded = [];

    protected static function booted(): void
    {
        // 1. Filter Otomatis: Hanya ambil yang statusnya 'planned'
        static::addGlobalScope('planned', function (Builder $builder) {
            $builder->where('status', 'planned');
        });

        // 2. Isi Otomatis: Saat create, set status jadi 'planned'
        static::creating(function ($model) {
            $model->status = 'planned';
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