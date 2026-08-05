<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'hostel_id', 'name', 'capacity', 'fee_per_term', 'status'];

    protected function casts(): array
    {
        return ['fee_per_term' => 'decimal:2'];
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RoomAllocation::class);
    }

    public function activeAllocations(): HasMany
    {
        return $this->hasMany(RoomAllocation::class)->where('status', 'active');
    }

    public function occupiedBeds(): int
    {
        return $this->activeAllocations()->count();
    }

    public function availableBeds(): int
    {
        return max(0, $this->capacity - $this->occupiedBeds());
    }

    public function hasSpace(): bool
    {
        return $this->availableBeds() > 0;
    }

    public function effectiveFeePerTerm(): ?string
    {
        return $this->fee_per_term ?? $this->hostel?->default_fee_per_term;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->hostel?->name} \u{2013} {$this->name}");
    }
}
