<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermOverallResult extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'student_enrollment_id', 'academic_term_id', 'total_score', 'average_score',
        'position_in_stream', 'stream_size', 'position_in_grade', 'grade_size',
        'class_teacher_remarks', 'principal_remarks', 'status', 'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
    public function academicTerm(): BelongsTo { return $this->belongsTo(AcademicTerm::class); }
    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
}
