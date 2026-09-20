<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    use HasStringId, LogsActivity;

    protected $fillable = ['id', 'route_id', 'name', 'sequence', 'landmark_description', 'fare'];

    protected function casts(): array
    {
        return ['fare' => 'decimal:2'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }
}
