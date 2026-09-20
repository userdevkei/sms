<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableEntry extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;

    protected $fillable = ['id', 'timetable_id', 'time_slot_id', 'grade_level_id', 'learning_area_id', 'subject_teacher_assignment_id', 'teacher_id', 'is_double', 'double_group_id', 'locked', 'source'];

    protected $casts = ['is_double' => 'boolean', 'locked' => 'boolean'];

    public function timeSlot() { return $this->belongsTo(TimeSlot::class); }
    public function gradeLevel() { return $this->belongsTo(GradeLevel::class); }
    public function learningArea() { return $this->belongsTo(LearningArea::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }

    public function streams(): BelongsToMany
    {
        return $this->belongsToMany(Stream::class, 'timetable_entry_streams');
    }
}
