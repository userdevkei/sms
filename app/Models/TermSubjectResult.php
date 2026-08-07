<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermSubjectResult extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'student_enrollment_id', 'learning_area_id', 'academic_term_id',
        'average_score', 'letter_grade', 'competency_level', 'teacher_remarks',
        'finalized_by', 'finalized_at',
    ];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
    public function learningArea(): BelongsTo { return $this->belongsTo(LearningArea::class); }
    public function academicTerm(): BelongsTo { return $this->belongsTo(AcademicTerm::class); }
}
