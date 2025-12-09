<?php
namespace App\Models;
use App\Traits\HasTenancyScope;
use Illuminate\Database\Eloquent\Model;

class AhsMaster extends Model
{
    use HasTenancyScope;
    protected $guarded = [];
    public static function isHybrid(): bool { return true; }

    public function coefficients() {
        return $this->hasMany(AhsCoefficient::class);
    }
}