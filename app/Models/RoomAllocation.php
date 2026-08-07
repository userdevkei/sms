<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomAllocation extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'user_id', 'room_id', 'reservation_id', 'academic_year', 'term',
        'status', 'allocated_on', 'vacated_on', 'allocated_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['allocated_on' => 'date', 'vacated_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RoomReservation::class, 'reservation_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
