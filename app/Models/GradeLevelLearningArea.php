<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class GradeLevelLearningArea extends Model
{
    use LogsActivity;
    protected $fillable = ['grade_level_id', 'learning_area_id'];
}
