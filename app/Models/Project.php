<?php

namespace App\Models;

use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasTenancyScope; // Strict Tenancy (Default)

    protected $guarded = [];

    // --- PERBAIKAN DI SINI (TYPE CASTING) ---
    // Ini mengubah string database "2023-01-01" menjadi Carbon Object
    // agar kita bisa pakai fungsi ->copy(), ->addWeeks(), ->format()
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    // ----------------------------------------

    // --- RELASI YANG HILANG (FIX UTAMA) ---
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
    // --------------------------------------

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function wbs(): HasMany
    {
        return $this->hasMany(Wbs::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }
    
    // Relasi tambahan untuk Cash Flow & Progress (Persiapan Phase selanjutnya)
    public function weeklyRealizations(): HasMany
    {
        return $this->hasMany(WeeklyRealization::class);
    }

    // --- TAMBAHKAN INI ---
    public function projectSchedules(): HasMany
    {
        // Mengambil semua jadwal milik proyek ini
        return $this->hasMany(ProjectSchedule::class);
    }
    // ---------------------

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class);
    }

    public function rabItems(): HasManyThrough
    {
        return $this->hasManyThrough(RabItem::class, Wbs::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Relasi ke Site Managers (Satu Project bisa punya Banyak Site Manager)
    public function siteManagers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_site_managers', 'project_id', 'user_id');
    }

    public function termins(): HasMany
    {
        return $this->hasMany(ProjectTermin::class);
    }

    
}