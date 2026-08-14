<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasStringId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasStringId;

    protected $fillable = [
        'id', 'userID', 'first_name', 'middle_name', 'last_name', 'gender',
        'date_of_birth', 'citizenship', 'county', 'sub_county', 'ward', 'ethnicity',
        'email', 'phone_number', 'password', 'status', 'avatar',
    ];
    protected $hidden = ['password', 'remember_token'];

    protected $keyType = 'string';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth'     => 'date',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_users', 'user_id', 'role_id')->withTimestamps();
    }


    public function hasRole(string|array $slugs): bool
    {
        $slugs = (array) $slugs;
        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super_admin') && ! in_array($permission, $this->principalOnlyPermissionSlugs(), true)) {
            return true;
        }

        return $this->allPermissions()->contains($permission);
    }

     protected function principalOnlyPermissionSlugs(): array
    {
        return once(fn () => \App\Models\Permission::where('is_principle', true)->pluck('name')->all());
    }

/*    public function hasAnyPermission(array $permissions): bool
    {
        return $this->allPermissions()->intersect($permissions)->isNotEmpty();
    }*/
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->allPermissions()->intersect($permissions)->isNotEmpty();
    }

    /** Memoized per-request so menu rendering doesn't re-query per item. */
    protected function allPermissions()
    {
        return once(function () {
            return $this->roles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('name')
                ->unique();
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} " . ($this->middle_name ? "{$this->middle_name} " : '') . $this->last_name);
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? route('file', ['path' => $this->avatar])
            : route('file', ['path' => 'Files/images/avatar.png']);
    }

    public function driver(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function currentEnrollment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentEnrollment::class)->where('status', 'active')->latestOfMany('enrolled_on');
    }

    public function roomAllocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RoomAllocation::class);
    }

    public function currentRoomAllocation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RoomAllocation::class)->where('status', 'active')->latestOfMany('allocated_on');
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
