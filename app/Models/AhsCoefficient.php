<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AhsCoefficient extends Model
{
    protected $guarded = [];
    // Tidak butuh HasTenancyScope karena dependent pada AhsMaster

    public function resource() {
        return $this->belongsTo(Resource::class);
    }
}