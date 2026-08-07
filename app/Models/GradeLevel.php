<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeLevel extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = ['id', 'education_level_id', 'name', 'code', 'sequence', 'status'];

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function learningAreas(): BelongsToMany
    {
        return $this->belongsToMany(LearningArea::class, 'grade_level_learning_area')->withTimestamps();
    }

    public function streams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Stream::class);
    }

    /** The next grade level up the global sequence — Progression's core hook point. */
    public function nextGradeLevel(): ?self
    {
        return static::query()->where('sequence', '>', $this->sequence)->orderBy('sequence')->first();
    }

    public function isSeniorSecondary(): bool
    {
        return $this->educationLevel?->code === 'SS';
    }

    /** True only for the lowest-sequence grade within Senior Secondary (Grade 10) — the pathway-classification point. */
    public function isSeniorSecondaryEntryGrade(): bool
    {
        if (! $this->isSeniorSecondary()) {
            return false;
        }

        $minSequence = static::query()
            ->whereHas('educationLevel', fn ($q) => $q->where('code', 'SS'))
            ->min('sequence');

        return $this->sequence === $minSequence;
    }
}
