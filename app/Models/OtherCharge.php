<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtherCharge extends Model
{
    use HasStringId, softDeletes;

    protected $fillable = [
        'id', 'other_charge_type_id', 'description', 'amount', 'academic_year', 'term',
        'grade_level_id', 'stream_id', 'user_id', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(OtherChargeType::class, 'other_charge_type_id');
    }

    public function gradeLevel(): BelongsTo { return $this->belongsTo(GradeLevel::class); }
    public function stream(): BelongsTo { return $this->belongsTo(Stream::class); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function scopeLabel(): string
    {
        if ($this->user_id) return 'Individual: ' . $this->student?->full_name;
        if ($this->stream_id) return 'Class: ' . $this->stream?->full_name;
        if ($this->grade_level_id) return 'Grade: ' . $this->gradeLevel?->name;
        return 'Unscoped';
    }

    /** Every student this charge actually applies to, resolved from its scope. */
    public function affectedStudentIds(): \Illuminate\Support\Collection
    {
        if ($this->user_id) {
            return collect([$this->user_id]);
        }

        $query = StudentEnrollment::query()->where('status', 'active')->where('academic_year', $this->academic_year);

        if ($this->stream_id) {
            $query->where('stream_id', $this->stream_id);
        } elseif ($this->grade_level_id) {
            $query->where('grade_level_id', $this->grade_level_id);
        } else {
            return collect();
        }

        return $query->pluck('user_id');
    }
}
