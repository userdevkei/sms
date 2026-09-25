<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionRequirement extends Model
{
    use HasStringId, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
        'options'     => 'array',
    ];

    public const TYPES = [
        'text'     => 'Short text',
        'textarea' => 'Long text',
        'number'   => 'Number',
        'date'     => 'Date',
        'select'   => 'Dropdown (choose one)',
        'checkbox' => 'Yes / No confirmation',
        'file'     => 'File upload',
    ];

    public const DEFAULT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    /** @return string[] */
    public function extensions(): array
    {
        $list = array_filter(array_map('trim', explode(',', strtolower((string) $this->allowed_extensions))));

        return $list ?: self::DEFAULT_EXTENSIONS;
    }

    public function acceptAttribute(): string
    {
        return implode(',', array_map(fn ($e) => '.'.$e, $this->extensions()));
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
