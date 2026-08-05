<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gateway extends Model
{
    use HasStringId, SoftDeletes;

    protected $fillable = ['id', 'type', 'provider', 'name', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function credentials(): HasMany { return $this->hasMany(GatewayCredential::class); }

    public static function active(string $type): ?self
    {
        return static::query()->where('type', $type)->where('is_active', true)->with('credentials')->first();
    }

    /** Returns credentials as a plain ['key' => 'decrypted value'] array — convenient for building an API client. */
    public function config(): array
    {
        return $this->credentials->pluck('value', 'key')->all();
    }

    /**
     * Replaces this gateway's credential set. Any key with a null/empty
     * value is SKIPPED rather than overwritten — this is what lets an edit
     * form leave a secret field blank to mean "keep the existing value"
     * without the controller needing special-case logic per field name.
     */
    public function syncCredentials(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                continue; // blank means "don't touch this credential"
            }

            $this->credentials()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
