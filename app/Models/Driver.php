<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasStringId, SoftDeletes;

    protected $fillable = ['id', 'user_id', 'license_number', 'license_class', 'license_expiry', 'status', 'notes'];

    protected function casts(): array
    {
        return ['license_expiry' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routeAssignments(): HasMany
    {
        return $this->hasMany(RouteAssignment::class);
    }

    // Convenience passthroughs so callers don't need $driver->user->full_name everywhere
    public function getFullNameAttribute(): string
    {
        return $this->user->full_name;
    }

    public function getPhoneNumberAttribute(): ?string
    {
        return $this->user->phone_number;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user->email;
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->user->avatar_url;
    }

    public function getLicenseExpiringSoonAttribute(): bool
    {
        return $this->license_expiry && $this->license_expiry->isBetween(now(), now()->addDays(30));
    }
}
