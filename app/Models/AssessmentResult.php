<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentResult extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'assessment_id', 'student_enrollment_id', 'score', 'competency_level',
        'is_absent', 'remarks', 'entered_by', 'entered_at',
    ];

    protected function casts(): array
    {
        return ['is_absent' => 'boolean', 'entered_at' => 'datetime'];
    }

    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
}
