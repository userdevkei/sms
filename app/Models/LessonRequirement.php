<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonRequirement extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $fillable = ['id', 'grade_level_id', 'learning_area_id', 'lessons_per_week', 'double_lessons_per_week', 'status'];

    public function gradeLevel() { return $this->belongsTo(GradeLevel::class); }
    public function learningArea() { return $this->belongsTo(LearningArea::class); }
}
