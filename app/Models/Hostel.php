<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hostel extends Model
{
    use HasStringId, SoftDeletes;

    protected $fillable = ['id', 'name', 'gender', 'warden_id', 'default_fee_per_term', 'description', 'status'];

    protected function casts(): array
    {
        return ['default_fee_per_term' => 'decimal:2'];
    }

    public function warden(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warden_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class)->orderBy('name');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(RoomReservation::class);
    }

    public function totalCapacity(): int
    {
        return $this->rooms()->sum('capacity');
    }

    public function totalOccupied(): int
    {
        return RoomAllocation::query()
            ->whereIn('room_id', $this->rooms()->pluck('id'))
            ->where('status', 'active')
            ->count();
    }
}
