<?php

namespace App\Models;

use App\Models\Concerns\HasStringId;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableSlotGroupSelection extends Model
{
    use HasStringId, SoftDeletes, LogsActivity;
    protected $fillable = ['id', 'timetable_id', 'education_level_id', 'time_slot_group_id'];

    public function timetable() { return $this->belongsTo(Timetable::class); }
    public function educationLevel() { return $this->belongsTo(EducationLevel::class); }
    public function timeSlotGroup() { return $this->belongsTo(TimeSlotGroup::class); }
}
