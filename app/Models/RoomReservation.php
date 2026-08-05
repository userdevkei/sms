<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomReservation extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'user_id', 'hostel_id', 'preferred_room_id', 'academic_year', 'term',
        'status', 'notes', 'requested_by', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function preferredRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'preferred_room_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function allocation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RoomAllocation::class, 'reservation_id');
    }
}
