<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportRoute extends Model
{
    use HasStringId, SoftDeletes;

    protected $table = 'transport_routes';

    protected $fillable = ['id', 'name', 'code', 'description', 'status'];

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class, 'route_id')->orderBy('sequence');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RouteAssignment::class, 'route_id');
    }

    public function currentAssignment(): ?RouteAssignment
    {
        return $this->assignments()->where('status', 'active')->latest('start_date')->first();
    }
}
