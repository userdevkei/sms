<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentRouteStop extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'user_id', 'route_stop_id', 'academic_year', 'term', 'status'];

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function routeStop(): BelongsTo { return $this->belongsTo(RouteStop::class); }
}
