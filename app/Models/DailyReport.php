<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DailyReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke Project
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Relasi ke User (Site Manager yang input)
    public function siteManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_manager_id');
    }

    // Relasi Many-to-Many: Item apa saja yang dikerjakan hari ini
    public function workItems(): BelongsToMany
    {
        return $this->belongsToMany(RabItem::class, 'daily_report_items', 'daily_report_id', 'rab_item_id');
    }
}