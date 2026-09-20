<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class TimetableGradeLevel extends Model
{
    use LogsActivity;
    protected $fillable = ['timetable_id', 'grade_level_id'];
}
