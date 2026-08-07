<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEnrollment extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'user_id', 'grade_level_id', 'stream_id', 'pathway_id',
        'academic_year', 'status', 'enrolled_on', 'exited_on', 'notes',
    ];

    protected function casts(): array
    {
        return ['enrolled_on' => 'date', 'exited_on' => 'date'];
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function gradeLevel(): BelongsTo { return $this->belongsTo(GradeLevel::class); }
    public function stream(): BelongsTo { return $this->belongsTo(Stream::class); }
    public function pathway(): BelongsTo { return $this->belongsTo(Pathway::class); }
    public function progressionException(): HasOne { return $this->hasOne(ProgressionException::class, 'enrollment_id'); }

    // Populated by the Results module — see term_result_completions migration note.
    public function assessmentResults(): HasMany { return $this->hasMany(AssessmentResult::class); }
    public function termSubjectResults(): HasMany { return $this->hasMany(TermSubjectResult::class); }
}
