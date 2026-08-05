<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stream extends Model
{
    use HasStringId, SoftDeletes;

    protected $fillable = ['id', 'grade_level_id', 'pathway_id', 'name', 'capacity', 'class_teacher_id', 'status'];

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function pathway(): BelongsTo
    {
        return $this->belongsTo(Pathway::class);
    }

    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->gradeLevel?->name} {$this->name}");
    }
}
