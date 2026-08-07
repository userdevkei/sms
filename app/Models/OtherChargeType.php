<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtherChargeType extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'name', 'description', 'status'];

    public function otherCharges()
    {
        return $this->hasMany(OtherCharge::class);
    }
}
