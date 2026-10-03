<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffPayee extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'basic_salary' => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(StaffPayItem::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** Employed at any point in the given month? */
    public function scopeEmployedIn($q, \Carbon\Carbon $monthStart)
    {
        $end = $monthStart->copy()->endOfMonth();

        return $q->where(fn ($w) => $w->whereNull('start_date')->orWhere('start_date', '<=', $end))
            ->where(fn ($w) => $w->whereNull('end_date')->orWhere('end_date', '>=', $monthStart));
    }
}
