<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PathwayLearningArea extends Model
{
    use LogsActivity;
    protected $fillable = ['learning_area_id', 'pathway_id'];
}
