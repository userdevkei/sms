<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'key', 'value', 'type', 'group'];

    /**
     * Bumped to v2 deliberately — this sidesteps any stale/corrupted cache
     * entry left over from the old single-row Setting model or the old
     * Setting::all() override, without relying on every environment
     * remembering to run cache:clear.
     */
    protected const CACHE_KEY = 'app.settings.v2';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function allSettings(): Collection
    {
        $cached = Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->get()
                ->mapWithKeys(fn ($s) => [$s->key => static::castValue($s->value, $s->type)]);
        });

        // Defensive: if the cache ever returns something that isn't the
        // Collection we expect (corrupted store, incompatible old data,
        // etc.), rebuild it fresh instead of throwing a TypeError.
        if (! $cached instanceof Collection) {
            Cache::forget(self::CACHE_KEY);

            return static::query()->get()
                ->mapWithKeys(fn ($s) => [$s->key => static::castValue($s->value, $s->type)]);
        }

        return $cached;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allSettings()->get($key, $default);
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => static::prepareValue($value, $type), 'type' => $type, 'group' => $group]
        );
    }

    public static function setMany(array $values, array $typesByKey = [], array $groupsByKey = []): void
    {
        foreach ($values as $key => $value) {
            static::set(
                $key,
                $value,
                $typesByKey[$key] ?? static::query()->where('key', $key)->value('type') ?? 'string',
                $groupsByKey[$key] ?? static::query()->where('key', $key)->value('group') ?? 'general'
            );
        }
    }

    public static function group(string $group): Collection
    {
        return static::query()->where('group', $group)->get()
            ->mapWithKeys(fn ($s) => [$s->key => static::castValue($s->value, $s->type)]);
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json'    => json_decode($value ?? '[]', true),
            default   => $value,
        };
    }

    protected static function prepareValue(mixed $value, string $type): ?string
    {
        return match ($type) {
            'json'    => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default   => is_null($value) ? null : (string) $value,
        };
    }
}
