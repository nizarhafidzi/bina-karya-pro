<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RabItemMaterial extends Model
{
    protected $guarded = [];
    // Ini adalah Snapshot, tidak ada relasi langsung ke Resource ID untuk menjaga integritas data historis
}