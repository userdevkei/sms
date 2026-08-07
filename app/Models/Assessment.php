<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'name', 'learning_area_id', 'stream_id', 'academic_term_id',
        'assessment_type_id', 'max_score', 'assessment_date', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['assessment_date' => 'date'];
    }

    public function learningArea(): BelongsTo { return $this->belongsTo(LearningArea::class); }
    public function stream(): BelongsTo { return $this->belongsTo(Stream::class); }
    public function academicTerm(): BelongsTo { return $this->belongsTo(AcademicTerm::class); }
    public function assessmentType(): BelongsTo { return $this->belongsTo(AssessmentType::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function results(): HasMany { return $this->hasMany(AssessmentResult::class); }

    public function isCompetencyBased(): bool
    {
        return $this->assessmentType?->isCompetencyBased() ?? false;
    }
}
