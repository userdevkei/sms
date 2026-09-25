<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionLevelSetting extends Model
{
    use HasStringId, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_open'            => 'boolean',
        'requires_interview' => 'boolean',
    ];

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    /** Settings for a level, with sensible defaults when nothing has been saved yet. */
    public static function forLevel(?string $educationLevelId): self
    {
        return static::firstOrNew(
            ['education_level_id' => $educationLevelId],
            ['is_open' => true, 'requires_interview' => false]
        );
    }
}
